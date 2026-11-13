<?php

namespace App\Models\Biometric;

use App\Models\Murid\Murid;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BiometricEnrollmentAudit extends Model
{
    protected $table = 'biometric_enrollment_audits';

    protected $fillable = [
        'murid_id',
        'biometric_template_id',
        'actor_id',
        'action',
        'status_before',
        'status_after',
        'reasons',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'reasons' => 'array',
        'metadata' => 'array',
    ];

    public function murid()
    {
        return $this->belongsTo(Murid::class)->withTrashed();
    }

    public function template()
    {
        return $this->belongsTo(BiometricTemplate::class, 'biometric_template_id')->withTrashed();
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
