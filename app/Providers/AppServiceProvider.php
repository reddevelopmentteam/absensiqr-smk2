<?php

namespace App\Providers;

use App\Models\Murid\Murid;
use App\Models\Setting;
use App\Observers\MuridObserver;
use App\Services\Biometric\Contracts\FaceProcessingService;
use App\Services\Biometric\PythonFaceProcessingService;
use App\Services\Biometric\UnavailableFaceProcessingService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FaceProcessingService::class, function (): FaceProcessingService {
            return match ((string) config('biometric.face_processor.driver', 'python')) {
                'python' => new PythonFaceProcessingService,
                default => new UnavailableFaceProcessingService,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('browser-refresh-version', static function (Request $request) {
            return Limit::perMinute(120)->by('browser-refresh:'.$request->session()->getId());
        });

        if (\Schema::hasTable('setting')) {
            View::share('siteSettings', Setting::values());
        }

        Murid::observe(MuridObserver::class);
    }
}
