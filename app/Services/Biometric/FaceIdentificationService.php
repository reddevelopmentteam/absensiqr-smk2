<?php

namespace App\Services\Biometric;

use App\Models\Murid\Murid;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Modules\ScanQR\Services\AttendanceProcessingService;
use Throwable;

class FaceIdentificationService
{
    public function __construct(
        private readonly PhotoQualityInspector $qualityInspector,
        private readonly BiometricAuditLogger $auditLogger,
        private readonly ImagePathResolver $pathResolver,
        private readonly AttendanceProcessingService $attendanceService,
    ) {}

    /**
     * Return candidate students only. Attendance is never written here.
     *
     * @return array{available: bool, candidates: array<int, array{murid: Murid, score: float, distance: float}>}
     */
    public function identify(string $absolutePath, bool $allowNoFace = false): array
    {
        $diagnostics = $this->qualityInspector->processorDiagnostics();

        if (! $diagnostics['available']) {
            throw new DomainException('Identifikasi wajah belum tersedia: '.$diagnostics['message']);
        }

        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            throw new DomainException('Foto pemeriksaan tidak dapat dibaca.');
        }

        try {
            $analysis = $this->qualityInspector->analyzeFace($absolutePath);
        } catch (Throwable $error) {
            report($error);

            throw new DomainException('Engine wajah gagal menganalisis foto pemeriksaan.');
        }

        if (! ($analysis['available'] ?? false)) {
            throw new DomainException((string) ($analysis['error'] ?? 'Engine wajah tidak tersedia.'));
        }

        $faces = array_values((array) ($analysis['faces'] ?? []));

        if (count($faces) !== 1) {
            if ($allowNoFace) {
                return ['available' => true, 'candidates' => []];
            }

            throw new DomainException('Identifikasi membutuhkan tepat satu wajah pada gambar.');
        }

        $probe = $faces[0]['embedding'] ?? null;

        if (! is_array($probe) || $probe === []) {
            throw new DomainException('Template wajah tidak dapat dibuat dari gambar pemeriksaan.');
        }

        $metric = (string) config('biometric.matching.metric', 'euclidean');
        $threshold = (float) config('biometric.matching.threshold', 0.60);
        $maxCandidates = max(1, (int) config('biometric.matching.max_candidates', 5));
        $chunkSize = max(1, (int) config('biometric.matching.chunk_size', 100));
        $candidates = [];

        Murid::query()
            ->aktif()
            ->whereNotNull('image_path')
            ->with(['rombel.tingkat', 'rombel.jurusan', 'rombel.indeks'])
            ->chunkById($chunkSize, function ($muridList) use (&$candidates, $probe, $metric, $threshold): void {
                foreach ($muridList as $murid) {
                    try {
                        $imagePath = $this->pathResolver->resolve($murid->image_path);
                        if (! $imagePath) {
                            continue;
                        }
                        $cacheKey = $this->referenceEmbeddingCacheKey($murid, $imagePath);
                        $stored = Cache::store('file')->remember(
                            $cacheKey,
                            now()->addMinutes(15),
                            function () use ($imagePath): array {
                                $analysis = $this->qualityInspector->analyzeFace($imagePath);
                                if (! ($analysis['available'] ?? false) || count((array) ($analysis['faces'] ?? [])) !== 1) {
                                    return [];
                                }

                                return (array) ($analysis['faces'][0]['embedding'] ?? []);
                            },
                        );
                    } catch (Throwable $error) {
                        report($error);

                        continue;
                    }

                    if (! is_array($stored) || $stored === [] || count($stored) !== count($probe)) {
                        continue;
                    }

                    if ($metric === 'cosine') {
                        $score = $this->cosineSimilarity($probe, $stored);

                        if ($score < $threshold) {
                            continue;
                        }

                        $distance = 1 - $score;
                    } else {
                        $distance = $this->euclideanDistance($probe, $stored);

                        if ($distance > $threshold) {
                            continue;
                        }

                        // Map distance 0..2 to a human-readable similarity score.
                        $score = max(0.0, min(1.0, 1 - ($distance / 2)));
                    }

                    $candidates[] = [
                        'murid' => $murid,
                        'score' => $score,
                        'distance' => $distance,
                    ];
                }
            });

        usort($candidates, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return [
            'available' => true,
            'candidates' => array_slice($candidates, 0, $maxCandidates),
        ];
    }

    private function referenceEmbeddingCacheKey(Murid $murid, string $imagePath): string
    {
        $fingerprint = implode('|', [
            $murid->image_path,
            (string) (filesize($imagePath) ?: 0),
            (string) (filemtime($imagePath) ?: 0),
            (string) config('biometric.face_processor.model_version', 'face_recognition-128d-v1'),
        ]);

        return 'face-recognition:reference:'.$murid->id.':'.hash('sha256', $fingerprint);
    }

    /**
     * Record attendance for a face-recognized student through the existing
     * QR/hardware attendance service.
     *
     * @return array{scanResult: array, murid: ?Murid, jadwal: array, absen: mixed}
     */
    public function recordAttendance(Murid $murid, User $officer, ?string $probePath = null): array
    {
        $murid->refresh();

        if ($murid->trashed() || $murid->status !== 'aktif') {
            throw new DomainException('Murid tidak aktif atau telah dihapus.');
        }

        if (! $murid->image_path || ! $this->pathResolver->resolve($murid->image_path)) {
            throw new DomainException('Foto murid tidak tersedia untuk identifikasi.');
        }

        if ($probePath !== null) {
            $candidates = $this->identify($probePath)['candidates'];
            $matched = collect($candidates)->first(
                static fn (array $candidate): bool => $candidate['murid']->id === $murid->id,
            );

            if (! $matched) {
                throw new DomainException('Kandidat tidak cukup yakin. Ulangi pengambilan foto atau gunakan QR/hardware scanner.');
            }
        }

        $result = $this->attendanceService->process($murid->uuid);
        $status = $result['scanResult']['status'] ?? 'failed';

        $this->auditLogger->log(
            $murid,
            'attendance_face_recognized',
            null,
            null,
            null,
            [],
            $officer,
            [
                'scan_status' => $status,
                'probe_verified' => $probePath !== null,
            ],
        );

        return $result;
    }

    /**
     * Cosine similarity for normalized face embeddings.
     *
     * @param  array<int, float|int>  $left
     * @param  array<int, float|int>  $right
     */
    private function cosineSimilarity(array $left, array $right): float
    {
        if (count($left) !== count($right) || $left === []) {
            return 0.0;
        }

        $length = count($left);

        $dot = 0.0;
        $leftMagnitude = 0.0;
        $rightMagnitude = 0.0;

        for ($index = 0; $index < $length; $index++) {
            $leftValue = (float) $left[$index];
            $rightValue = (float) $right[$index];

            $dot += $leftValue * $rightValue;
            $leftMagnitude += $leftValue * $leftValue;
            $rightMagnitude += $rightValue * $rightValue;
        }

        if ($leftMagnitude <= 0 || $rightMagnitude <= 0) {
            return 0.0;
        }

        return $dot / (sqrt($leftMagnitude) * sqrt($rightMagnitude));
    }

    /**
     * Euclidean distance used by the face_recognition model.
     *
     * @param  array<int, float|int>  $left
     * @param  array<int, float|int>  $right
     */
    private function euclideanDistance(array $left, array $right): float
    {
        $sum = 0.0;
        $length = count($left);

        for ($index = 0; $index < $length; $index++) {
            $difference = (float) $left[$index] - (float) $right[$index];
            $sum += $difference * $difference;
        }

        return sqrt($sum);
    }
}
