<?php

use Illuminate\Support\Facades\Route;
use Modules\Manajemen\Livewire\GenerateQR\Generate as GenerateQR;
use Modules\Manajemen\Livewire\MuridFoto\Create as TambahFoto;
use Modules\Manajemen\Livewire\MuridFoto\Edit as EditFoto;
use Modules\Manajemen\Livewire\MuridFoto\Manajemen as ManajemenFoto;
use Modules\Manajemen\Livewire\Role\Manajemen as IndexRole;
use Modules\Manajemen\Livewire\TahunAjaran\Manajemen as TahunAjaran;
use Modules\Manajemen\Livewire\User\Create as CreateUser;
use Modules\Manajemen\Livewire\User\Edit as EditUser;
use Modules\Manajemen\Livewire\User\Manajemen as IndexUser;
use Modules\Manajemen\Livewire\Waktu\Manajemen as ManajemenWaktu;

Route::prefix('/manajemen')->group(function () {
    // manajemen waktu
    Route::prefix('/waktu')->middleware('access:manajemen-waktu')->group(function () {
        Route::get('/', ManajemenWaktu::class)->name('manajemen-waktu');
    });

    // manajemen murid
    Route::prefix('/murid')->middleware('access:manajemen-murid')->group(function () {
        Route::get('/', ManajemenFoto::class)->name('manajemen-foto-murid');
        Route::get('/create', TambahFoto::class)->name('tambah-foto');
        Route::get('/edit', EditFoto::class)->name('edit-foto');
    });

    // generate
    Route::get('/generate-qr', GenerateQR::class)->middleware('access:generate-qr')->name('generate-QR');

    // role
    Route::get('/role', IndexRole::class)->middleware('access:manajemen-role')->name('manajemen-role');

    Route::middleware('access:manajemen-lainnya')->group(function () {
        // tahun ajaran
        Route::get('/tahun-ajaran', TahunAjaran::class)->name('manajemen-tahun-ajaran');

        // manajemen user group
        Route::prefix('/user')->group(function () {
            Route::get('/', IndexUser::class)->name('manajemen-user');
            Route::get('/create', CreateUser::class)->name('tambah-user');
            Route::get('/edit/{userId}', EditUser::class)->name('edit-user');
        });
    });
});
