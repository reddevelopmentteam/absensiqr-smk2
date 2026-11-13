<?php

use Illuminate\Support\Facades\Route;
use Modules\ScanQR\Services\JadwalAbsensiService;
use Modules\ScanQR\Livewire\Biometrik\Scan as ScanBiometrik;
use Modules\ScanQR\Livewire\Hardware\Scan as ScanHardware;
use Modules\ScanQR\Livewire\Murid\Scan as ScanQRCode;

//  scan qr code
Route::get('/scan-qrcode', ScanQRCode::class)->middleware('access:scan-qrcode')->name('scan-qrcode');

//  scan hardware
Route::get('/scan-hardware', ScanHardware::class)->middleware('access:scan-hardware')->name('scan-hardware');

//  scan biometrik (pengenalan wajah langsung)
Route::get('/scan-biometrik', ScanBiometrik::class)->middleware('access:scan-biometrik')->name('scan-biometrik');

Route::get('/scan-camera-status', static function (JadwalAbsensiService $schedule) {
    $user = auth()->user();
    abort_unless($user && ($user->canAccess('scan-qrcode') || $user->canAccess('scan-biometrik')), 403);

    return response()->json(
        ['open' => $schedule->isScanWindowOpen()],
        200,
        ['Cache-Control' => 'no-store, private'],
    );
})->middleware(['auth', 'throttle:scan-camera-status'])->name('scan-camera-status');
