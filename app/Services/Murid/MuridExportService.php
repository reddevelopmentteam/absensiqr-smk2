<?php

namespace App\Services\Murid;

use App\Enums\StudentStatus;
use App\Models\Murid\Murid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * Membangun query data murid untuk kebutuhan export Excel.
 *
 * Semua filter opsional dan hasilnya selalu dibatasi oleh daftar rombel
 * yang boleh diakses (mis. wali kelas hanya boleh mengekspor kelasnya).
 */
class MuridExportService
{
    /**
     * @param  array{search?: ?string, rombel_id?: ?int, status?: ?string}  $filters
     * @param  array<int, int>|null  $allowedRombelIds  null = tanpa batasan
     */
    public function query(array $filters = [], ?array $allowedRombelIds = null): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $rombelId = $filters['rombel_id'] ?? null;
        $status = $filters['status'] ?? null;

        return Murid::query()
            ->with(['rombel:id,tingkat_id,jurusan_id,indeks_id', 'rombel.tingkat:id,nama', 'rombel.jurusan:id,nama', 'rombel.indeks:id,nama'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('nama', 'like', '%'.$search.'%')
                        ->orWhere('nipd', $search)
                        ->orWhere('nisn', $search);
                });
            })
            ->when($allowedRombelIds !== null, function (Builder $query) use ($allowedRombelIds): void {
                $query->whereIn('rombel_id', $allowedRombelIds);
            })
            ->when($rombelId, fn (Builder $query) => $query->where('rombel_id', $rombelId))
            ->when($status === 'aktif', fn (Builder $query) => $this->applyActiveFilter($query))
            ->when($status === 'nonaktif', fn (Builder $query) => $this->applyInactiveFilter($query))
            ->orderBy('nama')
            ->orderBy('id');
    }

    /**
     * Jumlah baris yang akan diekspor, dipakai untuk preview pada modal.
     *
     * @param  array{search?: ?string, rombel_id?: ?int, status?: ?string}  $filters
     * @param  array<int, int>|null  $allowedRombelIds
     */
    public function count(array $filters = [], ?array $allowedRombelIds = null): int
    {
        return $this->query($filters, $allowedRombelIds)->count();
    }

    private function applyActiveFilter(Builder $query): Builder
    {
        if (! Schema::hasColumn($query->getModel()->getTable(), 'status')) {
            return $query->whereNotNull('rombel_id');
        }

        return $query->where('status', StudentStatus::Aktif->value);
    }

    private function applyInactiveFilter(Builder $query): Builder
    {
        if (! Schema::hasColumn($query->getModel()->getTable(), 'status')) {
            return $query->whereNull('rombel_id');
        }

        return $query->where(fn (Builder $inner) => $inner->whereNull('status')
            ->orWhere('status', '!=', StudentStatus::Aktif->value));
    }
}
