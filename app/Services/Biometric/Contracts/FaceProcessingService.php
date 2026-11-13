<?php

namespace App\Services\Biometric\Contracts;

interface FaceProcessingService
{
    /**
     * @return array{available: bool, driver: string, message: string}
     */
    public function diagnostics(): array;

    /**
     * Analyze a local image. The implementation must not persist the image.
     *
     * @return array{
     *     available: bool,
     *     error?: ?string,
     *     faces: array<int, array{
     *         x: int|float,
     *         y: int|float,
     *         width: int|float,
     *         height: int|float,
     *         confidence?: float,
     *         embedding?: array<int, float>
     *     }>
     * }
     */
    public function analyze(string $absolutePath): array;
}
