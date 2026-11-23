<?php

namespace App\Services\Api\Library;

use App\Models\Murid\Murid;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class LibraryStudentService
{
    public const MAX_PER_PAGE = 100;

    public const DEFAULT_PER_PAGE = 20;

    /**
     * Daftar siswa aktif dengan filter search, status, dan kelas.
     */
    public function index(Request $request): LengthAwarePaginator
    {
        $search = trim((string) $request->query('search', ''));
        $status = $this->normalizeStatus($request->query('status'));
        $classId = $request->query('class_id');

        return $this->baseQuery()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('murid.nama', 'like', '%'.$search.'%')
                        ->orWhere('murid.nipd', 'like', '%'.$search.'%')
                        ->orWhere('murid.nisn', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== null, function (Builder $query) use ($status): void {
                if ($status === 'active') {
                    $query->where('murid.status', 'aktif');
                } elseif ($status === 'inactive') {
                    $query->where('murid.status', '!=', 'aktif');
                } else {
                    $query->where('murid.status', $status);
                }
            })
            ->when($classId !== null && $classId !== '', function (Builder $query) use ($classId): void {
                $query->where('murid.rombel_id', (int) $classId);
            })
            ->orderBy('murid.nama')
            ->orderBy('murid.id')
            ->paginate($this->perPage($request))
            ->withQueryString();
    }

    /**
     * Perubahan data untuk sinkronisasi, termasuk soft delete.
     */
    public function changes(Request $request): LengthAwarePaginator
    {
        $since = Carbon::parse((string) $request->query('updated_since'));

        return Murid::query()
            ->withTrashed()
            ->select([
                'murid.id',
                'murid.uuid',
                'murid.nama',
                'murid.nipd',
                'murid.nisn',
                'murid.jk',
                'murid.tanggal_lahir',
                'murid.image_path',
                'murid.rombel_id',
                'murid.status',
                'murid.created_at',
                'murid.updated_at',
                'murid.deleted_at',
            ])
            ->with([
                'rombel' => fn ($query) => $query->with(['tingkat', 'jurusan', 'indeks']),
            ])
            ->where(function (Builder $query) use ($since): void {
                $query->where('murid.updated_at', '>=', $since)
                    ->orWhere('murid.deleted_at', '>=', $since);
            })
            ->orderBy('murid.updated_at')
            ->orderBy('murid.id')
            ->paginate($this->perPage($request))
            ->withQueryString();
    }

    public function findByUuid(string $uuid): ?Murid
    {
        return $this->baseQuery()
            ->where('murid.uuid', $uuid)
            ->first();
    }

    /**
     * NIS disimpan pada kolom nipd.
     */
    public function findByNis(string $nis): ?Murid
    {
        return $this->baseQuery()
            ->where('murid.nipd', $nis)
            ->first();
    }

    /**
     * QR absensi berisi UUID siswa, atau "uuid>izin_uuid" untuk kartu izin.
     * Hanya bagian UUID siswa yang dipakai untuk identifikasi perpustakaan.
     */
    public function extractUuidFromQrToken(string $token): ?string
    {
        $token = trim($token);

        if ($token === '') {
            return null;
        }

        if (str_contains($token, '>')) {
            [$token] = explode('>', $token, 2);
        }

        return Str::isUuid($token) ? $token : null;
    }

    public function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min(self::MAX_PER_PAGE, $perPage));
    }

    private function normalizeStatus(mixed $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        return match (strtolower(trim((string) $status))) {
            'active', 'aktif' => 'active',
            'inactive', 'nonaktif', 'tidak aktif' => 'inactive',
            default => strtolower(trim((string) $status)),
        };
    }

    private function baseQuery(): Builder
    {
        return Murid::query()
            ->select([
                'murid.id',
                'murid.uuid',
                'murid.nama',
                'murid.nipd',
                'murid.nisn',
                'murid.jk',
                'murid.tanggal_lahir',
                'murid.image_path',
                'murid.rombel_id',
                'murid.status',
                'murid.created_at',
                'murid.updated_at',
            ])
            ->with([
                'rombel' => fn ($query) => $query->with(['tingkat', 'jurusan', 'indeks']),
            ]);
    }
}
