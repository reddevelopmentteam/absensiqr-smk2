<?php

use Illuminate\Support\Facades\Route;
use Modules\ScanQR\Livewire\Hardware\Scan as ScanHardware;
use Modules\ScanQR\Livewire\Murid\Scan as ScanQRCode;

//  scan qr code
Route::get('/scan-qrcode', ScanQRCode::class)->middleware('access:scan-qrcode')->name('scan-qrcode');

//  scan hardware
Route::get('/scan-hardware', ScanHardware::class)->middleware('access:scan-hardware')->name('scan-hardware');
