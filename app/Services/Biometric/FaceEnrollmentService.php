<?php

namespace App\Services\Biometric;

use App\Enums\BiometricConsentStatus;
use App\Enums\BiometricStatus;
use App\Models\Biometric\BiometricConsent;
use App\Models\Biometric\BiometricEvaluation;
use App\Models\Biometric\BiometricTemplate;
use App\Models\Murid\Murid;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class FaceEnrollmentService
{
    public function __construct(
        private readonly PhotoQualityInspector $qualityInspector,
        private readonly ImagePathResolver $pathResolver,
        private readonly BiometricAuditLogger $auditLogger,
    ) {}

    public function grantConsent(Murid $murid, User $actor, ?string $notes = null): BiometricConsent
    {
        $this->assertStudentActive($murid);

        return DB::transaction(function () use ($murid, $actor, $notes): BiometricConsent {
            $consent = BiometricConsent::query()->firstOrNew(['murid_id' => $murid->id]);
            $before = $consent->status;

            $consent->fill([
                'status' => BiometricConsentStatus::Active->value,
                'consent_version' => (string) config('biometric.consent_version', '2026-11-v1'),
                'source' => 'admin',
                'consented_at' => now(),
                'revoked_at' => null,
                'granted_by' => $actor->id,
                'revoked_by' => null,
                'notes' => $notes,
            ])->save();

            $this->auditLogger->log(
                $murid,
                'consent_granted',
                null,
                $before,
                BiometricConsentStatus::Active->value,
                [],
                $actor,
                ['consent_version' => $consent->consent_version],
            );

            return $consent;
        });
    }

    public function revokeConsent(Murid $murid, User $actor, ?string $notes = null): BiometricConsent
    {
        return DB::transaction(function () use ($murid, $actor, $notes): BiometricConsent {
            $consent = BiometricConsent::query()->where('murid_id', $murid->id)->first();

            if (! $consent) {
                throw new DomainException('Persetujuan biometrik belum pernah dicatat untuk murid ini.');
            }

            $before = $consent->status;
            $consent->update([
                'status' => BiometricConsentStatus::Revoked->value,
                'revoked_at' => now(),
                'revoked_by' => $actor->id,
                'notes' => $notes ?: $consent->notes,
            ]);

            $this->auditLogger->log(
                $murid,
                'consent_revoked',
                null,
                $before,
                BiometricConsentStatus::Revoked->value,
                [],
                $actor,
            );

            $this->suspend($murid, 'consent_revoked', $actor);

            return $consent->refresh();
        });
    }

    public function evaluate(Murid $murid, ?User $actor = null): BiometricEvaluation
    {
        [, $evaluation] = $this->evaluateInternal($murid, $actor);

        return $evaluation;
    }

    public function enroll(Murid $murid, User $actor): BiometricTemplate
    {
        return DB::transaction(function () use ($murid, $actor): BiometricTemplate {
            $murid->refresh();
            $this->assertStudentActive($murid);

            $consent = $murid->biometricConsent;

            if (! $consent?->isActive()) {
                throw new DomainException('Enrollment ditolak: persetujuan biometrik aktif belum tersedia.');
            }

            [$result, $evaluation] = $this->evaluateInternal($murid, $actor);

            if ($result->status !== BiometricStatus::Eligible || $evaluation->status !== BiometricStatus::Eligible->value) {
                throw new DomainException('Enrollment ditolak: '.implode(' ', $this->evaluationMessages($evaluation)));
            }

            $embedding = $result->embedding();

            if (! is_array($embedding) || $embedding === []) {
                throw new DomainException('Enrollment ditolak: template biometrik tidak tersedia.');
            }

            $template = BiometricTemplate::query()->firstOrNew(['murid_id' => $murid->id]);
            $before = $template->status;

            $template->fill([
                'evaluation_id' => $evaluation->id,
                'status' => BiometricStatus::Enrolled->value,
                'algorithm' => (string) config('biometric.face_processor.algorithm', 'face_recognition'),
                'model_version' => (string) config('biometric.face_processor.model_version', 'face_recognition-128d-v1'),
                'template_payload' => [
                    'embedding' => array_values(array_map('floatval', $embedding)),
                    'face' => $evaluation->faces[0] ?? [],
                    'source_image_hash' => $evaluation->source_image_hash,
                    'enrolled_at' => now()->toIso8601String(),
                ],
                'source_image_path' => $murid->image_path,
                'source_image_hash' => $evaluation->source_image_hash,
                'quality_score' => $evaluation->quality_score,
                'enrolled_at' => now(),
                'expires_at' => now()->addMonths((int) config('biometric.templates.ttl_months', 12)),
                'suspended_at' => null,
                'suspension_reason' => null,
            ])->save();

            $this->auditLogger->log(
                $murid,
                'enrolled',
                $template,
                $before,
                BiometricStatus::Enrolled->value,
                $evaluation->reasons ?? [],
                $actor,
                [
                    'evaluation_id' => $evaluation->id,
                    'quality_score' => $evaluation->quality_score,
                    'model_version' => $template->model_version,
                ],
            );

            return $template->refresh();
        });
    }

    public function suspend(Murid $murid, string $reason, ?User $actor = null): ?BiometricTemplate
    {
        $template = BiometricTemplate::query()->where('murid_id', $murid->id)->first();

        if (! $template) {
            return null;
        }

        $before = $template->status;
        $template->update([
            'status' => BiometricStatus::Suspended->value,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);

        $this->auditLogger->log(
            $murid,
            'suspended',
            $template,
            $before,
            BiometricStatus::Suspended->value,
            [['code' => $reason, 'message' => $this->suspensionMessage($reason)]],
            $actor,
        );

        return $template->refresh();
    }

    public function markNeedsReview(Murid $murid, string $reason, ?User $actor = null): ?BiometricTemplate
    {
        $template = BiometricTemplate::query()->where('murid_id', $murid->id)->first();

        if (! $template) {
            return null;
        }

        $before = $template->status;
        $template->update([
            'status' => BiometricStatus::NeedsReview->value,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);

        $this->auditLogger->log(
            $murid,
            'needs_review',
            $template,
            $before,
            BiometricStatus::NeedsReview->value,
            [['code' => $reason, 'message' => $this->suspensionMessage($reason)]],
            $actor,
        );

        return $template->refresh();
    }

    public function handleReferencePhotoChanged(Murid $murid): void
    {
        $template = BiometricTemplate::query()->where('murid_id', $murid->id)->first();

        if ($template && $template->status === BiometricStatus::Enrolled->value) {
            $this->markNeedsReview($murid, 'reference_photo_changed');
        }
    }

    public function handleStudentUnavailable(Murid $murid, string $reason = 'student_inactive'): void
    {
        $template = BiometricTemplate::query()->where('murid_id', $murid->id)->first();

        if ($template && $template->status !== BiometricStatus::Suspended->value) {
            $this->suspend($murid, $reason);
        }
    }

    private function assertStudentActive(Murid $murid): void
    {
        if ($murid->trashed() || $murid->status !== 'aktif') {
            throw new DomainException('Murid tidak aktif atau telah dihapus.');
        }
    }

    /**
     * @return array{0: FaceEvaluationResult, 1: BiometricEvaluation}
     */
    private function evaluateInternal(Murid $murid, ?User $actor): array
    {
        $murid->refresh();

        if ($murid->trashed() || $murid->status !== 'aktif') {
            $result = new FaceEvaluationResult(
                status: BiometricStatus::Rejected,
                score: 0,
                reasons: [['code' => 'student_inactive', 'message' => 'Murid tidak aktif atau telah dihapus.']],
                metrics: [],
                faceCount: 0,
                faces: [],
                analysis: [],
            );

            return [$result, $this->storeEvaluation($murid, $result, $actor)];
        }

        $consent = $murid->biometricConsent;

        if (! $consent?->isActive()) {
            $result = new FaceEvaluationResult(
                status: BiometricStatus::Rejected,
                score: 0,
                reasons: [['code' => 'consent_missing', 'message' => 'Persetujuan biometrik aktif belum tersedia.']],
                metrics: [],
                faceCount: 0,
                faces: [],
                analysis: [],
            );

            return [$result, $this->storeEvaluation($murid, $result, $actor)];
        }

        $absolutePath = $this->pathResolver->resolve($murid->image_path);
        $result = $this->qualityInspector->inspect($absolutePath);

        return [$result, $this->storeEvaluation($murid, $result, $actor)];
    }

    private function storeEvaluation(Murid $murid, FaceEvaluationResult $result, ?User $actor): BiometricEvaluation
    {
        $evaluation = BiometricEvaluation::create([
            'murid_id' => $murid->id,
            'status' => $result->status->value,
            'source_image_path' => $murid->image_path,
            'source_image_hash' => $result->sourceHash,
            'face_count' => $result->faceCount,
            'quality_score' => $result->score,
            'reasons' => $result->reasons,
            'metrics' => $result->metrics,
            'faces' => $this->facesWithoutEmbeddings($result->faces),
            'processor' => (string) config('biometric.face_processor.driver', 'python'),
            'processor_version' => (string) config('biometric.face_processor.model_version', 'face_recognition-128d-v1'),
            'error' => $result->analysis['error'] ?? null,
            'evaluated_at' => now(),
        ]);

        $this->auditLogger->log(
            $murid,
            'evaluation',
            $murid->biometricTemplate,
            null,
            $result->status->value,
            $result->reasons,
            $actor,
            [
                'evaluation_id' => $evaluation->id,
                'quality_score' => $result->score,
                'face_count' => $result->faceCount,
            ],
        );

        return $evaluation;
    }

    /**
     * Biometric embeddings are only persisted in the encrypted template table.
     * Evaluation records keep geometry and confidence for audit/UI, not vectors.
     *
     * @param  array<int, array<string, mixed>>  $faces
     * @return array<int, array<string, mixed>>
     */
    private function facesWithoutEmbeddings(array $faces): array
    {
        return array_map(function (array $face): array {
            unset($face['embedding']);

            return $face;
        }, $faces);
    }

    /** @return array<int, string> */
    private function evaluationMessages(BiometricEvaluation $evaluation): array
    {
        $reasons = (array) ($evaluation->reasons ?? []);

        if ($reasons === []) {
            return ['Kualitas foto belum memenuhi syarat enrollment.'];
        }

        return array_values(array_map(
            static fn (array $reason): string => (string) ($reason['message'] ?? $reason['code'] ?? ''),
            $reasons,
        ));
    }

    private function suspensionMessage(string $reason): string
    {
        return match ($reason) {
            'consent_revoked' => 'Persetujuan biometrik telah dicabut.',
            'reference_photo_changed' => 'Foto referensi berubah; enrollment perlu ditinjau ulang.',
            'student_inactive' => 'Murid tidak aktif atau telah dihapus.',
            'student_deleted' => 'Murid telah dihapus; profil biometrik tidak dapat digunakan.',
            'template_expired' => 'Template biometrik telah kedaluwarsa.',
            default => 'Profil biometrik ditangguhkan.',
        };
    }
}
