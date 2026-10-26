<template data-scan-modal-template="error">
    <div class="fixed inset-0 z-50" id="error-modal">
        <div data-close-scan class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm"></div>
        <div
            class="pointer-events-none relative flex min-h-dvh items-center justify-center overflow-y-auto p-4 py-8 sm:p-6">
            <div class="pointer-events-auto w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
                <!-- header -->
                <div
                    class="flex items-center gap-3 bg-gradient-to-r from-red-700 to-red-600 px-5 py-4 text-white sm:px-6">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/15">
                        <iconify-icon icon="solar:close-circle-bold" width="26" height="26"
                            aria-hidden="true"></iconify-icon>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-base font-bold leading-tight sm:text-lg">Absensi Tidak Disimpan</h2>
                        <p class="truncate text-[11px] opacity-80 sm:text-xs">Sistem mengecek jadwal kelas dari
                            Manajemen Waktu.</p>
                    </div>
                </div>

                <!-- body -->
                <div class="space-y-4 p-5 text-sm text-gray-700 sm:p-6">
                    <p class="text-center text-xl font-extrabold leading-tight text-gray-900 sm:text-2xl"
                        data-scan-field="message"></p>
                    <div class="hidden rounded-2xl bg-gray-50 p-4" data-scan-murid>
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-lg font-bold text-blue-main"
                                data-scan-avatar-initial>-</span>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-gray-900" data-scan-field="nama">-</p>
                                <p class="text-xs text-gray-500">Kelas <span class="font-semibold text-gray-700"
                                        data-scan-field="rombel">-</span></p>
                            </div>
                        </div>
                    </div>
                    <div class="hidden rounded-2xl border border-red-100 bg-red-50 p-4 text-red-800" data-scan-jadwal>
                        <p class="flex items-center gap-2 font-semibold" data-scan-field="jadwal-label">-</p>
                        <p class="mt-1 hidden text-xs" data-scan-field="jadwal-nama-acara"></p>
                        <p class="hidden text-xs opacity-80" data-scan-field="jadwal-keterangan"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
