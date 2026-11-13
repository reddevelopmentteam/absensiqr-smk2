<?php

namespace App\Models\Biometric;

use App\Enums\BiometricStatus;
use App\Models\Murid\Murid;
use Illuminate\Database\Eloquent\Model;

class BiometricEvaluation extends Model
{
    protected $table = 'biometric_evaluations';

    protected $fillable = [
        'murid_id',
        'status',
        'source_image_path',
        'source_image_hash',
        'face_count',
        'quality_score',
        'reasons',
        'metrics',
        'faces',
        'processor',
        'processor_version',
        'error',
        'evaluated_at',
    ];

    protected $casts = [
        'reasons' => 'array',
        'metrics' => 'array',
        'faces' => 'array',
        'quality_score' => 'decimal:2',
        'evaluated_at' => 'datetime',
    ];

    public function murid()
    {
        return $this->belongsTo(Murid::class)->withTrashed();
    }

    public function template()
    {
        return $this->hasOne(BiometricTemplate::class, 'evaluation_id');
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('evaluated_at')->orderByDesc('id');
    }

    public function statusEnum(): BiometricStatus
    {
        return BiometricStatus::tryFrom($this->status) ?? BiometricStatus::Pending;
    }
}
