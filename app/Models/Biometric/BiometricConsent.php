<?php

namespace App\Models\Biometric;

use App\Enums\BiometricConsentStatus;
use App\Models\Murid\Murid;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BiometricConsent extends Model
{
    protected $table = 'biometric_consents';

    protected $fillable = [
        'murid_id',
        'status',
        'consent_version',
        'source',
        'consented_at',
        'revoked_at',
        'granted_by',
        'revoked_by',
        'notes',
    ];

    protected $casts = [
        'consented_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function murid()
    {
        return $this->belongsTo(Murid::class)->withTrashed();
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', BiometricConsentStatus::Active->value)
            ->whereNull('revoked_at');
    }

    public function isActive(): bool
    {
        return $this->status === BiometricConsentStatus::Active->value && $this->revoked_at === null;
    }
}
