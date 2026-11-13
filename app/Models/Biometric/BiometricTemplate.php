<?php

namespace App\Models\Biometric;

use App\Enums\BiometricStatus;
use App\Models\Murid\Murid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BiometricTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'biometric_templates';

    protected $fillable = [
        'uuid',
        'murid_id',
        'evaluation_id',
        'status',
        'algorithm',
        'model_version',
        'template_payload',
        'source_image_path',
        'source_image_hash',
        'quality_score',
        'enrolled_at',
        'expires_at',
        'suspended_at',
        'suspension_reason',
        'last_verified_at',
    ];

    protected $hidden = [
        'template_payload',
    ];

    protected $casts = [
        'template_payload' => 'encrypted:array',
        'quality_score' => 'decimal:2',
        'enrolled_at' => 'datetime',
        'expires_at' => 'datetime',
        'suspended_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $template): void {
            if (! $template->uuid) {
                $template->uuid = (string) Str::uuid();
            }
        });
    }

    public function murid()
    {
        return $this->belongsTo(Murid::class)->withTrashed();
    }

    public function evaluation()
    {
        return $this->belongsTo(BiometricEvaluation::class, 'evaluation_id');
    }

    public function audits()
    {
        return $this->hasMany(BiometricEnrollmentAudit::class);
    }

    public function scopeUsable($query)
    {
        return $query
            ->where('status', BiometricStatus::Enrolled->value)
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->whereHas('murid', function ($query): void {
                $query->where('status', 'aktif')->whereNull('deleted_at');
            })
            ->whereHas('murid.biometricConsent', function ($query): void {
                $query->where('status', 'active')->whereNull('revoked_at');
            });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return $this->status === BiometricStatus::Enrolled->value
            && ! $this->isExpired()
            && ! $this->trashed()
            && $this->murid
            && $this->murid->status === 'aktif'
            && ! $this->murid->trashed()
            && $this->murid->biometricConsent?->isActive();
    }

    public function statusEnum(): BiometricStatus
    {
        return BiometricStatus::tryFrom($this->status) ?? BiometricStatus::Pending;
    }
}
