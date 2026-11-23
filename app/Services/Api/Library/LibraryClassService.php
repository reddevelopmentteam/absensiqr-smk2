<?php

namespace App\Services\Api\Library;

use App\Models\Murid\Rombel\Rombel;
use Illuminate\Support\Collection;

class LibraryClassService
{
    /**
     * Daftar kelas/rombel beserta nama lengkapnya.
     *
     * @return Collection<int, Rombel>
     */
    public function index(): Collection
    {
        return Rombel::query()
            ->with(['tingkat', 'jurusan', 'indeks'])
            ->get()
            ->sortBy(fn (Rombel $rombel) => [
                $rombel->tingkat?->nama ?? '',
                $rombel->jurusan?->nama ?? '',
                $rombel->indeks?->nama ?? '',
            ])
            ->values();
    }
}
