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
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ($this->cardModels() as $model)
                        <button
                            type="button"
                            wire:click="unduhDenganModel('{{ $model['key'] }}')"
                            class="group rounded-3xl border-2 border-gray-200 bg-gray-50 p-5 text-left transition hover:border-blue-main hover:bg-blue-50 hover:shadow-lg"
                        >
                            <div class="flex flex-col items-center">
                                @if ($model['key'] === 'standar')
                                    @if ($pendingOrientation === 'vertical')
                                        {{-- Wireframe modern portrait (acuan kartu-pelajar-modern-vertical.html) --}}
                                        <div data-model-preview="standar-vertical" class="relative h-36 w-24 shrink-0 overflow-hidden rounded-lg border-2 bg-white" style="border-color: {{ $model['accent'] }}">
                                            <div class="absolute inset-x-0 top-0 h-[30%] rounded-b-[50%] bg-gradient-to-b from-[#0DC425] to-[#065E0C]"></div>
                                            <div class="absolute left-1/2 top-2 h-4 w-4 -translate-x-1/2 rounded-full bg-white/90 ring-1 ring-white"></div>
                                            <div class="absolute left-1/2 top-7 h-1 w-10 -translate-x-1/2 rounded bg-white/90"></div>
                                            <div class="absolute inset-x-2 top-[34%] flex flex-col items-center">
                                                <div class="h-8 w-6 rounded bg-[#d7e9ff] ring-1 ring-gray-300"></div>
                                                <div class="mt-1 h-1 w-12 rounded bg-gray-700/80"></div>
                                                <div class="mt-1 h-1 w-10 rounded bg-gray-400/80"></div>
                                                <div class="mt-1 h-1 w-8 rounded bg-gray-300"></div>
                                                <div class="mt-1.5 h-5 w-5 border border-black bg-white p-px">
                                                    <div class="grid h-full w-full grid-cols-3 grid-rows-3 gap-px">
                                                        <div class="bg-black"></div><div></div><div class="bg-black"></div>
                                                        <div></div><div class="bg-black"></div><div></div>
                                                        <div class="bg-black"></div><div></div><div class="bg-black"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        {{-- Wireframe modern landscape (acuan kartu-pelajar-modern-horizontal.html) --}}
                                        <div data-model-preview="standar-horizontal" class="relative h-24 w-36 shrink-0 overflow-hidden rounded-lg border-2 bg-white" style="border-color: {{ $model['accent'] }}">
                                            <div class="absolute inset-x-0 top-0 h-[38%] rounded-b-[50%] bg-gradient-to-b from-[#0DC425] to-[#065E0C]"></div>
                                            <div class="absolute left-2 top-2 h-4 w-4 rounded-full bg-white/90 ring-1 ring-white"></div>
                                            <div class="absolute left-8 top-2.5 h-1 w-12 rounded bg-white/90"></div>
                                            <div class="absolute left-8 top-4 h-0.5 w-8 rounded bg-white/70"></div>
                                            <div class="absolute bottom-2 left-2 h-9 w-7 rounded bg-[#d7e9ff] ring-1 ring-gray-300"></div>
                                            <div class="absolute bottom-2 left-11 right-2">
                                                <div class="h-1 w-3/4 rounded bg-gray-700/80"></div>
                                                <div class="mt-1 h-1 w-2/3 rounded bg-gray-400/80"></div>
                                                <div class="mt-1 h-1 w-1/2 rounded bg-gray-300"></div>
                                                <div class="mt-1.5 h-px w-full bg-black/70"></div>
                                                <div class="mt-1 flex items-end justify-between">
                                                    <div class="h-0.5 w-10 rounded bg-gray-300"></div>
                                                    <div class="h-5 w-5 border border-black bg-white p-px">
                                                        <div class="grid h-full w-full grid-cols-3 grid-rows-3 gap-px">
                                                            <div class="bg-black"></div><div></div><div class="bg-black"></div>
                                                            <div></div><div class="bg-black"></div><div></div>
                                                            <div class="bg-black"></div><div></div><div class="bg-black"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    @if ($pendingOrientation === 'vertical')
                                        {{-- Miniatur desain hijau model baru (portrait 2:3, panel hijau atas) --}}
                                        <div data-model-preview="baru-vertical" class="flex h-36 w-24 shrink-0 flex-col overflow-hidden rounded-lg border-2 bg-white" style="border-color: {{ $model['accent'] }}">
                                            <div class="flex h-[30%] flex-col items-center bg-[#85983c] py-0.5 text-white">
                                                <div class="h-2 w-2 rounded-full bg-white/80"></div>
                                                <div class="mt-px h-0.5 w-5 rounded bg-white/70"></div>
                                            </div>
                                            <div class="flex flex-1 flex-col items-center px-1 py-0.5">
                                                <div class="h-4 w-5 rounded-sm bg-sky-500 ring-1 ring-gray-300"></div>
                                                <div class="mt-0.5 h-0.5 w-8 rounded bg-gray-800"></div>
                                                <div class="mt-0.5 flex items-center gap-0.5 text-[4px] font-bold leading-none text-black">
                                                    <span>X PPLG A</span>
                                                    <span class="h-1.5 w-px bg-black"></span>
                                                    <span>NISN</span>
                                                </div>
                                                <div class="mt-0.5 h-4 w-4 border border-black bg-white p-px">
                                                    <div class="grid h-full w-full grid-cols-3 grid-rows-3 gap-px">
                                                        <div class="bg-black"></div><div></div><div class="bg-black"></div>
                                                        <div></div><div class="bg-black"></div><div></div>
                                                        <div class="bg-black"></div><div></div><div class="bg-black"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        {{-- Miniatur desain hijau model baru (landscape 3:2) --}}
                                        <div data-model-preview="baru-horizontal" class="flex h-24 w-36 shrink-0 overflow-hidden rounded-lg border-2 bg-white" style="border-color: {{ $model['accent'] }}">
                                            <div class="flex w-[28%] flex-col items-center bg-[#85983c] py-1 text-white">
                                                <div class="h-3 w-3 rounded-full bg-white/80"></div>
                                                <div class="mt-0.5 h-0.5 w-8 rounded bg-white/70"></div>
                                                <div class="mt-1 h-6 w-4 rounded-sm bg-sky-500 ring-1 ring-white"></div>
                                            </div>
                                            <div class="flex-1 px-1 py-1">
                                                <div class="text-[5px] font-extrabold leading-tight text-black">KARTU PELAJAR</div>
                                                <div class="mt-1 space-y-1">
                                                    <div>
                                                        <div class="text-[4px] leading-none text-gray-500">Nama</div>
                                                        <div class="text-[5px] font-bold leading-tight text-black">Nama Murid</div>
                                                    </div>
                                                    <div>
                                                        <div class="text-[4px] leading-none text-gray-500">Kelas</div>
                                                        <div class="text-[5px] font-bold leading-tight text-black">X PPLG A</div>
                                                    </div>
                                                </div>
                                                <div class="ml-auto mt-1 h-5 w-5 border border-black bg-white p-px">
                                                    <div class="grid h-full w-full grid-cols-3 grid-rows-3 gap-px">
                                                        <div class="bg-black"></div><div></div><div class="bg-black"></div>
                                                        <div></div><div class="bg-black"></div><div></div>
                                                        <div class="bg-black"></div><div></div><div class="bg-black"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                                <div class="mt-3 flex w-full flex-col items-center text-center">
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $model['accent'] }}">{{ $model['tag'] }}</span>
                                    <h3 class="mt-1.5 font-bold text-gray-800">
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
