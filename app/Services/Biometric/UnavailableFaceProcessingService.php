<?php

namespace App\Services\Biometric;

use App\Services\Biometric\Contracts\FaceProcessingService;

class UnavailableFaceProcessingService implements FaceProcessingService
{
    public function diagnostics(): array
    {
        return [
            'available' => false,
            'driver' => 'unavailable',
            'message' => 'Engine pemrosesan wajah belum dikonfigurasi pada server ini.',
        ];
    }

    public function analyze(string $absolutePath): array
    {
        return [
            'available' => false,
            'error' => $this->diagnostics()['message'],
            'faces' => [],
        ];
    }
}
