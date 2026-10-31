<?php

namespace Modules\Manajemen\Livewire\GenerateQR;

use App\Models\Murid\Murid;
use App\Services\Rombel\RombelFilterService;
use Livewire\Component;
use Livewire\WithFileUploads;

class Generate extends Component
{
    use WithFileUploads;

    public $listRombel;
    public $filteredJurusan;
    public $filteredIndeks;

    public ?int $filterIndeks = null;
    public ?int $filterTingkat = null;
    public ?int $filterJurusan = null;
    public ?int $activeFilterIndeks = null;
    public ?int $activeFilterTingkat = null;
    public ?int $activeFilterJurusan = null;
    public int $totalData = 0;
    public int $lastId = 0;
    public bool $isGenerating = false;
    public string $orientation = 'horizontal';
    public int $runId = 0;
    public ?string $pendingOrientation = null;
    public string $cardModel = 'standar';

    public function mount(): void
    {
        $this->listRombel = $this->getRombel();
        $this->refreshFilterOptions();
    }

    private function rombelFilterService(): RombelFilterService
    {
        return app(RombelFilterService::class);
    }

    private function getRombel()
    {
        return $this->rombelFilterService()->getRombelList(
            $this->filterTingkat,
            $this->filterJurusan,
            $this->filterIndeks,
            null,
        );
    }

    private function getAvailableJurusan()
    {
        return $this->rombelFilterService()->getAvailableJurusan(
            $this->filterTingkat,
            $this->filterJurusan,
            $this->filterIndeks,
            null,
        );
    }

    private function getAvailableIndeks()
    {
        return $this->rombelFilterService()->getAvailableIndeks(
            $this->filterTingkat,
            $this->filterJurusan,
            $this->filterIndeks,
            null,
        );
    }

    private function loadChunk()
    {
        $hasFilter = $this->isGenerating
            ? ($this->activeFilterTingkat || $this->activeFilterJurusan || $this->activeFilterIndeks)
            : ($this->filterTingkat || $this->filterJurusan || $this->filterIndeks);

        $query = Murid::query()
            ->aktif()
            ->with([
                'rombel:id,tingkat_id,jurusan_id,indeks_id',
            ])
            ->tap(fn ($q) => $this->rombelFilterService()->applyRombelFiltersToRelation(
                $q,
                'rombel',
                $this->isGenerating ? $this->activeFilterTingkat : $this->filterTingkat,
                $this->isGenerating ? $this->activeFilterJurusan : $this->filterJurusan,
                $this->isGenerating ? $this->activeFilterIndeks : $this->filterIndeks,
                null,
            ));

        $this->totalData = $this->totalData?: (clone $query)->count();

        $data = $query
            ->where('id', '>', $this->lastId)
            ->orderBy('id')
            ->take(1000)
            ->get(['id', 'uuid', 'nama', 'nisn', 'tanggal_lahir', 'image_path' ,'rombel_id']);

            if ($data->isNotEmpty()) {
                $this->lastId = $data->last()->id;
            }

        return $data;
    }

    private function refreshFilterOptions(): void
    {
        $this->filteredJurusan = $this->getAvailableJurusan();
        $this->filteredIndeks = $this->getAvailableIndeks();
    }
    public function updatedFilterTingkat(): void
    {
        $this->refreshFilterOptions();
    }

    public function updatedFilterJurusan(): void
    {
        $this->refreshFilterOptions();
    }

    public function updatedFilterIndeks(): void
    {
        $this->refreshFilterOptions();
    }

    /** @return array<int, array{key: string, nama: string, deskripsi: string, tag: string, accent: string}> */
    public function cardModels(): array
    {
        return [
            ['key' => 'standar', 'nama' => 'Model Standar', 'deskripsi' => 'Desain modern', 'tag' => 'Modern', 'accent' => '#0a7a1e'],
            ['key' => 'baru', 'nama' => 'Model Baru', 'deskripsi' => 'Desain hijau SMKN 2 Banjarmasin', 'tag' => 'Classic', 'accent' => '#85983c'],
        ];
    }

    public function pilihModelUntukGenerate(string $orientasi): void
    {
        if ($this->isGenerating) {
            return;
        }

        if (! in_array($orientasi, ['vertical', 'horizontal'], true)) {
            return;
        }

        $this->pendingOrientation = $orientasi;
        $this->dispatch('open-generate-model-modal');
    }

    public function batalPilihModel(): void
    {
        $this->pendingOrientation = null;
        $this->dispatch('close-generate-model-modal');
    }

    public function startGenerate($orientation = 'horizontal', $model = 'standar')
    {
        if($this->isGenerating) {
            return;
        }

        if (! in_array($orientation, ['vertical', 'horizontal'], true)) {
            return;
        }

        if (! in_array($model, array_column($this->cardModels(), 'key'), true)) {
            return;
        }

        $this->isGenerating = true;
        $this->orientation = $orientation;
        $this->cardModel = $model;
        $this->runId++;
        $this->activeFilterTingkat = $this->filterTingkat;
        $this->activeFilterJurusan = $this->filterJurusan;
        $this->activeFilterIndeks = $this->filterIndeks;

        $this->lastId = 0;
        $this->totalData = 0;
        $chunkData = $this->loadChunk();

        $this->dispatch(
            'generate-qr',
            runId: $this->runId,
            dataMurid: $chunkData->toArray(),
            totalData: $this->totalData,
            orientation: $orientation,
            model: $model
        );

        $this->dispatch('close-generate-model-modal');
        $this->pendingOrientation = null;
    }


    public function nextChunk($orientation = 'horizontal', $model = 'standar')
    {
        $effectiveModel = in_array($model, array_column($this->cardModels(), 'key'), true)
            ? $model
            : $this->cardModel;

        $chunkData = $this->loadChunk();

        if (!$chunkData->isNotEmpty()) {
            $this->lastId = 0;
            $this->totalData = 0;
            $this->isGenerating = false;
            $this->orientation = 'horizontal';
            $this->cardModel = 'standar';
            $this->activeFilterTingkat = null;
            $this->activeFilterJurusan = null;
            $this->activeFilterIndeks = null;
            return;
        }

        $this->dispatch(
            'generate-qr',
            runId: $this->runId,
            dataMurid: $chunkData->toArray(),
            totalData: $this->totalData,
            orientation: $orientation,
            model: $effectiveModel
        );
    }

    public function render()
    {
        return view('manajemen::livewire.generate-qr.generate');
    }
}
