<div>
    <div
        x-data="{ openModalDownload: false }"
        x-show="openModalDownload"

        x-on:open-download-modal.window="
            openModalDownload = true;
            if ($event.detail && $event.detail.id) { $wire.loadMurid($event.detail.id); }
        "

        @close-download-modal.window="openModalDownload = false"
        x-transition.opacity
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-md"
    >
        <div
            @click.outside="openModalDownload = false"
            x-transition.scale
            class="w-full max-w-xl overflow-hidden rounded-[2rem] border border-white/20 bg-white shadow-2xl"
        >

            <!-- HEADER -->
            <div
                class="relative overflow-hidden bg-gradient-to-r from-blue-main via-blue-deep to-[#07162f] px-6 py-5 text-white"
            >

                <div class="absolute -right-12 -top-12 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute bottom-0 left-0 h-28 w-28 rounded-full bg-cyan-400/10 blur-2xl"></div>

                <div class="relative flex items-center justify-between gap-4">

                    <div class="flex items-center gap-4">

                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/15 bg-white/10 backdrop-blur"
                        >

                            <iconify-icon
                                icon="solar:download-bold"
                                width="28"
                                height="28">
                            </iconify-icon>

                        </div>

                        <div>
                            <h1 class="text-xl font-bold">
                                Download Kartu Pelajar / QR code
                            </h1>

                            <p class="mt-1 text-sm text-blue-100">
                                Pilih format kartu yang ingin diunduh untuk {{ $murid?->nama ?? 'murid ini' }}. Lanjut pilih model.
                            </p>
                        </div>

                    </div>

                    <button
                        type="button"
                        @click="openModalDownload = false"
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-white transition hover:bg-white/20 active:scale-95"
                    >

                        <iconify-icon
                            icon="mdi:close"
                            width="22"
                            height="22">
                        </iconify-icon>

                    </button>

                </div>

            </div>

            <!-- BODY -->
            <div class="max-h-[75vh] overflow-y-auto p-6 scroll-thin">
                <div class="grid grid-cols-2 gap-4">

                    <!-- Vertical -->
                    <button
                        type="button"
                        wire:click="pilihOrientasi('vertical')"
                        class="group rounded-3xl border-2 border-gray-200 bg-gray-50 p-5 transition hover:border-blue-main hover:bg-blue-50"
                    >
                        <div class="flex flex-col items-center">
                            <div class="mb-3 flex h-20 w-14 items-center justify-center rounded-md border-2 border-blue-500">
                                <iconify-icon
                                    icon="solar:document-bold"
                                    width="28"
                                    height="28"
                                    class="text-blue-500"
                                ></iconify-icon>
                            </div>

                            <h3 class="font-bold text-gray-800">
                                Vertical
                            </h3>

                            <p class="mt-1 text-xs text-gray-500">
                                Ukuran portrait
                            </p>
                        </div>
                    </button>

                    <!-- Horizontal -->
                    <button
                        type="button"
                        wire:click="pilihOrientasi('horizontal')"
                        class="group rounded-3xl border-2 border-gray-200 bg-gray-50 p-5 transition hover:border-emerald-500 hover:bg-emerald-50"
                    >
                        <div class="flex flex-col items-center">
                            <div class="mb-3 flex h-14 w-20 items-center justify-center rounded-md border-2 border-green-500">
                                <iconify-icon
                                    icon="solar:document-bold"
                                    width="28"
                                    height="28"
                                    class="text-green-500"
                                ></iconify-icon>
                            </div>

                            <h3 class="font-bold text-gray-800">
                                Horizontal
                            </h3>

                            <p class="mt-1 text-xs text-gray-500">
                                Ukuran landscape
                            </p>
                        </div>
                    </button>

                </div>
            </div>

            <!-- FOOTER -->
            <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                <button
                    type="button"
                    @click="openModalDownload = false"
                    class="rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
                >
                    Batal
                </button>
            </div>

        </div>
    </div>

    <div
        x-data="{ openModalModel: false }"
        x-show="openModalModel"
        x-on:open-model-modal.window="openModalModel = true"
        @close-model-modal.window="openModalModel = false"
        x-transition.opacity
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-md"
    >
        <div
            @click.outside="openModalModel = false"
            x-transition.scale
            class="w-full max-w-xl overflow-hidden rounded-[2rem] border border-white/20 bg-white shadow-2xl"
        >

            <!-- HEADER -->
            <div
                class="relative overflow-hidden bg-gradient-to-r from-blue-main via-blue-deep to-[#07162f] px-6 py-5 text-white"
            >

                <div class="absolute -right-12 -top-12 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute bottom-0 left-0 h-28 w-28 rounded-full bg-cyan-400/10 blur-2xl"></div>

                <div class="relative flex items-center justify-between gap-4">

                    <div class="flex items-center gap-4">

                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/15 bg-white/10 backdrop-blur"
                        >

                            <iconify-icon
                                icon="solar:palette-bold"
                                width="28"
                                height="28">
                            </iconify-icon>

                        </div>

                        <div>
                            <h1 class="text-xl font-bold">
                                Pilih Model Kartu
                            </h1>

                            <p class="mt-1 text-sm text-blue-100">
                                Orientasi: {{ $pendingOrientation === 'vertical' ? 'Vertical' : 'Horizontal' }}
                                untuk {{ $murid?->nama ?? 'murid ini' }}.
                            </p>
                        </div>

                    </div>

                    <button
                        type="button"
                        @click="openModalModel = false"
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-white transition hover:bg-white/20 active:scale-95"
                    >

                        <iconify-icon
                            icon="mdi:close"
                            width="22"
                            height="22">
                        </iconify-icon>

                    </button>

                </div>

            </div>

            <!-- BODY -->
            <div class="max-h-[75vh] overflow-y-auto p-6 scroll-thin">
                <div class="grid grid-cols-1 gap-4">
                    @foreach ($this->cardModels() as $model)
                        <button
                            type="button"
                            wire:click="unduhDenganModel('{{ $model['key'] }}')"
                            class="group rounded-3xl border-2 border-gray-200 bg-gray-50 p-5 text-left transition hover:border-blue-main hover:bg-blue-50"
                        >
                            <div class="flex items-center gap-4">
                                <div class="{{ $pendingOrientation === 'horizontal' ? 'h-14 w-20 border-green-500' : 'h-20 w-14 border-blue-500' }} flex shrink-0 items-center justify-center rounded-md border-2">
                                    <iconify-icon
                                        icon="solar:card-bold"
                                        width="28"
                                        height="28"
                                        class="text-blue-500"
                                    ></iconify-icon>
                                </div>

                                <div>
                                    <h3 class="font-bold text-gray-800">
                                        {{ $model['nama'] }}
                                    </h3>

                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $model['deskripsi'] }}
                                    </p>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- FOOTER -->
            <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                <button
                    type="button"
                    wire:click="kembaliKeOrientasi"
                    class="rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
                >
                    Kembali
                </button>
                <button
                    type="button"
                    @click="openModalModel = false"
                    class="rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
                >
                    Batal
                </button>
            </div>

        </div>
    </div>
</div>
