<?php

namespace Modules\DataMaster\Livewire\DataMurid\Components\Modal;

use App\Models\Murid\Murid;
use Livewire\Component;

class PilihQr extends Component
{
    public ?Murid $murid = null;

    public ?string $pendingOrientation = null;

    public function loadMurid($muridId): void
    {
        $this->murid = Murid::query()->with(['rombel.jurusan'])->findOrFail($muridId);
    }

    /** @return array<int, array{key: string, nama: string, deskripsi: string}> */
    public function cardModels(): array
    {
        return [
            ['key' => 'standar', 'nama' => 'Model Standar', 'deskripsi' => 'Desain bawaan sekolah'],
        ];
    }

    public function pilihOrientasi(string $orientasi): void
    {
        if (! in_array($orientasi, ['vertical', 'horizontal'], true)) {
            return;
        }

        $this->pendingOrientation = $orientasi;

        $this->dispatch('close-download-modal');
        $this->dispatch('open-model-modal');
    }

    public function unduhDenganModel(string $model): void
    {
        if (! $this->murid || ! $this->pendingOrientation) {
            return;
        }

        if (! in_array($model, array_column($this->cardModels(), 'key'), true)) {
            return;
        }

        $this->dispatch('generateStudentCardSinglePdf',
            murid: $this->murid->load(['rombel.jurusan'])->toArray(),
            orientation: $this->pendingOrientation,
            model: $model,
            filename: null,
        );

        $this->dispatch('close-model-modal');
        $this->pendingOrientation = null;
    }

    public function kembaliKeOrientasi(): void
    {
        $this->pendingOrientation = null;

        $this->dispatch('close-model-modal');
        $this->dispatch('open-download-modal');
    }

    public function render()
    {
        return view('datamaster::livewire.data-murid.components.modal.pilih-qr');
    }
}
