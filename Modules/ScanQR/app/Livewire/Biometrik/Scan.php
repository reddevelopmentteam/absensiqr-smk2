<?php

namespace Modules\ScanQR\Livewire\Biometrik;

use App\Models\Murid\AbsenMurid;
use App\Models\Murid\Murid;
use App\Services\Biometric\FaceIdentificationService;
use DomainException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
class Scan extends Component
{
    /** @var array<int, array<string, mixed>> */
    #[Locked]
    public array $candidates = [];

    public string $message = '';

    public string $messageType = 'info';

    public ?array $lastResult = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('scan-biometrik'), 403);
    }

    public function recognizeStudent(string $muridUuid, float $similarity): void
    {
        if (! is_finite($similarity) || $similarity <= 45 || $similarity > 100) {
            return;
        }

        $murid = Murid::query()
            ->aktif()
            ->where('uuid', $muridUuid)
            ->whereNotNull('image_path')
            ->with(['rombel.tingkat', 'rombel.jurusan', 'rombel.indeks'])
            ->first();

        if (! $murid) {
            return;
        }

        if (($this->candidates[0]['id'] ?? null) === $murid->id) {
            return;
        }

        $this->lastResult = null;
        $this->candidates = [[
            'id' => $murid->id,
            'uuid' => $murid->uuid,
            'nama' => $murid->nama,
            'nipd' => $murid->nipd,
            'nisn' => $murid->nisn,
            'rombel' => $murid->rombel?->nama_lengkap,
            'image_path' => $murid->image_path,
            'image_url' => $this->imageUrl($murid->image_path),
            'score' => round(max(0, min(100, $similarity)), 1),
        ]];
        $this->message = 'Wajah cocok di atas 45%. Absensi sedang dicatat otomatis.';
        $this->messageType = 'info';
        $this->recordRecognizedAttendance($murid);
    }

    public function clearRecognizedStudent(?string $muridUuid = null): void
    {
        if ($muridUuid !== null && ($this->candidates[0]['uuid'] ?? null) !== $muridUuid) {
            return;
        }

        $this->resetCandidates();
        $this->message = '';
        $this->messageType = 'info';
    }

    private function recordRecognizedAttendance(Murid $recognizedMurid): void
    {
        try {
            $murid = Murid::query()->aktif()->findOrFail($recognizedMurid->id);
            $result = app(FaceIdentificationService::class)->recordAttendance($murid, auth()->user());
            $scanResult = $result['scanResult'] ?? [];
            $modal = $scanResult['modal'] ?? [];
            $status = $scanResult['status'] ?? 'failed';
            $absen = $result['absen'] ?? null;

            $this->lastResult = [
                'type' => $modal['type'] ?? 'error',
                'status' => $status,
                'message' => $modal['message'] ?? null,
                'title' => $modal['title'] ?? null,
                'murid' => $murid,
                'murid_image_url' => $murid->image_path ? $this->imageUrl($murid->image_path) : null,
                'absen' => $absen instanceof AbsenMurid ? $absen : null,
            ];
            $this->message = (string) ($modal['message'] ?? 'Proses absensi selesai.');
            $this->messageType = in_array($status, ['success', 'late', 'already_recorded'], true) ? 'success' : 'error';

            if (in_array($status, ['success', 'late'], true)) {
                $this->dispatch('face-attendance-result', status: $status);
            }
        } catch (DomainException $error) {
            $this->message = $error->getMessage();
            $this->messageType = 'error';
        } catch (Throwable $error) {
            report($error);
            $this->message = 'Absensi otomatis gagal diproses. Gunakan QR atau hardware scanner.';
            $this->messageType = 'error';
        }
    }

    public function render()
    {
        return view('scanqr::livewire.biometrik.scan', [
            'students' => Murid::query()
                ->aktif()
                ->whereNotNull('image_path')
                ->orderBy('nama')
                ->get(['uuid', 'nama', 'image_path'])
                ->map(fn (Murid $murid): array => [
                    'uuid' => $murid->uuid,
                    'nama' => $murid->nama,
                    'imageUrl' => $this->imageUrl($murid->image_path),
                    'imageVersion' => hash('sha256', (string) $murid->image_path),
                ])
                ->all(),
        ]);
    }

    private function imageUrl(string $path): string
    {
        if (Str::startsWith($path, ['http://', 'https://'])) {
            $host = parse_url($path, PHP_URL_HOST);
            if (in_array($host, ['localhost', '127.0.0.1'], true)) {
                return url((string) parse_url($path, PHP_URL_PATH));
            }

            return $path;
        }

        if (Str::startsWith($path, '/storage/')) {
            return url($path);
        }

        if (Str::startsWith($path, 'storage/')) {
            return url('/'.$path);
        }

        if (Str::startsWith($path, 'public/storage/')) {
            return url('/storage/'.Str::after($path, 'public/storage/'));
        }

        if (Str::startsWith($path, 'public/')) {
            return asset(Str::after($path, 'public/'));
        }

        return Storage::disk('public')->url($path);
    }

    private function resetCandidates(): void
    {
        $this->candidates = [];
    }
}
