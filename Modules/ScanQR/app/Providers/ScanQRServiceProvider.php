<?php

namespace Modules\ScanQR\Providers;

use App\Support\ModuleLivewireRegistrar;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ScanQRServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'ScanQR';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'scanqr';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        RateLimiter::for('scan-qrcode-ping', static function (Request $request) {
            return Limit::perMinute(120)->by('scan-ping:'.$request->session()->getId());
        });

        RateLimiter::for('scan-camera-status', static function (Request $request) {
            return Limit::perMinute(120)->by('scan-camera-status:'.$request->user()?->getAuthIdentifier());
        });

        ModuleLivewireRegistrar::register($this->name);
    }

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
