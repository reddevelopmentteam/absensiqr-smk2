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
                font-size: 14px;
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
                width: 58mm !important;
                height: 58mm !important;
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
        class="mb-5 rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Print</button>

    <div id="printArea" class="w-[58mm] max-w-[58mm] rounded bg-white">

        <div class="w-full p-3">
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
        ]).then(async () => {

            const qrCode = @json($qrCode);

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
                Livewire.navigate('{{ route('laporan-izin-keluar') }}');
            };

        });
    </script>
@endscript
