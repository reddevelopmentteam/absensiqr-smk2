@assets
    <style>
        @media print {
            @page {
                size: 58mm auto;
                margin: 0;
            }

            html,
            body {
                width: 58mm !important;
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
                width: 58mm !important;
                max-width: 58mm !important;
                margin: 0 auto !important;
                padding: 0 !important;
                display: flex !important;
                justify-content: center !important;
                box-sizing: border-box !important;
                background: #fff !important;
                border: none !important;
                box-shadow: none !important;
                font-size: 14px;
                line-height: 1.2;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            /* Lebar cetak thermal 58mm, dipusatkan supaya sisa kertas kiri-kanan seimbang */
            #printArea>div {
                width: 56mm !important;
                max-width: 58mm !important;
                margin: 0 auto !important;
                padding: 2mm 1mm !important;
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

            #qrcode {
                width: 26mm !important;
                height: 26mm !important;
                display: block !important;
                margin-left: auto !important;
                margin-right: auto !important;
                object-fit: contain;
            }

            button {
                display: none !important;
            }
        }
    </style>
@endassets

<div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 p-2">
    <button type="button" onclick="window.print()"
        class="mb-5 rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Cetak Ulang</button>

    <div id="printArea" class="w-[58mm] max-w-[58mm] rounded bg-white">

        <div class="w-full p-3">

            <h1 class="mb-3 text-center text-sm font-bold uppercase">
                Surat Izin Telat
            </h1>

            <div class="flex flex-col space-y-2">

                <div class="text-sm">
                    <p class="font-semibold">Nama :</p>
                    <p>{{ $absen->murid->nama }}</p>
                </div>

                <div class="text-sm">
                    <p class="font-semibold">Alasan Terlambat :</p>
                    <p class="whitespace-pre-line break-words">{{ $absen->keterangan }}</p>
                </div>

            </div>

            <div class="my-4 border border-dotted"></div>

            <div class="flex justify-end">
                <div class="w-28 text-center text-sm">

                    <p class="font-semibold mb-1">
                        {{ now()->locale('id')->locale('id')->translatedFormat('d F Y') }}<br>
                        {{ now()->locale('id')->translatedFormat('H:i:s') }} WITA
                    </p>
                    <p class="font-semibold">
                        Pengawas
                    </p>

                    <div class="h-16"></div>

                    <div class="border-t border-black pt-1">
                        {{ auth()->user()->name }}
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>

@script
    <script>
        if (@js($siapCetak)) {
            requestAnimationFrame(() => {
                window.print();
                Livewire.navigate(@js(route('cetak-izin-telat')));
            });
        }
    </script>
@endscript
