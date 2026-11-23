<?php

namespace Modules\DataMaster\Livewire\DataMurid\Components\Modal;

use App\Exports\Murid\MuridExport;
use App\Models\Guru\Guru;
use App\Models\Murid\Rombel\Rombel;
use App\Services\Murid\MuridExportService;
use App\Services\Rombel\RombelFilterService;
use Illuminate\Support\Str;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class Export extends Component
{
    public bool $showModal = false;

    public ?int $filterRombelId = null;

    /** aktif | nonaktif | semua */
    public string $filterStatus = 'aktif';

    public bool $includePhoto = false;

    public string $search = '';

    public int $totalMurid = 0;

    public array $listRombel = [];

    public bool $isWaliKelas = false;

    /** @var array<int, int>|null */
    public ?array $waliRombelIds = null;

    public function mount(): void
    {
        $this->applyWaliKelasLock();
        $this->listRombel = $this->getRombel()->toArray();
        $this->refreshTotal();
    }

    public function openModal(): void
    {
        $this->showModal = true;
        $this->refreshTotal();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function updatedFilterRombelId(): void
    {
        $this->refreshTotal();
    }

    public function updatedFilterStatus(): void
    {
        $this->refreshTotal();
    }

    public function updatedSearch(): void
    {
        $this->refreshTotal();
    }

    public function resetFilter(): void
    {
        $this->filterRombelId = $this->isWaliKelas ? ($this->waliRombelIds[0] ?? null) : null;
        $this->filterStatus = 'aktif';
        $this->search = '';
        $this->includePhoto = false;

        $this->refreshTotal();
    }

    public function export()
    {
        $this->validate([
            'filterRombelId' => ['nullable', 'integer', 'exists:rombel,id'],
            'filterStatus' => ['required', 'in:aktif,nonaktif,semua'],
            'search' => ['nullable', 'string', 'max:191'],
            'includePhoto' => ['boolean'],
        ]);

        $this->applyWaliKelasLock();
        $this->guardRombelAccess();

        $query = app(MuridExportService::class)->query($this->filters(), $this->waliRombelIds);

        $filename = sprintf(
            'data-murid-%s-%s.xlsx',
            $this->rombelSlug(),
            now()->format('Ymd-His'),
        );

        return Excel::download(new MuridExport($query, $this->includePhoto), $filename);
    }

    public function render()
    {
        return view('datamaster::livewire.data-murid.components.modal.export');
    }

    /**
     * @return array{search?: ?string, rombel_id?: ?int, status?: ?string}
     */
    private function filters(): array
    {
        return [
            'search' => $this->search,
            'rombel_id' => $this->filterRombelId,
            'status' => $this->filterStatus,
        ];
    }

    private function refreshTotal(): void
    {
        $this->totalMurid = app(MuridExportService::class)->count($this->filters(), $this->waliRombelIds);
    }

    private function rombelSlug(): string
    {
        if (! $this->filterRombelId) {
            return 'semua-kelas';
        }

        $rombel = Rombel::query()->find($this->filterRombelId);
        $name = $rombel?->nama_lengkap ?: 'kelas-'.$this->filterRombelId;

        $slug = Str::slug($name);

        return $slug !== '' ? $slug : 'kelas-'.$this->filterRombelId;
    }

    private function guardRombelAccess(): void
    {
        if ($this->waliRombelIds === null) {
            return;
        }

        if ($this->filterRombelId === null || ! in_array($this->filterRombelId, $this->waliRombelIds, true)) {
            $this->filterRombelId = $this->waliRombelIds[0] ?? null;
        }
    }

    private function getRombel()
    {
        return app(RombelFilterService::class)->getRombelList(
            null,
            null,
            null,
            $this->waliRombelIds,
        );
    }

    private function applyWaliKelasLock(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $this->isWaliKelas = $user->hasOnlyRoles(['Wali Kelas', 'Wali Murid']);

        if (! $this->isWaliKelas) {
            return;
        }

        $guru = Guru::query()->where('user_id', $user->id)->first();

        if (! $guru) {
            $this->waliRombelIds = [];

            return;
        }

        $this->waliRombelIds = Rombel::query()
            ->where('wali_guru_id', $guru->id)
            ->pluck('id')
            ->all();

        if ($this->filterRombelId === null && count($this->waliRombelIds) === 1) {
            $this->filterRombelId = $this->waliRombelIds[0];
        }
    }
}
