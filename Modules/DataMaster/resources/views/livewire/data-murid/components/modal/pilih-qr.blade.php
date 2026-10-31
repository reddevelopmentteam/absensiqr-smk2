<div>
    <div
        x-data="{ openModalDownload: false }"
        x-show="openModalDownload"

        x-on:open-download-modal.window="
            openModalDownload = true;
            if ($event.detail && $event.detail.id) { $wire.loadMurid($event.detail.id); }
        "

        @close-download-modal.window="openModalDownload = false"
        x-transition
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-5 m-0"
    >
        <div
            @click.outside="openModalDownload = false"
            class="bg-white rounded-lg shadow-lg w-[430px] py-6"
        >

            <!-- Header -->
            <h2 class="flex items-center gap-2 text-lg font-semibold mb-4 px-6">
                <div class="bg-blue-100 text-blue-600 p-2 rounded-lg flex items-center justify-center">
                    <iconify-icon icon="solar:download-bold" width="20" height="20"></iconify-icon>
                </div>
                Download Kartu Pelajar / QR code
            </h2>

            <hr class="mb-5 text-gray-300">

            <!-- Content -->
            <div class="px-6">
                <p class="text-gray-600 mb-5">
                    Pilih format kartu yang ingin diunduh untuk {{ $murid?->nama ?? 'murid ini' }}. Lanjut pilih model.
                </p>

                <div class="grid grid-cols-2 gap-4">

                    <!-- Vertical -->
                    <button
                        type="button"
                        wire:click="pilihOrientasi('vertical')"
                        class="group border-2 border-gray-200 rounded-xl p-5 hover:border-blue-500 hover:bg-blue-50 transition"
                    >
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-20 border-2 border-blue-500 rounded-md mb-3 flex items-center justify-center">
                                <iconify-icon
                                    icon="solar:document-bold"
                                    width="28"
                                    height="28"
                                    class="text-blue-500"
                                ></iconify-icon>
                            </div>

                            <h3 class="font-semibold text-gray-800">
                                Vertical
                            </h3>

                            <p class="text-xs text-gray-500 mt-1">
                                Ukuran portrait
                            </p>
                        </div>
                    </button>

                    <!-- Horizontal -->
                    <button
                        type="button"
                        wire:click="pilihOrientasi('horizontal')"
                        class="group border-2 border-gray-200 rounded-xl p-5 hover:border-green-500 hover:bg-green-50 transition"
                    >
                        <div class="flex flex-col items-center">
                            <div class="w-20 h-14 border-2 border-green-500 rounded-md mb-3 flex items-center justify-center">
                                <iconify-icon
                                    icon="solar:document-bold"
                                    width="28"
                                    height="28"
                                    class="text-green-500"
                                ></iconify-icon>
                            </div>

                            <h3 class="font-semibold text-gray-800">
                                Horizontal
                            </h3>

                            <p class="text-xs text-gray-500 mt-1">
                                Ukuran landscape
                            </p>
                        </div>
                    </button>

                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 mt-6 px-6">
                <button
                    @click="openModalDownload = false"
                    class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition"
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
        x-transition
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-5 m-0"
    >
        <div
            @click.outside="openModalModel = false"
            class="bg-white rounded-lg shadow-lg w-[430px] py-6"
        >

            <!-- Header -->
            <h2 class="flex items-center gap-2 text-lg font-semibold mb-4 px-6">
                <div class="bg-blue-100 text-blue-600 p-2 rounded-lg flex items-center justify-center">
                    <iconify-icon icon="solar:palette-bold" width="20" height="20"></iconify-icon>
                </div>
                Pilih Model Kartu
            </h2>

            <hr class="mb-5 text-gray-300">

            <!-- Content -->
            <div class="px-6">
                <p class="text-gray-600 mb-5">
                    Orientasi: {{ $pendingOrientation === 'vertical' ? 'Vertical' : 'Horizontal' }}
                    untuk {{ $murid?->nama ?? 'murid ini' }}.
                </p>

                <div class="grid grid-cols-1 gap-4">
                    @foreach ($this->cardModels() as $model)
                        <button
                            type="button"
                            wire:click="unduhDenganModel('{{ $model['key'] }}')"
                            class="group border-2 border-gray-200 rounded-xl p-5 hover:border-blue-500 hover:bg-blue-50 transition text-left"
                        >
                            <div class="flex items-center gap-4">
                                <div class="{{ $pendingOrientation === 'horizontal' ? 'w-20 h-14 border-green-500' : 'w-14 h-20 border-blue-500' }} border-2 rounded-md flex items-center justify-center shrink-0">
                                    <iconify-icon
                                        icon="solar:card-bold"
                                        width="28"
                                        height="28"
                                        class="text-blue-500"
                                    ></iconify-icon>
                                </div>

                                <div>
                                    <h3 class="font-semibold text-gray-800">
                                        {{ $model['nama'] }}
                                    </h3>

                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $model['deskripsi'] }}
                                    </p>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 mt-6 px-6">
                <button
                    type="button"
                    wire:click="kembaliKeOrientasi"
                    class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition"
                >
                    Kembali
                </button>
                <button
                    @click="openModalModel = false"
                    class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition"
                >
                    Batal
                </button>
            </div>

        </div>
    </div>
</div>
