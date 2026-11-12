<?php

namespace Modules\ScanQR\Livewire\Hardware;

use App\Models\Murid\AbsenMurid;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\ScanQR\Services\AttendanceProcessingService;

#[Layout('layouts.app')]
class Scan extends Component
{
    use WithPagination;

    public string $scanInput = '';

    public string $scannerStatus = 'Siap menerima input dari scanner.';

    public string $scannerStatusType = 'info';

    public ?array $lastResult = null;

    public string $search = '';

    public string $filterStatus = '';

    public int $perPage = 10;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('scan-hardware'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function submitScan(): void
    {
        $payload = trim($this->scanInput);
        $this->scanInput = '';

        if ($payload === '') {
            $this->scannerStatus = 'Input scanner kosong. Silakan lakukan scan ulang.';
            $this->scannerStatusType = 'error';

            return;
        }

        $result = app(AttendanceProcessingService::class)->process($payload);
        $scanResult = $result['scanResult'] ?? [];
        $modal = $scanResult['modal'] ?? [];
        $status = $scanResult['status'] ?? 'failed';
        $type = $modal['type'] ?? 'error';

        $this->scannerStatus = (string) ($modal['message'] ?? 'Hasil scan tidak tersedia.');
        $this->scannerStatusType = $this->resolveStatusType($type, $status);

        $murid = $result['murid'] ?? null;
        $absen = $result['absen'] ?? null;
        $tanggal = $absen?->tanggal?->format('d M Y') ?? now()->format('d M Y');
        $waktu = $absen?->waktu_keluar ?? $absen?->waktu_masuk ?? now()->format('H:i:s');

        $this->lastResult = [
            'type' => $type,
            'status' => $status,
            'message' => $modal['message'] ?? null,
            'title' => $modal['title'] ?? null,
            'murid' => $murid ? [
                'nama' => $murid->nama,
                'nisn' => $murid->nisn,
                'nipd' => $murid->nipd,
                'rombel' => $murid->rombel?->nama_lengkap,
                'imagePath' => $murid->image_path,
            ] : ($modal['murid'] ?? null),
            'absen' => $absen ? [
                'tanggal' => $tanggal,
                'waktu' => $waktu,
                'waktu_masuk' => $absen->waktu_masuk,
                'waktu_keluar' => $absen->waktu_keluar,
                'status' => $absen->status,
                'jenis' => $this->attendanceTypeLabel($absen->waktu_masuk, $absen->waktu_keluar),
            ] : null,
        ];

        $this->resetPage();
    }

    public function clearStatus(): void
    {
        $this->scannerStatus = 'Siap menerima input dari scanner.';
        $this->scannerStatusType = 'info';
        $this->lastResult = null;
    }

    public function render()
    {
        $history = AbsenMurid::query()
            ->with([
                'murid:id,nama,nipd,nisn,rombel_id,image_path',
                'murid.rombel:id,tingkat_id,jurusan_id,indeks_id',
                'murid.rombel.tingkat:id,nama',
                'murid.rombel.jurusan:id,nama',
                'murid.rombel.indeks:id,nama',
            ])
            ->whereDate('tanggal', today())
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);

                $query->whereHas('murid', function ($muridQuery) use ($search) {
                    $muridQuery->where('nama', 'like', "%{$search}%")
                        ->orWhere('nipd', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%");
                });
            })
            ->when($this->filterStatus !== '', fn ($query) => $query->where('status', $this->filterStatus))
            ->orderByDesc('updated_at')
            ->paginate($this->perPage);

        return view('scanqr::livewire.hardware.scan', [
            'history' => $history,
            'statusOptions' => ['Hadir', 'Masuk', 'Izin', 'Sakit', 'Alpa', 'Terlambat'],
        ]);
    }

    private function resolveStatusType(string $type, string $status): string
    {
        if ($type === 'error' || in_array($status, ['failed', 'attendance_not_open'], true)) {
            return 'error';
        }

        if ($type === 'success') {
            return 'success';
        }

        return 'info';
    }

    private function attendanceTypeLabel($waktuMasuk, $waktuKeluar): string
    {
        if ($waktuMasuk && $waktuKeluar) {
            return 'Masuk & Pulang';
        }

        if ($waktuKeluar) {
            return 'Pulang';
        }

        if ($waktuMasuk) {
            return 'Masuk';
        }

        return '-';
    }
}
