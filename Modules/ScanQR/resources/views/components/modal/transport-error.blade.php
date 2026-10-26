<template data-scan-modal-template="transport">
    <div class="fixed inset-0 z-50">
        <div data-close-scan class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm"></div>
        <div
            class="pointer-events-none relative flex min-h-dvh items-center justify-center overflow-y-auto p-4 py-8 sm:p-6">
            <div class="pointer-events-auto w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
                <!-- header -->
                <div
                    class="flex items-center gap-3 bg-gradient-to-r from-red-700 to-red-600 px-5 py-4 text-white sm:px-6">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/15">
                        <iconify-icon icon="solar:wi-fi-router-minimalistic-bold" width="26" height="26"
                            aria-hidden="true"></iconify-icon>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-base font-bold leading-tight sm:text-lg">Absensi Tidak Disimpan</h2>
                        <p class="truncate text-[11px] opacity-80 sm:text-xs">Gangguan koneksi ke server</p>
                    </div>
                </div>

                <!-- body -->
                <div class="space-y-3 p-5 text-center sm:p-6">
                    <p class="text-sm leading-relaxed text-gray-700">Server tidak dapat memproses hasil scan. Silakan
                        coba kembali.</p>
                    <p
                        class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-red-500"></span>
                        Menunggu koneksi pulih otomatis
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
