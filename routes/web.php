<?php

use App\Livewire\Auth\Login;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware('guest')->group(function () {
    Route::get('/', Login::class)->name('login-page');
});

// Endpoint ini hanya tersedia untuk sesi login dan hanya mengirim UUID pada header.
Route::match(['HEAD'], '/browser-refresh-version', function () {
    return response()
        ->noContent()
        ->header('X-Browser-Refresh-Version', Setting::valueOf('system.browser_refresh_version', ''))
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
})->middleware(['auth', 'throttle:browser-refresh-version'])->name('browser-refresh-version');

Route::get('/mpanel', function () {
    return to_route(request()->user()->defaultRouteName());
})->middleware('auth')->name('home');

Route::prefix('mpanel')->middleware('auth')->group(function () {

    Route::get('/card-template/{orientation}', function (Request $request, string $orientation) {
        $templateFiles = [
            'horizontal' => 'card/kartu-pelajar-horizontal.html',
            'vertical' => 'card/kartu-pelajar-vertical.html',
            'horizontal-belakang' => 'card/kartu-pelajar-belakang-horizontal.html',
            'vertical-belakang' => 'card/kartu-pelajar-belakang-vertical.html',
        ];

        if (! array_key_exists($orientation, $templateFiles)) {
            abort(404);
        }

        $fileName = $templateFiles[$orientation];

        $html = Storage::disk('local')->get($fileName);

        static $motifDataUrl;
        $motifDataUrl ??= 'data:image/png;base64,'.base64_encode(
            file_get_contents(public_path('assets/img/motif-kartu.png'))
        );
        $html = str_replace('__MOTIF_DATA__', $motifDataUrl, $html);

        // ETag dari isi respons final (termasuk motif) agar revisi template
        // langsung ketahuan browser: fetch mengirim If-None-Match dan server
        // menjawab 304 bila belum berubah, 200 bila sudah berubah.
        $etag = '"'.sha1($html).'"';

        if (trim($request->header('If-None-Match', '')) === $etag) {
            return response('', 304, ['ETag' => $etag]);
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'ETag' => $etag,
            // Selalu revalidasi; template kecil sehingga 304/200 murah
            // dibanding risiko kartu lama nyangkut di cache seminggu.
            'Cache-Control' => 'private, no-cache, must-revalidate',
        ]);
    })->name('card-template');
});
