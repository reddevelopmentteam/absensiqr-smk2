<div class="space-y-6">

    <!-- HERO -->
    <div
        class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-main via-blue-deep to-[#07162f] p-6 shadow-lg">

        <div class="absolute -right-16 -top-16 h-60 w-60 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 h-40 w-40 rounded-full bg-cyan-400/10 blur-3xl"></div>

        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">

            <div class="flex items-center gap-5">

                <div
                    class="flex h-16 w-16 items-center justify-center rounded-2xl border border-white/10 bg-white/10 backdrop-blur">

                    <iconify-icon
                        icon="solar:qr-code-bold"
                        width="36"
                        height="36"
                        class="text-white">
                    </iconify-icon>

                </div>

                <div>
                    <h1 class="text-3xl font-bold text-white">
                        Generate QR Code
                    </h1>

                    <p class="mt-1 text-sm text-blue-100">
                        Generate dan download QR Code murid atau guru dalam format ZIP.
                    </p>
                </div>

            </div>

            <div
                class="rounded-2xl border border-white/15 bg-white/10 px-5 py-4 backdrop-blur">

                <p class="text-xs uppercase tracking-[0.2em] text-blue-100">
                    Status
                </p>

                <h2 class="mt-1 text-lg font-bold text-white">
                    {{ $isGenerating ? 'Sedang Generate' : 'Siap Generate' }}
                </h2>

            </div>

        </div>
    </div>

    <!-- GRID -->
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

        <!-- GENERATE QR MURID -->
        <div
            class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm col-span-2">

            <!-- header -->
            <div
                class="border-b border-gray-200 bg-gray-50 px-6 py-5">

                <div class="flex items-start gap-4">

                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-blue-main">

                        <iconify-icon
                            icon="solar:users-group-rounded-bold"
                            width="28"
                            height="28">
                        </iconify-icon>

                    </div>

                    <div>
                        <h2 class="text-xl font-bold text-gray-800">
                            QR Code Murid
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Generate QR berdasarkan tingkat, jurusan, dan kelas.
                        </p>
                    </div>

                </div>

            </div>

            <!-- content -->
            <div class="p-6">

                <!-- filter -->
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                                <!-- tingkat -->
            <livewire:components.searchable-select
                wire:model.live="filterTingkat"
                label="Tingkat"
                placeholder="Cari tingkat..."
                :options="$listRombel->pluck('tingkat')->filter()->sortBy('nama')->unique('id')->values()"
                value-key="id"
                label-key="nama"
                all-label="Semua Tingkat"
                not-found-text="Tingkat tidak ditemukan."
                input-size="md"
                width="full"
                icon="mdi:magnify"
                icon-size="22"
                icon-position="right"
                icon-offset="4"
                icon-class="text-gray-500"
                dropdown-height="md"
                input-class="bg-white"
                dropdown-class="shadow-2xl"
                option-class="font-medium"
                check-icon="mdi:check"
                check-icon-size="20"
                check-icon-class="text-black-500" />
            
            <!-- jurusan -->
            <livewire:components.searchable-select
                wire:model.live="filterJurusan"
                label="Jurusan"
                placeholder="Cari jurusan..."
                :options="$filteredJurusan"
                value-key="id"
                label-key="nama"
                all-label="Semua Jurusan"
                not-found-text="Jurusan tidak ditemukan."
                input-size="md"
                width="full"
                icon="mdi:magnify"
                icon-size="22"
                icon-position="right"
                icon-offset="4"
                icon-class="text-gray-500"
                dropdown-height="md"
                input-class="bg-white"
                dropdown-class="shadow-2xl"
                option-class="font-medium"
                check-icon="mdi:check"
                check-icon-size="20"
                check-icon-class="text-black-500" />
                
            <!-- kelas / indexs -->
            <livewire:components.searchable-select
                wire:model.live="filterIndeks"
                label="Kelas"
                placeholder="Cari kelas..."
                :options="$filteredIndeks"
                value-key="id"
                label-key="nama"
                all-label="Semua Kelas"
                not-found-text="Kelas tidak ditemukan."
                input-size="md"
                width="full"
                icon="mdi:magnify"
                icon-size="22"
                icon-position="right"
                icon-offset="4"
                icon-class="text-gray-500"
                dropdown-height="md"
                input-class="bg-white"
                dropdown-class="shadow-2xl"
                option-class="font-medium"
                check-icon="mdi:check"
                check-icon-size="20"
                check-icon-class="text-black-500" />
                </div>

                <!-- progress -->
                <div
                    class="mt-8 rounded-3xl border border-gray-200 bg-gray-50 p-5 {{ $totalData <= 0 ? 'opacity-50' : '' }}">

                    <div wire:ignore>

                        <div class="mb-3 flex items-center justify-between">

                            <div>
                                <h3 class="font-semibold text-gray-800">
                                    Progress Generate
                                </h3>

                                <p class="text-sm text-gray-500">
                                    File akan otomatis terdownload setelah selesai.
                                </p>
                            </div>

                            <div
                                class="rounded-full bg-blue-100 px-4 py-1 text-sm font-semibold text-blue-main">

                                <span id="progressPercentMurid">0%</span>

                            </div>

                        </div>

                        <div class="mb-2 flex justify-between text-sm text-gray-500">
                            <span id="progressTextMurid">0/0</span>
                            <span>QR Murid</span>
                        </div>

                        <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200">

                            <div
                                id="progressBarMurid"
                                class="h-3 rounded-full bg-gradient-to-r from-blue-main to-blue-deep transition-all duration-300"
                                style="width: 0%">
                            </div>

                        </div>

                    </div>

                </div>

                <!-- action -->
                <div class="mt-6 grid grid-cols-2 gap-4">

                    <!-- horizontal  -->
                    <button
                        wire:click="pilihModelUntukGenerate('horizontal')"
                        @disabled($orientation === 'horizontal' && $isGenerating)
                        class="py-4 px-2 group flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-main to-blue-deep px-5 py-3 font-semibold text-white shadow-lg text-sm transition hover:-translate-y-0.5 hover:shadow-xl active:scale-95 disabled:cursor-not-allowed disabled:opacity-60 md:w-auto">

                        <iconify-icon
                            icon="{{ $orientation === 'horizontal' && $isGenerating ? 'line-md:loading-twotone-loop' : 'lineicons:cloud-download' }}"
                            width="22"
                            height="22">
                        </iconify-icon>

                        {{ $isGenerating ? 'Generating...' : 'Download QR Murid (HORIZONTAL)' }}

                    </button>
                    
                    <!-- VERTICAL -->
                    <button
                        wire:click="pilihModelUntukGenerate('vertical')"
                       @disabled($orientation === 'vertical' && $isGenerating)
                        class="py-4 px-2 group flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-main to-blue-deep px-5 py-3 font-semibold text-white shadow-lg text-sm transition hover:-translate-y-0.5 hover:shadow-xl active:scale-95 disabled:cursor-not-allowed disabled:opacity-60 md:w-auto">

                        <iconify-icon
                            icon="{{ $orientation === 'vertical' && $isGenerating ? 'line-md:loading-twotone-loop' : 'lineicons:cloud-download' }}"
                            width="22"
                            height="22">
                        </iconify-icon>

                        {{ $isGenerating ? 'Generating...' : 'Download QR Murid (VERTICAL)' }}

                    </button>

                </div>

            </div>

        </div>

        <!-- GENERATE QR GURU -->
        {{-- <div
            class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">

            <!-- header -->
            <div
                class="border-b border-gray-200 bg-gray-50 px-6 py-5">

                <div class="flex items-start gap-4">

                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">

                        <iconify-icon
                            icon="solar:user-id-bold"
                            width="28"
                            height="28">
                        </iconify-icon>

                    </div>

                    <div>
                        <h2 class="text-xl font-bold text-gray-800">
                            QR Code Guru
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Generate semua QR Code guru dalam satu file ZIP.
                        </p>
                    </div>

                </div>

            </div>

            <!-- content -->
            <div class="p-6">

                <!-- info box -->
                <div
                    class="rounded-3xl border border-emerald-100 bg-emerald-50 p-5">

                    <div class="flex gap-4">

                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-500 text-white">

                            <iconify-icon
                                icon="solar:info-circle-bold"
                                width="24"
                                height="24">
                            </iconify-icon>

                        </div>

                        <div>
                            <h3 class="font-semibold text-gray-800">
                                Generate QR Guru
                            </h3>

                            <p class="mt-1 text-sm leading-relaxed text-gray-500">
                                QR Code guru akan diproses dan dikemas ke dalam file ZIP untuk memudahkan download.
                            </p>
                        </div>

                    </div>

                </div>

                <!-- progress -->
                <div class="mt-8 rounded-3xl border border-gray-200 bg-gray-50 p-5">

                    <div class="mb-3 flex items-center justify-between">

                        <div>
                            <h3 class="font-semibold text-gray-800">
                                Progress Generate
                            </h3>

                            <p class="text-sm text-gray-500">
                                Tunggu sampai progress mencapai 100%.
                            </p>
                        </div>

                        <div
                            class="rounded-full bg-emerald-100 px-4 py-1 text-sm font-semibold text-emerald-600">

                            <span id="progressPercentGuru">0%</span>

                        </div>

                    </div>

                    <div class="mb-2 flex justify-between text-sm text-gray-500">
                        <span id="progressTextGuru">0/0</span>
                        <span>QR Guru</span>
                    </div>

                    <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200">

                        <div
                            id="progressBarGuru"
                            class="h-3 rounded-full bg-gradient-to-r from-emerald-500 to-emerald-600 transition-all duration-300"
                            style="width: 0%">
                        </div>

                    </div>

                </div>

                <!-- action -->
                <div class="mt-6 flex justify-end">
                    
                    <button
                        onclick="startGenerateGuru()"
                        class="group flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-xl active:scale-95 md:w-auto">

                        <iconify-icon
                            icon="lineicons:cloud-download"
                            width="22"
                            height="22"
                            class="transition group-hover:scale-110">
                        </iconify-icon>

                        Download QR Guru

                    </button>

                </div>

            </div>

        </div> --}}

    </div>

    <!-- MODAL PILIH MODEL -->
    <div
        x-data="{ openGenerateModel: false }"
        x-show="openGenerateModel"
        x-on:open-generate-model-modal.window="openGenerateModel = true"
        @close-generate-model-modal.window="openGenerateModel = false"
        x-transition.opacity
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-md"
    >
        <div
            @click.outside="openGenerateModel = false"
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
                                Orientasi: {{ $pendingOrientation === 'vertical' ? 'Vertical' : 'Horizontal' }} — model dipakai untuk semua kartu pada batch ini.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="openGenerateModel = false"
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
                @if ($pendingOrientation)
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach ($this->cardModels() as $model)
                            <button
                                type="button"
                                wire:click="startGenerate('{{ $pendingOrientation }}', '{{ $model['key'] }}')"
                                class="group rounded-3xl border-2 border-gray-200 bg-gray-50 p-5 text-left transition hover:border-blue-main hover:bg-blue-50 hover:shadow-lg"
                            >
                                <div class="flex flex-col items-center">
                                    @if ($model['key'] === 'standar')
                                        @if ($pendingOrientation === 'vertical')
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
                @endif
            </div>
            <!-- FOOTER -->
            <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                <button
                    type="button"
                    wire:click="batalPilihModel"
                    class="rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
                >
                    Batal
                </button>
            </div>
        </div>
    </div>

</div>

@script
    <script>
        Promise.all([
            import('{{ Vite::asset('resources/js/generateQR.js') }}'),
            import('{{ Vite::asset('resources/js/generateCard.js') }}')
        ]).then(() => {
            const progressTextMurid = document.getElementById('progressTextMurid');
            const progressPercentMurid = document.getElementById('progressPercentMurid');
            const progressBarMurid = document.getElementById('progressBarMurid');

            let processing = false;
            let processed = 0;
            let totalData = 0;
            let currentRunId = null;
            let cardsSinceYield = 0;
            let backSourceByClass = new Map();
            let zip = null;
            let zipChunks = [];

            function resetProgress() {
                processed = 0;
                totalData = 0;
                cardsSinceYield = 0;
                zipChunks = [];
                backSourceByClass = new Map();

                progressTextMurid.textContent = '0/0';
                progressPercentMurid.textContent = '0%';
                progressBarMurid.style.width = '0%';
            }

            function createZip() {
                zipChunks = [];

                zip = new Zip((err, chunk, final) => {
                    if (err) {
                        console.error(err);
                        return;
                    }

                    zipChunks.push(chunk);

                    if (final) {
                        const blob = new Blob(zipChunks, {
                            type: 'application/zip'
                        });

                        const a = document.createElement('a');

                        a.href = URL.createObjectURL(blob);
                        a.download = 'murid-qr.zip';

                        a.click();

                        URL.revokeObjectURL(a.href);
                    }
                });
            }

            const sanitizeFileName = (value) => {
                return String(value ?? 'kelas')
                    .trim()
                    .replace(/[\\?%*:|"<>]/g, '-')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^[-.]+|[-.]+$/g, '');
            };

            const downloadPdf = (fileName, bytes) => {
                const blob = new Blob([bytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = fileName;
                a.click();
                URL.revokeObjectURL(url);
            };

            let lastYieldAt = 0;

            const yieldToBrowser = async (force = false) => {
                const now = performance.now();

                if (!force && now - lastYieldAt < 24) {
                    return;
                }

                lastYieldAt = now;

                if (globalThis.scheduler?.yield) {
                    await globalThis.scheduler.yield();
                    return;
                }

                await new Promise(resolve => {
                    if (document.hidden || typeof requestAnimationFrame !== 'function') {
                        setTimeout(resolve, 0);
                        return;
                    }

                    requestAnimationFrame(() => resolve());
                });
            };

            const yieldAfterCard = async () => {
                cardsSinceYield++;

                const elapsedMs = performance.now() - lastYieldAt;
                const shouldYield = cardsSinceYield >= 4
                    || (cardsSinceYield >= 2 && elapsedMs >= 100);

                if (!shouldYield) {
                    return;
                }

                cardsSinceYield = 0;
                await yieldToBrowser(true);
            };

            const releaseCanvas = (canvas) => {
                if (!canvas) {
                    return;
                }

                canvas.width = 1;
                canvas.height = 1;
            };

            const preloadImage = (src) => {
                if (!src) {
                    return;
                }

                const image = new Image();
                image.crossOrigin = 'anonymous';
                image.decoding = 'async';
                image.src = new URL(src, window.location.origin).href;
            };

            $wire.$on('generate-qr', async (event) => {
                if (processing) {
                    return;
                }

                processing = true;

                try {
                    if (currentRunId !== event.runId) {
                        currentRunId = event.runId;
                        resetProgress();
                        createZip();
                        window.resetStudentCardPdf?.();
                        await window.preloadStudentCardAssets?.([event.orientation ?? 'horizontal'], { model: event.model ?? 'standar' });
                        await yieldToBrowser(true);
                    }

                    if (typeof event.totalData === 'number' && event.totalData >= 0) {
                        totalData = event.totalData;
                    }

                    const {
                        dataMurid
                    } = event;
                    const qrTasks = new Map();
                    const lookAhead = 3;
                    const queueAssets = (index) => {
                        const murid = dataMurid[index];

                        if (!murid) {
                            return;
                        }

                        if (!qrTasks.has(index)) {
                            qrTasks.set(index, generateQRSVG(murid.uuid));
                        }

                        preloadImage(murid.image_path);
                    };

                    for (let index = 0; index < Math.min(lookAhead, dataMurid.length); index++) {
                        queueAssets(index);
                    }

                    for (let index = 0; index < dataMurid.length; index++) {
                        const murid = dataMurid[index];
                        queueAssets(index + lookAhead);
                        const svgString = await qrTasks.get(index);
                        qrTasks.delete(index);

                        const renderer = event.orientation === 'horizontal'
                            ? window.renderStudentCardCanvas
                            : window.renderStudentCardVerticalCanvas;

                        const cardCanvas = await renderer({
                            murid,
                            qrSvg: svgString,
                            model: event.model ?? 'standar'
                        });


                        if (cardCanvas) {
                            const className = murid?.rombel?.nama_lengkap ?? 'Kelas';
                            if (!backSourceByClass.has(className)) {
                                backSourceByClass.set(className, { murid, qrSvg: svgString });
                            }
                            await window.addStudentCardToPdf?.({
                                className,
                                canvas: cardCanvas,
                                orientation: event.orientation ?? 'horizontal',
                                model: event.model ?? 'standar'
                            });
                            releaseCanvas(cardCanvas);
                        }



                        processed++;

                        const percent = totalData > 0 ?
                            Math.round((processed / totalData) * 100) :
                            0;

                        progressTextMurid.textContent = `${processed}/${totalData}`;
                        progressPercentMurid.textContent = `${percent}%`;
                        progressBarMurid.style.width = `${percent}%`;

                        await yieldAfterCard();
                    }

                    if (processed >= totalData) {
                        await yieldToBrowser(true);
                        const orientation = event.orientation ?? 'horizontal';
                        for (const murid of dataMurid) {
                            const className = murid?.rombel?.nama_lengkap ?? 'Kelas';
                            if (!appendedBackPages.has(className)) {
                                appendedBackPages.add(className);
                                const backSource = backSourceByClass.get(className) ?? { murid, qrSvg: '' };
                                await window.appendStudentCardBackPage?.({ className, orientation, model: event.model ?? 'standar', murid: backSource.murid, qrSvg: backSource.qrSvg });
                                await yieldToBrowser(true);
                            }
                        }


                        const pdfFiles = await (
                            window.exportStudentCardPdfsAsync?.() ??
                            Promise.resolve(window.exportStudentCardPdfs?.() ?? [])
                        );

                        if (pdfFiles.length === 1) {
                            const single = pdfFiles[0];
                            const pdfName = `${sanitizeFileName(single.className)}.pdf`;
                            downloadPdf(pdfName, single.pdfBytes);
                        } else {
                            for (const pdf of pdfFiles) {
                                const pdfName = `${sanitizeFileName(pdf.className)}.pdf`;
                                const file = new ZipPassThrough(pdfName);
                                zip.add(file);
                                file.push(pdf.pdfBytes, true);
                                await yieldToBrowser();
                            }

                            zip.end();
                        }

                        window.clearStudentCardMemory?.();
                    }
                } catch (error) {
                    console.error(error);
                } finally {
                    processing = false;
                    $wire.nextChunk(event.orientation ?? 'horizontal', event.model ?? 'standar');
                }
            });
        })
    </script>
@endscript
