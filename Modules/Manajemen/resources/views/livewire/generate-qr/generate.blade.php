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
                        wire:click="startGenerate('horizontal')"
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
                        wire:click="startGenerate('vertical')"
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
            let appendedBackPages = new Set();
            let zip = null;
            let zipChunks = [];

            function resetProgress() {
                processed = 0;
                totalData = 0;
                cardsSinceYield = 0;
                zipChunks = [];
                appendedBackPages = new Set();

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
                        await window.preloadStudentCardAssets?.([event.orientation ?? 'horizontal']);
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
                            qrSvg: svgString
                        });


                        if (cardCanvas) {
                            const className = murid?.rombel?.nama_lengkap ?? 'Kelas';
                            await window.addStudentCardToPdf?.({
                                className,
                                canvas: cardCanvas,
                                orientation: event.orientation ?? 'horizontal'
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
                                await window.appendStudentCardBackPage?.({ className, orientation });
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
                    $wire.nextChunk(event.orientation ?? 'horizontal');
                }
            });
        })
    </script>
@endscript
