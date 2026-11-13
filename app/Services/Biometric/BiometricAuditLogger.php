<?php

namespace App\Services\Biometric;

use App\Models\Biometric\BiometricEnrollmentAudit;
use App\Models\Biometric\BiometricTemplate;
use App\Models\Murid\Murid;
use App\Models\User;

class BiometricAuditLogger
{
    /**
     * @param  array<int, array{code: string, message: string}>  $reasons
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        ?Murid $murid,
        string $action,
        ?BiometricTemplate $template = null,
        ?string $statusBefore = null,
        ?string $statusAfter = null,
        array $reasons = [],
        ?User $actor = null,
        array $metadata = [],
    ): BiometricEnrollmentAudit {
        return BiometricEnrollmentAudit::create([
            'murid_id' => $murid?->id,
            'biometric_template_id' => $template?->id,
            'actor_id' => $actor?->id ?? auth()->id(),
            'action' => $action,
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'reasons' => $reasons ?: null,
            'metadata' => $metadata ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
