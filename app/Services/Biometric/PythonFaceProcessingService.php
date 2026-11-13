<?php

namespace App\Services\Biometric;

use App\Services\Biometric\Contracts\FaceProcessingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Throwable;

class PythonFaceProcessingService implements FaceProcessingService
{
    public function diagnostics(): array
    {
        $cacheSeconds = (int) config('biometric.face_processor.cache_seconds', 60);

        return Cache::remember('biometric:face-processor:diagnostics', $cacheSeconds, function (): array {
            $script = (string) config('biometric.face_processor.script');
            $python = (string) config('biometric.face_processor.python_binary', 'python3');

            if (! is_file($script)) {
                return [
                    'available' => false,
                    'driver' => 'python',
                    'message' => 'Script pemrosesan wajah tidak ditemukan.',
                ];
            }

            try {
                $result = Process::timeout(5)->run([
                    $python,
                    '-c',
                    'import face_recognition, cv2',
                ]);

                if (! $result->successful()) {
                    return [
                        'available' => false,
                        'driver' => 'python',
                        'message' => 'Engine wajah belum siap. Pasang face_recognition dan opencv-python-headless pada server.',
                    ];
                }
            } catch (Throwable $error) {
                report($error);

                return [
                    'available' => false,
                    'driver' => 'python',
                    'message' => 'Engine wajah tidak dapat dijalankan pada server ini.',
                ];
            }

            return [
                'available' => true,
                'driver' => 'python',
                'message' => 'Engine wajah siap digunakan.',
            ];
        });
    }

    public function analyze(string $absolutePath): array
    {
        $diagnostics = $this->diagnostics();

        if (! $diagnostics['available']) {
            return [
                'available' => false,
                'error' => $diagnostics['message'],
                'faces' => [],
            ];
        }

        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            return [
                'available' => false,
                'error' => 'File gambar tidak dapat dibaca.',
                'faces' => [],
            ];
        }

        try {
            $result = Process::timeout((int) config('biometric.face_processor.timeout', 30))->run([
                (string) config('biometric.face_processor.python_binary', 'python3'),
                (string) config('biometric.face_processor.script'),
                $absolutePath,
            ]);
        } catch (Throwable $error) {
            report($error);

            return [
                'available' => false,
                'error' => 'Proses deteksi wajah gagal dijalankan.',
                'faces' => [],
            ];
        }

        if (! $result->successful()) {
            return [
                'available' => false,
                'error' => 'Proses deteksi wajah gagal.',
                'faces' => [],
            ];
        }

        $payload = json_decode(trim($result->output()), true);

        if (! is_array($payload)) {
            return [
                'available' => false,
                'error' => 'Respons engine wajah tidak valid.',
                'faces' => [],
            ];
        }

        if (! ($payload['available'] ?? false)) {
            return [
                'available' => false,
                'error' => 'Engine wajah tidak dapat menganalisis gambar ini.',
                'faces' => [],
            ];
        }

        $faces = [];

        foreach ((array) ($payload['faces'] ?? []) as $face) {
            if (! is_array($face)) {
                continue;
            }

            $embedding = $face['embedding'] ?? null;

            $faces[] = [
                'x' => (float) ($face['x'] ?? 0),
                'y' => (float) ($face['y'] ?? 0),
                'width' => (float) ($face['width'] ?? 0),
                'height' => (float) ($face['height'] ?? 0),
                'confidence' => (float) ($face['confidence'] ?? 1.0),
                'embedding' => is_array($embedding) ? array_values(array_map('floatval', $embedding)) : [],
            ];
        }

        return [
            'available' => true,
            'error' => null,
            'faces' => $faces,
        ];
    }
}
