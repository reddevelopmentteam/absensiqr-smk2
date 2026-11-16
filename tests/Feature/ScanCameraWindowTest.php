<?php

namespace Tests\Feature;

use App\Models\Murid\Rombel\Rombel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ScanQR\Services\JadwalAbsensiService;
use Tests\TestCase;

class ScanCameraWindowTest extends TestCase
{
    use RefreshDatabase;

    public function test_camera_opens_when_check_in_starts_and_closes_after_check_out_ends(): void
    {
        Rombel::create([]);
        $service = app(JadwalAbsensiService::class);

        $this->assertFalse($service->isScanWindowOpen(Carbon::parse('2026-09-16 05:59:00')));
        $this->assertTrue($service->isScanWindowOpen(Carbon::parse('2026-09-16 06:00:00')));

        // Jeda antara jendela scan masuk dan scan pulang tetap membuka kamera.
        $this->assertTrue($service->isScanWindowOpen(Carbon::parse('2026-09-16 12:00:00')));

        $this->assertTrue($service->isScanWindowOpen(Carbon::parse('2026-09-16 17:59:59')));
        $this->assertFalse($service->isScanWindowOpen(Carbon::parse('2026-09-16 18:01:00')));
    }

    public function test_camera_uses_the_friday_check_out_end(): void
    {
        Rombel::create([]);
        $service = app(JadwalAbsensiService::class);

        $this->assertTrue($service->isScanWindowOpen(Carbon::parse('2026-09-18 12:00:00')));
        $this->assertTrue($service->isScanWindowOpen(Carbon::parse('2026-09-18 14:30:00')));
        $this->assertFalse($service->isScanWindowOpen(Carbon::parse('2026-09-18 14:31:00')));
    }

    public function test_camera_stays_closed_on_weekends(): void
    {
        Rombel::create([]);
        $service = app(JadwalAbsensiService::class);

        $this->assertFalse($service->isScanWindowOpen(Carbon::parse('2026-09-19 12:00:00')));
    }
}
