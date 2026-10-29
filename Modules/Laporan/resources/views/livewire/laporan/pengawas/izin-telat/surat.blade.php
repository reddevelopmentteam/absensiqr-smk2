@assets
    <style>
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
                class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Cetak Ulang</button>
            <button type="button" id="btnDirectPrint"
                class="rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700 disabled:opacity-50">Cetak
                Langsung</button>
        </div>
        <span id="printStatus" class="text-sm text-gray-600"></span>
    </div>

    <div id="printArea" class="w-[58mm] max-w-[58mm] rounded bg-white">

        <div class="w-[58mm] px-1 py-3">

            <h1 class="mt-2 mb-3 text-center text-[16px] font-bold uppercase">
                Surat Izin Telat
            </h1>

            <div class="flex flex-col space-y-2">

                <div class="text-[16px]">
                    <p class="font-semibold">Nama :</p>
                    <p>{{ $absen->murid->nama }}</p>
                </div>

                <div class="text-[16px]">
                    <p class="font-semibold">Alasan Terlambat :</p>
                    <p class="whitespace-pre-line break-words">{{ $absen->keterangan }}</p>
                </div>

            </div>

            <div class="my-4 border border-dotted"></div>

            <div class="flex justify-end">
                <div class="w-28 text-center text-[16px]">

                    <p class="font-semibold mb-1">
                        {{ now()->locale('id')->locale('id')->translatedFormat('d F Y') }}<br>
                        {{ now()->locale('id')->translatedFormat('H:i:s') }} WITA
                    </p>
                    <p class="font-semibold">
                        Pengawas
                    </p>

                    <div class="h-16"></div>

                    <div class="border-t border-black pt-1 pb-2">
                        {{ auth()->user()->name }}
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>

@script
    <script type="module">
        Promise.all([
            import('{{ Vite::asset('resources/js/thermal/escpos.js') }}'),
            import('{{ Vite::asset('resources/js/thermal/transport-usb.js') }}'),
            import('{{ Vite::asset('resources/js/thermal/transport-bt.js') }}'),
            import('{{ Vite::asset('resources/js/thermal/transport-native.js') }}'),
            import('{{ Vite::asset('resources/js/thermal/surat-payload.js') }}'),
        ]).then(() => {
            // Jalur cetak langsung ESC/POS — hanya on-click (wajib user gesture).
            // Urutan: aplikasi Android (native SPP) > WebUSB > WebBluetooth.
            const btn = document.getElementById('btnDirectPrint');
            const status = document.getElementById('printStatus');
            const say = (msg) => {
                if (status) status.textContent = msg;
            };
            const hasNative = window.ThermalNative && window.ThermalNative.isSupported();
            const supported = hasNative || 'usb' in navigator || 'bluetooth' in navigator;
            if (!supported) {
                if (btn) btn.disabled = true;
                say('Browser tidak mendukung cetak langsung — gunakan Cetak Ulang.');
            }
            if (btn && supported) {
                btn.addEventListener('click', async () => {
                    try {
                        btn.disabled = true;
                        say('Menghubungkan…');
                        const payload = window.SuratPayload.buildIzinTelatBytes({
                            nama: @js($absen->murid->nama),
                            alasan: @js($absen->keterangan),
                            tanggal: @js(now()->locale('id')->translatedFormat('d F Y')),
                            jam: @js(now()->locale('id')->translatedFormat('H:i:s')),
                            pengawas: @js(auth()->user()->name),
                        });
                        if (hasNative) {
                            await window.ThermalNative.sendBytes(payload);
                        } else if ('usb' in navigator) {
                            await window.ThermalUsb.sendBytes(payload);
                        } else {
                            await window.ThermalBt.sendBytes(payload);
                        }
                        say('Tercetak.');
                        Livewire.navigate(@js(route('cetak-izin-telat')));
                    } catch (e) {
                        say('Gagal: ' + (e?.message ?? e) + ' — gunakan Cetak Ulang.');
                        btn.disabled = false;
                    }
                });
            }
        });
    </script>
    <script>
        // Dialog print otomatis untuk browser desktop. Tanpa navigate otomatis
        // supaya halaman tidak kabur sendiri di WebView Android (window.print()
        // di sana tidak membuka dialog). Navigasi balik tetap terjadi setelah
        // tombol Cetak Langsung / Cetak Ulang ditekan.
        if (@js($siapCetak) && typeof window.ThermalNative === 'undefined' && typeof window.ThermalPrinter ===
            'undefined') {
            requestAnimationFrame(() => {
                window.print();
            });
        }
    </script>
@endscript
