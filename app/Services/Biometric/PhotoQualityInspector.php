<?php

namespace App\Services\Biometric;

use App\Enums\BiometricStatus;
use App\Services\Biometric\Contracts\FaceProcessingService;
use Throwable;

class PhotoQualityInspector
{
    public function __construct(
        private readonly FaceProcessingService $faceProcessor,
        private readonly ImagePathResolver $pathResolver,
    ) {}

    /**
     * @return array{available: bool, driver: string, message: string}
     */
    public function processorDiagnostics(): array
    {
        return $this->faceProcessor->diagnostics();
    }

    /**
     * @return array{available: bool, error?: ?string, faces: array<int, array<string, mixed>>}
     */
    public function analyzeFace(string $absolutePath): array
    {
        return $this->faceProcessor->analyze($absolutePath);
    }

    public function inspect(?string $absolutePath): FaceEvaluationResult
    {
        $reasons = [];
        $metrics = [];
        $faces = [];
        $analysis = [];
        $faceCount = 0;
        $score = 0;
        $sourceHash = $this->pathResolver->hash($absolutePath);

        if (! $absolutePath || ! is_file($absolutePath)) {
            $this->addReason($reasons, 'photo_missing', 'Foto referensi tidak ditemukan.');

            return $this->result(BiometricStatus::Rejected, $score, $reasons, $metrics, $faceCount, $faces, $analysis, $sourceHash);
        }

        if (! is_readable($absolutePath)) {
            $this->addReason($reasons, 'photo_unreadable', 'Foto referensi tidak dapat dibaca.');

            return $this->result(BiometricStatus::Rejected, $score, $reasons, $metrics, $faceCount, $faces, $analysis, $sourceHash);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($absolutePath) ?: null;
        $allowedMimes = (array) config('biometric.quality.allowed_mimes', []);

        $metrics['mime'] = $mime;
        $metrics['file_size'] = filesize($absolutePath) ?: 0;

        if (! $mime || ! in_array($mime, $allowedMimes, true)) {
            $this->addReason($reasons, 'mime_invalid', 'Format file bukan JPG, PNG, atau WEBP yang valid.');

            return $this->result(BiometricStatus::Rejected, $score, $reasons, $metrics, $faceCount, $faces, $analysis, $sourceHash);
        }

        $imageInfo = @getimagesize($absolutePath);

        if (! is_array($imageInfo)) {
            $this->addReason($reasons, 'image_corrupt', 'File gambar rusak atau tidak dapat dibaca.');

            return $this->result(BiometricStatus::Rejected, $score, $reasons, $metrics, $faceCount, $faces, $analysis, $sourceHash);
        }

        [$width, $height] = $imageInfo;
        $metrics['width'] = (int) $width;
        $metrics['height'] = (int) $height;

        $minWidth = (int) config('biometric.quality.min_width', 320);
        $minHeight = (int) config('biometric.quality.min_height', 320);

        if ($width < $minWidth || $height < $minHeight) {
            $this->addReason($reasons, 'resolution_too_small', "Resolusi foto minimal {$minWidth}x{$minHeight} piksel.");
        }

        $image = @imagecreatefromstring((string) file_get_contents($absolutePath));

        if (! $image) {
            $this->addReason($reasons, 'image_corrupt', 'File gambar rusak atau tidak dapat diproses.');

            return $this->result(BiometricStatus::Rejected, $score, $reasons, $metrics, $faceCount, $faces, $analysis, $sourceHash);
        }

        try {
            $lighting = $this->lightingAndSharpness($image);
            $metrics['brightness'] = round($lighting['brightness'], 2);
            $metrics['sharpness'] = round($lighting['sharpness'], 2);

            $minBrightness = (float) config('biometric.quality.min_brightness', 45);
            $maxBrightness = (float) config('biometric.quality.max_brightness', 215);
            $minSharpness = (float) config('biometric.quality.min_sharpness', 6.0);

            if ($lighting['brightness'] < $minBrightness) {
                $this->addReason($reasons, 'lighting_too_dark', 'Pencahayaan foto terlalu gelap.');
            } elseif ($lighting['brightness'] > $maxBrightness) {
                $this->addReason($reasons, 'lighting_too_bright', 'Pencahayaan foto terlalu terang.');
            }

            if ($lighting['sharpness'] < $minSharpness) {
                $this->addReason($reasons, 'image_blurry', 'Foto terlalu buram. Gunakan foto yang lebih tajam.');
            }
        } finally {
            imagedestroy($image);
        }

        try {
            $analysis = $this->faceProcessor->analyze($absolutePath);
        } catch (Throwable $error) {
            report($error);
            $analysis = [
                'available' => false,
                'error' => 'Engine pemrosesan wajah gagal menganalisis gambar.',
                'faces' => [],
            ];
        }

        $faces = array_values((array) ($analysis['faces'] ?? []));
        $faceCount = count($faces);
        $metrics['face_count'] = $faceCount;
        $metrics['processor_available'] = (bool) ($analysis['available'] ?? false);

        if (! ($analysis['available'] ?? false)) {
            $this->addReason(
                $reasons,
                'face_processor_unavailable',
                (string) ($analysis['error'] ?? 'Engine pemrosesan wajah belum tersedia.'),
            );
        } elseif ($faceCount === 0) {
            $this->addReason($reasons, 'no_face', 'Tidak ada wajah yang terdeteksi pada foto.');
        } elseif ($faceCount > 1) {
            $this->addReason($reasons, 'multiple_faces', 'Terdeteksi lebih dari satu wajah. Gunakan foto dengan satu wajah.');
        } else {
            $face = $faces[0];
            $faceWidth = (float) ($face['width'] ?? 0);
            $faceHeight = (float) ($face['height'] ?? 0);
            $faceX = (float) ($face['x'] ?? 0);
            $faceY = (float) ($face['y'] ?? 0);
            $metrics['face_width'] = round($faceWidth, 2);
            $metrics['face_height'] = round($faceHeight, 2);

            if ($faceWidth <= 0 || $faceHeight <= 0) {
                $this->addReason($reasons, 'invalid_face_box', 'Area wajah tidak valid.');
            } else {
                $faceRatio = min($faceWidth / max(1, $width), $faceHeight / max(1, $height));
                $metrics['face_ratio'] = round($faceRatio, 4);

                if ($faceRatio < (float) config('biometric.quality.min_face_ratio', 0.10)) {
                    $this->addReason($reasons, 'face_too_small', 'Wajah terlalu kecil pada foto. Gunakan foto dengan wajah lebih jelas.');
                }

                if ($faceX < 0 || $faceY < 0 || ($faceX + $faceWidth) > $width || ($faceY + $faceHeight) > $height) {
                    $this->addReason($reasons, 'face_outside_frame', 'Area wajah keluar dari batas foto.');
                } else {
                    $margin = (float) config('biometric.quality.edge_margin_ratio', 0.02) * min($width, $height);
                    $minMargin = min($faceX, $faceY, $width - ($faceX + $faceWidth), $height - ($faceY + $faceHeight));
                    $metrics['face_margin'] = round($minMargin, 2);

                    if ($minMargin < $margin) {
                        $this->addReason($reasons, 'face_cropped', 'Wajah terlalu dekat tepi foto dan berpotensi terpotong.');
                    }
                }

                if (empty($face['embedding']) || ! is_array($face['embedding'])) {
                    $this->addReason($reasons, 'embedding_unavailable', 'Template biometrik tidak dapat dibuat dari wajah ini.');
                }
            }
        }

        $score = $this->score($metrics, $reasons);
        $status = $this->statusFromReasons($reasons);

        if ($status === BiometricStatus::Eligible && $score < (float) config('biometric.quality.min_score', 60)) {
            $this->addReason($reasons, 'quality_score_low', 'Skor kualitas foto belum memenuhi ambang enrollment.');
            $status = BiometricStatus::NeedsReview;
        }

        if ($status === BiometricStatus::Rejected) {
            $score = 0;
        }

        return $this->result($status, $score, $reasons, $metrics, $faceCount, $faces, $analysis, $sourceHash);
    }

    /**
     * @return array{brightness: float, sharpness: float}
     */
    private function lightingAndSharpness(\GdImage $image): array
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $sampleWidth = min($width, 640);
        $sampleHeight = min($height, 640);

        $sample = imagecreatetruecolor($sampleWidth, $sampleHeight);
        imagecopyresampled($sample, $image, 0, 0, 0, 0, $sampleWidth, $sampleHeight, $width, $height);
        imagefilter($sample, IMG_FILTER_GRAYSCALE);

        $sum = 0.0;
        $sumSquares = 0.0;
        $gradient = 0.0;
        $count = 0;

        for ($y = 1; $y < $sampleHeight - 1; $y += 2) {
            for ($x = 1; $x < $sampleWidth - 1; $x += 2) {
                $center = imagecolorat($sample, $x, $y) & 0xFF;
                $left = imagecolorat($sample, $x - 1, $y) & 0xFF;
                $right = imagecolorat($sample, $x + 1, $y) & 0xFF;
                $top = imagecolorat($sample, $x, $y - 1) & 0xFF;
                $bottom = imagecolorat($sample, $x, $y + 1) & 0xFF;

                $sum += $center;
                $sumSquares += $center * $center;
                $gradient += abs((4 * $center) - $left - $right - $top - $bottom);
                $count++;
            }
        }

        imagedestroy($sample);

        if ($count === 0) {
            return ['brightness' => 0.0, 'sharpness' => 0.0];
        }

        $mean = $sum / $count;
        $variance = max(0, ($sumSquares / $count) - ($mean * $mean));

        return [
            'brightness' => $mean,
            'sharpness' => ($gradient / $count) + sqrt($variance) * 0.1,
        ];
    }

    /**
     * @param  array<int, array{code: string, message: string}>  $reasons
     * @param  array<string, mixed>  $metrics
     */
    private function score(array $metrics, array $reasons): int
    {
        $score = 100.0;
        $sharpness = (float) ($metrics['sharpness'] ?? 0);
        $brightness = (float) ($metrics['brightness'] ?? 0);
        $faceRatio = (float) ($metrics['face_ratio'] ?? 0);
        $minSharpness = (float) config('biometric.quality.min_sharpness', 6.0);

        $score -= max(0, $minSharpness - $sharpness) * 5;
        $score -= abs($brightness - 128) * 0.25;
        $score -= max(0, (float) config('biometric.quality.min_face_ratio', 0.10) - $faceRatio) * 150;
        $score -= count($reasons) * 8;

        return (int) max(0, min(100, round($score)));
    }

    /**
     * @param  array<int, array{code: string, message: string}>  $reasons
     */
    private function statusFromReasons(array $reasons): BiometricStatus
    {
        $hardFailures = [
            'photo_missing',
            'photo_unreadable',
            'mime_invalid',
            'image_corrupt',
            'resolution_too_small',
            'lighting_too_dark',
            'lighting_too_bright',
            'image_blurry',
            'no_face',
            'multiple_faces',
            'invalid_face_box',
            'face_outside_frame',
        ];

        foreach ($reasons as $reason) {
            if (in_array($reason['code'], $hardFailures, true)) {
                return BiometricStatus::Rejected;
            }
        }

        if ($reasons !== []) {
            return BiometricStatus::NeedsReview;
        }

        return BiometricStatus::Eligible;
    }

    /**
     * @param  array<int, array{code: string, message: string}>  $reasons
     */
    private function addReason(array &$reasons, string $code, string $message): void
    {
        $reasons[] = ['code' => $code, 'message' => $message];
    }

    /**
     * @param  array<int, array{code: string, message: string}>  $reasons
     * @param  array<string, mixed>  $metrics
     * @param  array<int, array<string, mixed>>  $faces
     * @param  array<string, mixed>  $analysis
     */
    private function result(
        BiometricStatus $status,
        int $score,
        array $reasons,
        array $metrics,
        int $faceCount,
        array $faces,
        array $analysis,
        ?string $sourceHash,
    ): FaceEvaluationResult {
        return new FaceEvaluationResult(
            status: $status,
            score: $score,
            reasons: $reasons,
            metrics: $metrics,
            faceCount: $faceCount,
            faces: $faces,
            analysis: $analysis,
            sourceHash: $sourceHash,
        );
    }
}
