<?php

namespace App\Models\Murid;

use App\Enums\StudentStatus;
use App\Models\Biometric\BiometricConsent;
use App\Models\Biometric\BiometricEvaluation;
use App\Models\Biometric\BiometricTemplate;
use App\Models\Murid\Rombel\Rombel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Murid extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'murid';

    protected $fillable = [
        'uuid',
        'nama',
        'nipd',
        'jk',
        'nisn',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'alamat',
        'rt',
        'rw',
        'kelurahan',
        'kecamatan',
        'kode_pos',
        'hp',
        'email',
        'nama_ayah',
        'nama_ibu',
        'nama_wali',
        'image_path',
        'rombel_id',
        'status',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date:Y-m-d',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }

            if (Schema::hasColumn($model->getTable(), 'status') && ! $model->status) {
                $model->status = StudentStatus::Aktif->value;
            }
        });
    }

    public function scopeAktif($query)
    {
        static $hasStatusColumn = null;

        $hasStatusColumn ??= Schema::hasColumn($this->getTable(), 'status');

        if ($hasStatusColumn) {
            return $query->where('status', StudentStatus::Aktif->value);
        }

        return $query->whereNotNull('rombel_id');
    }

    /**
     * Relasi ke Rombel
     */
    public function rombel()
    {
        return $this->belongsTo(Rombel::class)->withTrashed();
    }

    /**
     * Relasi ke status absen
     */
    public function statusAbsen()
    {
        return $this->hasMany(AbsenMurid::class)->withTrashed();
    }

    /**
     * Relasi ke data izin murid.
     */
    public function izinMurid()
    {
        return $this->hasMany(IzinMurid::class)->withTrashed();
    }

    public function biometricConsent()
    {
        return $this->hasOne(BiometricConsent::class);
    }

    public function biometricTemplate()
    {
        return $this->hasOne(BiometricTemplate::class)->withTrashed();
    }

    public function biometricEvaluations()
    {
        return $this->hasMany(BiometricEvaluation::class);
    }

    public function latestBiometricEvaluation()
    {
        return $this->hasOne(BiometricEvaluation::class)->latestOfMany('id');
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
