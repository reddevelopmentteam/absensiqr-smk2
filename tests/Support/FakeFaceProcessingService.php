<?php

namespace Tests\Support;

use App\Services\Biometric\Contracts\FaceProcessingService;

class FakeFaceProcessingService implements FaceProcessingService
{
    public bool $available = true;

    /** @var array<int, array<string, mixed>> */
    public array $faces = [];

    public function __construct()
    {
        $this->faces = [[
            'x' => 220,
            'y' => 160,
            'width' => 200,
            'height' => 240,
            'confidence' => 1.0,
            'embedding' => [1.0, 0.0, 0.0],
        ]];
    }

    public function diagnostics(): array
    {
        return [
            'available' => $this->available,
            'driver' => 'fake',
            'message' => $this->available ? 'Fake engine ready.' : 'Fake engine unavailable.',
        ];
    }

    public function analyze(string $absolutePath): array
    {
        return [
            'available' => $this->available,
            'error' => $this->available ? null : 'Fake engine unavailable.',
            'faces' => $this->available ? $this->faces : [],
        ];
    }
}
