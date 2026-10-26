<template data-scan-modal-template="message">
    <div class="fixed inset-0 z-50" id="message-modal">
        <div data-close-scan class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm"></div>
        <div
            class="pointer-events-none relative flex min-h-dvh items-center justify-center overflow-y-auto p-4 py-8 sm:p-6">
            <div class="pointer-events-auto w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
                <!-- header -->
                <div
                    class="flex items-center gap-3 bg-gradient-to-r from-amber-600 to-amber-500 px-5 py-4 text-white sm:px-6">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/15">
                        <iconify-icon icon="solar:info-circle-bold" width="26" height="26"
                            aria-hidden="true"></iconify-icon>
                    </span>
                    <h2 class="min-w-0 flex-1 truncate text-base font-bold leading-tight sm:text-lg"
                        data-scan-field="title">Informasi Absensi</h2>
                </div>

                <!-- body -->
                <div class="p-5 sm:p-6">
                    <p class="text-center text-xl font-extrabold leading-tight text-gray-900 sm:text-2xl"
                        data-scan-field="message"></p>
                </div>
            </div>
        </div>
    </div>
</template>
