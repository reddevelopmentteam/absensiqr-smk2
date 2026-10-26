<template data-scan-modal-template="success">
    <div class="fixed inset-0 z-50" id="success-modal">
        <div data-close-scan class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm"></div>
        <div
            class="pointer-events-none relative flex min-h-dvh items-center justify-center overflow-y-auto p-4 py-8 sm:p-6">
            <div class="pointer-events-auto w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl sm:max-w-xl">
                <!-- header -->
                <div
                    class="flex items-center gap-3 bg-gradient-to-r from-blue-deep to-blue-deep-solid px-5 py-4 text-white sm:px-6">
                    <img src="{{ asset('assets/img/logo_smkn_2.png') }}" class="h-9 w-9 shrink-0 sm:h-10 sm:w-10"
                        alt="">
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-base font-bold leading-tight sm:text-lg">Absensi Murid</h2>
                        <p class="text-[11px] uppercase tracking-wider opacity-80 sm:text-xs">SMKN 2 Banjarmasin</p>
                    </div>
                    <p class="hidden shrink-0 text-right text-[11px] opacity-70 sm:block">ID: <span
                            data-scan-field="uuid">-</span></p>
                </div>

                <!-- status notice -->
                <div class="px-4 pt-4 sm:px-6 sm:pt-5">
                    <div data-scan-success-notice
                        class="rounded-2xl border border-green-200 bg-green-50 px-4 py-4 text-green-800">
                        <div class="flex items-center justify-center gap-2">
                            <iconify-icon icon="solar:check-circle-bold" width="28" height="28"
                                aria-hidden="true" class="shrink-0 text-green-600"></iconify-icon>
                            <p class="text-center text-xl font-extrabold leading-tight sm:text-2xl"
                                data-scan-field="message">Absensi berhasil disimpan.</p>
                        </div>
                        <p data-scan-late-message
                            class="mt-3 hidden text-center text-xl font-extrabold leading-tight text-amber-700 sm:text-2xl">
                            Anda terlambat, silahkan lapor ke pengawas
                        </p>
                        <p class="mt-2 hidden text-center text-xs" data-scan-schedule>Jadwal: <span
                                data-scan-field="jadwal-label">-</span><span data-scan-schedule-time></span></p>
                    </div>
                </div>

                <!-- murid detail: avatar top on mobile, side-by-side on sm+.
                     JS reveals it with classList.remove('hidden'); flex direction
                     comes from CSS so there is no hidden-vs-flex specificity fight. -->
                <div data-scan-murid class="scan-murid-detail hidden gap-4 p-4 sm:p-6">
                    <div class="relative flex h-28 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border bg-blue-100 text-3xl font-bold text-blue-main shadow sm:h-40 sm:w-32 sm:text-4xl"
                        role="img" data-scan-avatar>
                        <span data-scan-avatar-initial></span><img
                            class="absolute inset-0 hidden h-full w-full object-cover" alt=""
                            data-scan-avatar-image>
                    </div>
                    <dl class="grid w-full flex-1 grid-cols-2 gap-x-4 gap-y-2.5 text-sm">
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide text-gray-500">Nama</dt>
                            <dd class="truncate font-semibold text-gray-900" data-scan-field="nama">-</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide text-gray-500">Kelas</dt>
                            <dd class="font-semibold text-gray-900" data-scan-field="rombel">-</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide text-gray-500">NISN</dt>
                            <dd class="font-semibold text-gray-900" data-scan-field="nisn">-</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide text-gray-500">NIPD</dt>
                            <dd class="font-semibold text-gray-900" data-scan-field="nipd">-</dd>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <dt class="text-[11px] uppercase tracking-wide text-gray-500">Tempat, Tgl Lahir</dt>
                            <dd class="font-semibold text-gray-900" data-scan-field="lahir">-</dd>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <dt class="text-[11px] uppercase tracking-wide text-gray-500">Jenis Kelamin</dt>
                            <dd class="font-semibold text-gray-900" data-scan-field="jenisKelamin">-</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide text-gray-500">Agama</dt>
                            <dd class="font-semibold text-gray-900" data-scan-field="agama">-</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] uppercase tracking-wide text-gray-500">No HP</dt>
                            <dd class="truncate font-semibold text-gray-900" data-scan-field="hp">-</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-[11px] uppercase tracking-wide text-gray-500">Alamat</dt>
                            <dd class="font-semibold leading-snug text-gray-900" data-scan-field="alamat">-</dd>
                        </div>
                    </dl>
                </div>

                <!-- footer -->
                <div
                    class="flex items-center justify-between bg-gray-100 px-5 py-2.5 text-[11px] text-gray-500 sm:px-6 sm:text-xs">
                    <p>Dicetak oleh sistem</p>
                    <p data-scan-date></p>
                </div>
            </div>
        </div>
    </div>
</template>
