<?php

namespace App\Services\Biometric;

use App\Enums\BiometricStatus;

final class FaceEvaluationResult
{
    /**
     * @param  array<int, array{code: string, message: string}>  $reasons
     * @param  array<string, mixed>  $metrics
     * @param  array<int, array<string, mixed>>  $faces
     * @param  array<string, mixed>  $analysis
     */
    public function __construct(
        public readonly BiometricStatus $status,
        public readonly int $score,
        public readonly array $reasons,
        public readonly array $metrics,
        public readonly int $faceCount,
        public readonly array $faces,
        public readonly array $analysis,
        public readonly ?string $sourceHash = null,
    ) {}

    public function isEligible(): bool
    {
        return $this->status === BiometricStatus::Eligible;
    }

    /** @return array<int, string> */
    public function reasonCodes(): array
    {
        return array_map(static fn (array $reason): string => $reason['code'], $this->reasons);
    }

    /** @return array<int, string> */
    public function reasonMessages(): array
    {
        return array_map(static fn (array $reason): string => $reason['message'], $this->reasons);
    }

    public function embedding(): ?array
    {
        $embedding = $this->faces[0]['embedding'] ?? null;

        return is_array($embedding) && $embedding !== [] ? $embedding : null;
    }
}
