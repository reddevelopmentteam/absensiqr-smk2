<?php

namespace Modules\ScanQR\Livewire\Murid;

use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Modules\ScanQR\Services\AttendanceProcessingService;

class Scan extends Component
{
    #[Locked]
    public ?array $scanResult = null;

    public bool $tersimpan = false;

    public ?string $scanStatus = null;

    #[On('verifiedQRCode')]
    #[Renderless]
    public function verifiedQRCode($payload): void
    {
        if (! is_string($payload)) {
            $payload = '';
        }

        $result = app(AttendanceProcessingService::class)->process($payload);
        $this->scanResult = $result['scanResult'] ?? null;
        $this->syncCompatibilityState();

        // Publish only the final outcome, after the transaction commits.
        $this->dispatch('scanResult', result: $this->scanResult);
    }

    #[Renderless]
    public function closeModal(?string $resultId = null): void
    {
        if ($resultId !== null && $this->scanResult && ($this->scanResult['id'] ?? null) !== $resultId) {
            return;
        }

        $this->scanResult = null;
        $this->syncCompatibilityState();

        $this->dispatch('scanModalClosed');
    }

    #[Layout('layouts.auth')]
    public function render()
    {
        Carbon::setLocale('id');
        $dateNow = Carbon::now()->translatedFormat('d F Y, l');

        return view('scanqr::livewire.murid.scan', [
            'dateNow' => $dateNow,
        ]);
    }

    private function syncCompatibilityState(): void
    {
        $type = $this->scanResult['modal']['type'] ?? null;

        $this->tersimpan = $type === 'success';
        $this->scanStatus = match ($type) {
            'success' => 'success',
            'message' => 'message',
            'error' => 'error',
            default => null,
        };
    }
}

