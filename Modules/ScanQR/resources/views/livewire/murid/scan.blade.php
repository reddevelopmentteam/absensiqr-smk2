<div class="flex min-h-dvh flex-col bg-blue-dark font-sans" data-browser-refresh-via-ping>
    @php
        $serverPingUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'scan-qrcode.ping',
            now()->addMinutes(10),
            [],
            false,
        );
    @endphp
    <header
        class="sticky top-0 z-30 border-b border-white/10 bg-slate-950/80 px-4 py-3 shadow-2xl backdrop-blur-xl sm:px-6">

        <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-3">

            <!-- brand -->
            <div class="flex min-w-0 items-center gap-3">

                <img src="{{ asset('assets/img/logo_smkn_2.png') }}" class="w-9 shrink-0 sm:w-10"
                    alt="Logo SMKN 2 Banjarmasin">

                <div class="min-w-0">
                    <h1 class="truncate text-sm font-bold uppercase tracking-wide text-white">
                        Absensi QR
                    </h1>

                    <p class="truncate text-[10px] uppercase tracking-[0.25em] text-slate-400 sm:text-[11px]">
                        SMKN 2 Banjarmasin
                    </p>
                </div>

            </div>

            <!-- clock + connection: visible on all sizes, compact on mobile -->
            <div class="flex shrink-0 items-center gap-2">
                <p id="server-status"
                    class="hidden items-center gap-1.5 rounded-full border border-red-400/30 bg-red-500/10 px-3 py-1.5 text-[11px] font-semibold text-red-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>
                    <span class="hidden sm:inline">Tidak terhubung ke server</span>
                    <span class="sm:hidden">Offline</span>
                </p>

                <div
                    class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-xs font-semibold backdrop-blur sm:px-5 sm:py-2 sm:text-sm">

                    <div class="h-2 w-2 shrink-0 animate-pulse rounded-full bg-emerald-400"></div>
                    <span class="hidden text-white lg:inline">
                        {{ $dateNow ?? 'Tanggal sekarang' }},
                    </span>
                    <span class="text-white tabular-nums" id="clock">
                        {{ now()->format('H:i:s') ?? 'Waktu sekarang' }}
                    </span>

                </div>

            </div>

        </div>

    </header>

    @include('components.scan-floating-actions')

    <main
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col items-center justify-center gap-4 px-4 py-6 sm:px-6 lg:flex-row lg:items-stretch lg:gap-8">
        <!-- SCANNER AREA -->
        <div class="w-full max-w-xl lg:max-w-2xl">

            <!-- scanner card -->
            <div
                class="relative overflow-hidden rounded-[1.5rem] border border-white/10 bg-white/10 p-3 shadow-2xl backdrop-blur-xl sm:rounded-[2rem] sm:p-4">
                <!-- reader wrapper: portrait-friendly on phones, square on larger screens -->
                <div
                    class="relative aspect-[3/4] w-full overflow-hidden rounded-[1.1rem] bg-black shadow-2xl sm:rounded-[1.6rem] min-[480px]:aspect-square">

                    <!-- corner frame -->
                    <div class="pointer-events-none absolute inset-0 z-20">
                        <div
                            class="absolute left-3 top-3 h-10 w-10 rounded-tl-2xl border-l-4 border-t-4 border-blue-400 sm:left-4 sm:top-4 sm:h-12 sm:w-12">
                        </div>
                        <div
                            class="absolute right-3 top-3 h-10 w-10 rounded-tr-2xl border-r-4 border-t-4 border-blue-400 sm:right-4 sm:top-4 sm:h-12 sm:w-12">
                        </div>
                        <div
                            class="absolute bottom-3 left-3 h-10 w-10 rounded-bl-2xl border-b-4 border-l-4 border-blue-400 sm:bottom-4 sm:left-4 sm:h-12 sm:w-12">
                        </div>
                        <div
                            class="absolute bottom-3 right-3 h-10 w-10 rounded-br-2xl border-b-4 border-r-4 border-blue-400 sm:bottom-4 sm:right-4 sm:h-12 sm:w-12">
                        </div>
                    </div>

                    <!-- center helper -->
                    <div
                        class="pointer-events-none absolute left-1/2 top-1/2 z-20 h-[58%] w-[58%] -translate-x-1/2 -translate-y-1/2 rounded-3xl border border-blue-400/40 bg-blue-400/5">
                    </div>

                    <!-- scan line -->
                    <div
                        class="scan-line pointer-events-none absolute left-[22%] top-1/2 z-20 h-0.5 w-[56%] -translate-y-1/2 rounded-full bg-blue-400 shadow-[0_0_25px_rgba(96,165,250,0.9)]">
                    </div>

                    <!-- camera -->
                    <div wire:ignore id="reader"
                        class="min-h-full overflow-hidden rounded-[1.1rem] bg-black text-white sm:rounded-[1.6rem]">
                    </div>

                </div>

            </div>

        </div>

        <!-- SIDE PANEL: instructions (stacked below scanner on mobile, side card on desktop) -->
        <aside class="w-full max-w-xl lg:flex lg:max-w-sm lg:flex-col lg:justify-center">
            <div
                class="rounded-[1.5rem] border border-white/10 bg-white/10 p-5 text-center shadow-2xl backdrop-blur-xl sm:p-6 lg:text-left">
                <div class="flex items-center justify-center gap-3 lg:justify-start">
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-500/20 text-blue-300">
                        <iconify-icon icon="solar:qr-code-bold" width="24" height="24"
                            aria-hidden="true"></iconify-icon>
                    </span>
                    <h2 class="text-base font-bold text-white sm:text-lg">
                        Pindai QR Kehadiran
                    </h2>
                </div>

                <ol class="mt-4 space-y-3 text-left text-sm text-slate-300">
                    <li class="flex gap-3">
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-500/20 text-xs font-bold text-blue-200">1</span>
                        <span>Posisikan QR Code di tengah kotak scanner.</span>
                    </li>
                    <li class="flex gap-3">
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-500/20 text-xs font-bold text-blue-200">2</span>
                        <span>Pastikan kode terlihat jelas dan tidak buram.</span>
                    </li>
                    <li class="flex gap-3">
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-500/20 text-xs font-bold text-blue-200">3</span>
                        <span>Hasil absensi muncul otomatis setelah QR terbaca.</span>
                    </li>
                </ol>

                <div
                    class="mt-4 flex items-center justify-center gap-2 rounded-2xl bg-white/5 px-4 py-3 text-xs text-slate-400 lg:justify-start">
                    <iconify-icon icon="solar:shield-check-linear" width="18" height="18" aria-hidden="true"
                        class="shrink-0 text-emerald-400"></iconify-icon>
                    <span>Kamera hanya dipakai untuk memindai, tidak merekam.</span>
                </div>
            </div>
        </aside>
    </main>

    <div wire:ignore data-scan-transport-host></div>
    @include('scanqr::components.modal.success')
    @include('scanqr::components.modal.message')
    @include('scanqr::components.modal.error')
    @include('scanqr::components.modal.transport-error')

</div>

@script
    <script>
        const serverPingUrl = @json($serverPingUrl);
        let browserRefreshVersion = @json($siteSettings['system.browser_refresh_version'] ?? '');
        const connectionCheckInterval = 5000;
        const connectionTimeout = 4000;
        const serverTimeAtLoad = @json(now()->timestamp * 1000);
        const clientTimeAtLoad = Date.now();
        const clockFormatter = new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        });

        const lifecycle = new AbortController();
        let disposed = false;
        let scanAudio;
        let scanResults;
        const listen = (target, event, handler) => target.addEventListener(event, handler, {
            signal: lifecycle.signal
        });
        $wire.__instance.addCleanup(() => {
            disposed = true;
            lifecycle.abort();
            clearInterval(clockTimer);
            clearTimeout(connectionCheckTimer);
            connectionRequestController?.abort();
            scanResults?.dispose();
            window.destroyScanner?.();
            window.resetScannerQrLock?.();
            window.scanned = false;
        });
        let isServerConnected = true;
        let connectionCheckTimer = null;
        let connectionRequestController = null;
        let connectionRetryAt = 0;
        let clockTimer = null;

        function renderServerStatus() {
            const statusElement = document.getElementById('server-status');

            if (!statusElement) {
                return;
            }

            // Pill uses flex layout; toggle both classes so show/hide keeps alignment.
            statusElement.classList.toggle('hidden', isServerConnected);
            statusElement.classList.toggle('inline-flex', !isServerConnected);
        }

        function updateClock() {
            const clockElement = document.getElementById('clock');

            if (clockElement) {
                const elapsedTime = Date.now() - clientTimeAtLoad;
                clockElement.textContent = clockFormatter.format(
                    new Date(serverTimeAtLoad + elapsedTime),
                );
            }
        }

        function startClock() {
            clearInterval(clockTimer);
            updateClock();
            clockTimer = setInterval(updateClock, 1000);
        }

        function scheduleServerCheck(delay = connectionCheckInterval) {
            clearTimeout(connectionCheckTimer);

            if (disposed || document.hidden || connectionRequestController) {
                return;
            }

            connectionCheckTimer = setTimeout(checkServerConnection, Math.max(delay, connectionRetryAt - Date.now()));
        }

        async function checkServerConnection() {
            connectionCheckTimer = null;

            if (disposed || document.hidden || connectionRequestController) {
                return;
            }

            if (Date.now() < connectionRetryAt) {
                scheduleServerCheck();
                return;
            }

            if (!navigator.onLine) {
                isServerConnected = false;
                renderServerStatus();
                return;
            }

            const checkStartedAt = performance.now();
            connectionRequestController = new AbortController();
            const timeout = setTimeout(
                () => connectionRequestController?.abort(),
                connectionTimeout,
            );

            try {
                const response = await fetch(serverPingUrl, {
                    method: 'HEAD',
                    cache: 'no-store',
                    credentials: 'same-origin',
                    signal: connectionRequestController.signal,
                });

                if (response.status === 403) {
                    window.location.reload();
                    return;
                }

                if (response.status === 429) {
                    const seconds = Number(response.headers.get('Retry-After'));
                    connectionRetryAt = Date.now() + (Number.isFinite(seconds) && seconds > 0 ? seconds : 60) * 1000;
                    isServerConnected = true;
                    return;
                }

                const refreshVersion = response.headers.get('X-Browser-Refresh-Version');
                if (response.ok && refreshVersion && refreshVersion !== browserRefreshVersion) {
                    window.location.reload();
                    return;
                }
                browserRefreshVersion = refreshVersion || browserRefreshVersion;

                isServerConnected = response.ok;
            } catch {
                isServerConnected = false;
            } finally {
                clearTimeout(timeout);
                connectionRequestController = null;
                renderServerStatus();

                const elapsedTime = performance.now() - checkStartedAt;
                scheduleServerCheck(Math.max(0, connectionCheckInterval - elapsedTime));
            }
        }

        function handleVisibilityChange() {
            if (document.hidden) {
                clearInterval(clockTimer);
                clearTimeout(connectionCheckTimer);
                connectionRequestController?.abort();
                return;
            }

            startClock();
            scheduleServerCheck(0);
        }

        listen(window, 'offline', () => {
            clearTimeout(connectionCheckTimer);
            connectionRequestController?.abort();
            isServerConnected = false;
            renderServerStatus();
        });

        listen(window, 'online', () => scheduleServerCheck(0));
        listen(document, 'visibilitychange', handleVisibilityChange);

        startClock();
        scheduleServerCheck(0);


        import('{{ Vite::asset('Modules/ScanQR/resources/assets/js/scanner.js') }}')
            .then(({
                createScanAudio,
                createScanResultController
            }) => {
                if (disposed) return;
                const root = $wire.$el;
                scanAudio = createScanAudio();
                const pause = () => {
                    window.scanned = true;
                };
                scanResults = createScanResultController({
                    audio: scanAudio,
                    pause,
                    resume: () => {
                        // Allow the same stationary QR again after each modal closes.
                        window.resetScannerQrLock();
                        window.scanned = false;
                        window.initScanner();
                    },
                    closeBackend: async result => {
                        if (result.transport) {
                            root.querySelector('[data-scan-transport-host]').replaceChildren();
                        } else {
                            try {
                                await $wire.closeModal(result.id);
                            } finally {
                                root.querySelector('[data-scan-transport-host]').replaceChildren();
                            }
                        }
                    },
                });
                // Kiosk browsers with autoplay permission can start without a gesture.
                void scanAudio.preloadScanAudios();
                const preload = () => {
                    void scanAudio.preloadScanAudios();
                };
                listen(document, 'pointerdown', preload);
                listen(document, 'keydown', preload);
                const setField = (element, name, value) => {
                    const field = element.querySelector(`[data-scan-field="${name}"]`);
                    if (field) field.textContent = value || '-';
                };
                const renderScanModal = result => {
                    const modal = result.modal || {};
                    const template = root.querySelector(
                        `[data-scan-modal-template="${modal.type || 'error'}"]`,
                    );
                    const element = template?.content.firstElementChild.cloneNode(true);

                    if (!element) return null;

                    const successNotice = element.querySelector('[data-scan-success-notice]');
                    if (successNotice && result.status === 'late') {
                        successNotice.classList.remove('border-green-200', 'bg-green-50', 'text-green-800');
                        successNotice.classList.add('border-yellow-200', 'bg-yellow-50', 'text-yellow-800');
                        element.querySelector('[data-scan-field="message"]')?.classList.add('hidden');
                        element.querySelector('[data-scan-late-message]')?.classList.remove('hidden');
                    }

                    const murid = modal.murid || {};
                    const jadwal = modal.jadwal || {};
                    element.dataset.scanResult = JSON.stringify(result);
                    for (const [name, value] of Object.entries({
                            message: modal.message,
                            title: modal.title,
                            ...murid,
                            'jadwal-label': jadwal.label,
                            'jadwal-nama-acara': jadwal.nama_acara,
                            'jadwal-keterangan': jadwal.keterangan,
                            lahir: [murid.tempatLahir, murid.tanggalLahir].filter(Boolean).join(', '),
                        })) setField(element, name, value);

                    const avatarInitial = element.querySelector('[data-scan-avatar-initial]');
                    if (avatarInitial) avatarInitial.textContent = (murid.nama || '-').trim().charAt(0)
                    .toUpperCase();
                    const avatarImage = element.querySelector('[data-scan-avatar-image]');
                    if (avatarImage && murid.imagePath) {
                        avatarImage.src = murid.imagePath;
                        avatarImage.classList.remove('hidden');
                    }

                    const schedule = element.querySelector('[data-scan-schedule]');
                    if (schedule && Object.keys(jadwal).length) {
                        schedule.classList.remove('hidden');
                        const time = [jadwal.jam_masuk, jadwal.jam_pulang].filter(Boolean).join(' - ');
                        if (time) element.querySelector('[data-scan-schedule-time]').textContent = ` · ${time}`;
                    }
                    const muridDetail = element.querySelector('[data-scan-murid]');
                    if (muridDetail && modal.murid) muridDetail.classList.remove('hidden');
                    const jadwalDetail = element.querySelector('[data-scan-jadwal]');
                    if (jadwalDetail && Object.keys(jadwal).length) {
                        jadwalDetail.classList.remove('hidden');
                        for (const name of ['jadwal-nama-acara', 'jadwal-keterangan']) {
                            if (jadwal[name.replace('jadwal-', '').replaceAll('-', '_')]) {
                                element.querySelector(`[data-scan-field="${name}"]`).classList.remove('hidden');
                            }
                        }
                    }
                    const date = element.querySelector('[data-scan-date]');
                    if (date) date.textContent = new Intl.DateTimeFormat('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                    }).format(new Date());

                    root.querySelector('[data-scan-transport-host]').replaceChildren(element);
                    return element;
                };
                listen(root, 'scanResult', event => {
                    const result = event.detail.result;
                    const element = renderScanModal(result);
                    if (element) scanResults.openScanResultModal(result, element);
                });
                const transportFailure = () => {
                    if (disposed || root.querySelector('[data-scan-transport-host]').firstElementChild) return;
                    const result = {
                        id: crypto.randomUUID(),
                        status: 'failed',
                        autoClose: true,
                        transport: true,
                        modal: {
                            type: 'transport'
                        },
                    };
                    const element = renderScanModal(result);
                    if (element) scanResults.openScanResultModal(result, element);
                };
                for (const action of ['verifiedQRCode']) {
                    const removeInterceptor = $wire.$interceptRequest(action, ({
                        onError,
                        onFailure
                    }) => {
                        onError(({
                            preventDefault
                        }) => {
                            preventDefault();
                            transportFailure();
                        });
                        onFailure(transportFailure);
                    });
                    $wire.__instance.addCleanup(removeInterceptor);
                }
                listen(root, 'scanStarted', event => {
                    pause();
                    // Capture now, but serialize server state changes after modal close.
                    scanResults.whenClosed().then(() => {
                        if (!disposed) return $wire.verifiedQRCode(event.detail.qr);
                    }).catch(transportFailure);
                });
                // Capture close actions before Livewire sends its own duplicate request.
                root.addEventListener('click', event => {
                    const closeTarget = event.composedPath().find(element => element?.getAttribute && (
                        element.hasAttribute('data-close-scan') ||
                        element.getAttribute('wire:click') === 'closeModal' ||
                        (element.getAttribute('wire:click.self') === 'closeModal' && element === event
                            .target)
                    ));
                    if (!closeTarget) return;
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    void scanResults.closeScanResultModal();
                }, {
                    capture: true,
                    signal: lifecycle.signal
                });
                listen(document, 'keydown', event => {
                    if (event.key === 'Escape' && scanResults.isOpen()) {
                        event.preventDefault();
                        void scanResults.closeScanResultModal();
                    }
                });
                listen(document, 'livewire:navigating', () => scanResults.dispose());
                if (!scanResults.isOpen()) window.initScanner();
            });
    </script>
@endscript
