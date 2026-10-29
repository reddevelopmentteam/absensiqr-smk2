@assets
    <style>
        /* QR pratinjau: penuhi lebar struk, tetap tajam saat diperbesar */
        #qrcode {
            width: 100%;
            height: auto;
            aspect-ratio: 1 / 1;
            image-rendering: pixelated;
        }

        @media print {
            @page {
                size: 61mm auto;
                margin: 0;
            }

            html,
            body {
                width: 61mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            body {
                display: block !important;
            }

            /* Sembunyikan seluruh halaman */
            body * {
                visibility: hidden;
            }

            /* Tampilkan hanya receipt */
            #printArea,
            #printArea * {
                visibility: visible;
            }

            #printArea {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                width: 61mm !important;
                max-width: 61mm !important;
                margin: 0 auto !important;
                padding: 0 !important;
                display: flex !important;
                justify-content: center !important;
                box-sizing: border-box !important;
                background: #fff !important;
                border: none !important;
                box-shadow: none !important;
                font-size: 16px;
                line-height: 1.2;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            /* Lebar cetak 61mm (+3mm kompensasi penyusutan aplikasi), dipusatkan supaya sisa kertas kiri-kanan seimbang */
            #printArea>div {
                width: 61mm !important;
                max-width: 61mm !important;
                margin: 0 auto !important;
                padding: 1mm 0mm !important;
                box-sizing: border-box !important;
            }

            #printArea table {
                width: 100%;
                table-layout: fixed;
                border-collapse: collapse;
            }

            #printArea td {
                vertical-align: top;
                white-space: normal;
                word-break: break-word;
                overflow-wrap: anywhere;
            }

            /* QR */
            #qrcode {
                width: 61mm !important;
                height: 61mm !important;
                display: block !important;
                object-fit: contain;
                image-rendering: pixelated;
            }

            button {
                display: none !important;
            }
        }
    </style>
@endassets

<div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 p-2">
    <div class="mb-5 flex flex-col items-center gap-2">
        <div class="flex gap-2">
            <button type="button" onclick="window.print()"
                class="rounded bg-blue-600 transition-all duration-150 hover:bg-blue-700 active:scale-95 px-4 py-2 text-white ">Print</button>
            <button type="button" id="btnDirectPrint"
                class="rounded bg-green-600 transition-all duration-150 hover:bg-green-700 active:scale-95 px-4 py-2 text-white disabled:opacity-50">Cetak
                Langsung</button>
        </div>
        <span id="printStatus" class="text-sm text-gray-600"></span>
    </div>

    <div id="printArea" class="w-[58mm] max-w-[58mm] rounded bg-white">

        <div class="w-full px-0 py-3">
            <div class="flex w-full flex-col items-center gap-3">
                <h1 class="text-center text-lg font-bold uppercase">
                    Surat Izin Keluar
                </h1>
                <img id="qrcode" class="object-contain" alt="QR Code Surat Izin Keluar">
            </div>
        </div>

    </div>
</div>

@script
    <script type="module">
        Promise.all([
            import('{{ Vite::asset('resources/js/generateQR.js') }}'),
            import('{{ Vite::asset('resources/js/thermal/escpos.js') }}'),
            import('{{ Vite::asset('resources/js/thermal/transport-usb.js') }}'),
            import('{{ Vite::asset('resources/js/thermal/transport-bt.js') }}'),
            import('{{ Vite::asset('resources/js/thermal/transport-native.js') }}'),
            import('{{ Vite::asset('resources/js/thermal/surat-payload.js') }}'),
        ]).then(async () => {

            const qrCode = @json($qrCode);

            // Jalur cetak langsung ESC/POS — hanya on-click (wajib user gesture).
            // Urutan: aplikasi Android (native SPP) > WebUSB > WebBluetooth.
            // Didaftarkan dulu supaya tetap hidup walau QR PNG gagal dimuat.
            const btn = document.getElementById('btnDirectPrint');
            const status = document.getElementById('printStatus');
            const say = (msg) => {
                if (status) status.textContent = msg;
            };
            const hasNative = window.ThermalNative && window.ThermalNative.isSupported();
            const supported = hasNative || 'usb' in navigator || 'bluetooth' in navigator;
            if (!supported) {
                if (btn) btn.disabled = true;
                say('Browser tidak mendukung cetak langsung — gunakan Print.');
            }
            if (btn && supported) {
                btn.addEventListener('click', async () => {
                    try {
                        btn.disabled = true;
                        say('Menghubungkan…');
                        const payload = window.SuratPayload.buildIzinKeluarBytes(qrCode);
                        if (hasNative) {
                            await window.ThermalNative.sendBytes(payload);
                        } else if ('usb' in navigator) {
                            await window.ThermalUsb.sendBytes(payload);
                        } else {
                            await window.ThermalBt.sendBytes(payload);
                        }
                        say('Tercetak.');
                        Livewire.navigate('{{ route('laporan-izin-keluar') }}');
                    } catch (e) {
                        say('Gagal: ' + (e?.message ?? e) + ' — gunakan Print.');
                        btn.disabled = false;
                    }
                });
            }

            // QR PNG untuk jalur lama (window.print). Best-effort: QR native
            // cetak langsung tidak butuh WASM/PNG. Tanpa navigate otomatis
            // supaya tombol sempat diklik — wajib di aplikasi Android karena
            // window.print() di WebView tidak membuka dialog.
            try {
                const bytes = await generateQRPNG(qrCode);

                const blob = new Blob([bytes], {
                    type: 'image/png'
                });

                const url = URL.createObjectURL(blob);

                const img = document.getElementById('qrcode');

                img.src = url;

                img.onload = () => {
                    URL.revokeObjectURL(url);

                    window.print();
                };

                img.onerror = () => {
                    URL.revokeObjectURL(url);
                };
            } catch (e) {
                say('QR gambar gagal dimuat — cetak langsung tetap bisa dipakai.');
            }

        });
    </script>
@endscript
