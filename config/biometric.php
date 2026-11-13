<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Face processing engine
    |--------------------------------------------------------------------------
    |
    | The application intentionally does not bundle a face model. Choose a
    | processor that has been validated on the target server. The default
    | "python" adapter uses scripts/face_processor.py and requires the
    | face_recognition and opencv-python-headless packages. When the engine is
    | unavailable, enrollment is blocked and the admin UI shows a diagnostic.
    |
    */
    'face_processor' => [
        'driver' => env('BIOMETRIC_FACE_DRIVER', 'python'),
        'python_binary' => env('BIOMETRIC_PYTHON_BINARY', 'python3'),
        'script' => base_path('scripts/face_processor.py'),
        'timeout' => (int) env('BIOMETRIC_FACE_TIMEOUT', 30),
        'cache_seconds' => (int) env('BIOMETRIC_FACE_DIAGNOSTIC_CACHE', 60),
        'model_version' => env('BIOMETRIC_FACE_MODEL_VERSION', 'face_recognition-128d-v1'),
        'algorithm' => env('BIOMETRIC_FACE_ALGORITHM', 'face_recognition'),
    ],

    'quality' => [
        'min_width' => (int) env('BIOMETRIC_MIN_WIDTH', 320),
        'min_height' => (int) env('BIOMETRIC_MIN_HEIGHT', 320),
        'min_face_ratio' => (float) env('BIOMETRIC_MIN_FACE_RATIO', 0.10),
        'edge_margin_ratio' => (float) env('BIOMETRIC_EDGE_MARGIN_RATIO', 0.02),
        'min_brightness' => (float) env('BIOMETRIC_MIN_BRIGHTNESS', 45),
        'max_brightness' => (float) env('BIOMETRIC_MAX_BRIGHTNESS', 215),
        'min_sharpness' => (float) env('BIOMETRIC_MIN_SHARPNESS', 6.0),
        'min_score' => (float) env('BIOMETRIC_MIN_SCORE', 60),
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
    ],

    'templates' => [
        'ttl_months' => (int) env('BIOMETRIC_TEMPLATE_TTL_MONTHS', 12),
    ],

    'matching' => [
        // face_recognition uses Euclidean distance with a 0.60 threshold.
        // Set to "cosine" only when the configured engine emits normalized vectors.
        'metric' => env('BIOMETRIC_MATCH_METRIC', 'euclidean'),
        'threshold' => (float) env('BIOMETRIC_MATCH_THRESHOLD', 0.60),
        'max_candidates' => (int) env('BIOMETRIC_MAX_CANDIDATES', 5),
        'chunk_size' => (int) env('BIOMETRIC_MATCH_CHUNK_SIZE', 100),
    ],

    'consent_version' => env('BIOMETRIC_CONSENT_VERSION', '2026-11-v1'),
];
