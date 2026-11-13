<div class="space-y-6">

    <!-- HEADER -->
    <div
        class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-main to-blue-deep p-6 shadow-sm">

        <!-- ornament -->
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10"></div>
        <div class="absolute bottom-0 left-0 h-32 w-32 rounded-full bg-white/5"></div>

        <div class="relative z-10">

            <div class="flex items-center gap-4">

                <div
                    class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 backdrop-blur text-white">

                    <iconify-icon
                        icon="solar:qr-code-bold"
                        width="34"
                        height="34">
                    </iconify-icon>

                </div>

                <div>
                    <h1 class="text-3xl font-bold text-white capitalize">
                        Scan Absensi
                    </h1>

                    <p class="mt-1 text-sm text-blue-100">
                        Pilih jenis absensi yang ingin dilakukan.
                    </p>
                </div>

            </div>

        </div>
    </div>

    <!-- CARD LIST -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- ABSEN MURID -->
        <a href="{{ route('scan-qrcode') }}"
            class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 text-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-xl col-span-1 lg:col-span-2">

            <!-- ornament -->
            <div
                class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 transition group-hover:scale-110">
            </div>

            <div class="relative z-10 flex h-full flex-col justify-between">

                <!-- icon -->
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 backdrop-blur">

                    <iconify-icon
                        icon="solar:user-check-bold"
                        width="34"
                        height="34">
                    </iconify-icon>

                </div>

                <!-- content -->
                <div class="mt-10">

                    <div class="flex items-center justify-between gap-3">

                        <div>
                            <h1 class="text-2xl font-bold capitalize">
                                Absen Murid
                            </h1>

                            <p class="mt-2 text-sm text-emerald-50">
                                Scan QR untuk melakukan absensi masuk atau keluar siswa.
                            </p>
                        </div>

                        <iconify-icon
                            class="transition group-hover:translate-x-1"
                            icon="solar:arrow-right-linear"
                            width="28"
                            height="28">
                        </iconify-icon>

                    </div>

                    <!-- jadwal -->
                    {{-- <div
                        class="mt-5 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm backdrop-blur">

                        <iconify-icon
                            icon="solar:clock-circle-bold"
                            width="18"
                            height="18">
                        </iconify-icon>

                        06.30 - 08.30

                    </div> --}}

                </div>

            </div>
        </a>

        @if (auth()->user()?->canAccess('scan-biometrik'))
            <a href="{{ route('scan-biometrik') }}"
                class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-violet-600 to-indigo-700 p-6 text-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-xl col-span-1 lg:col-span-2">
                <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 transition group-hover:scale-110"></div>
                <div class="relative z-10 flex h-full flex-col justify-between">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 backdrop-blur">
                        <iconify-icon icon="solar:face-scan-circle-bold" width="34" height="34"></iconify-icon>
                    </div>
                    <div class="mt-10 flex items-center justify-between gap-3">
                        <div>
                            <h1 class="text-2xl font-bold">Face Recognition</h1>
                            <p class="mt-2 text-sm text-indigo-100">Kenali wajah melalui kamera dengan foto murid di data murid, lalu konfirmasi sebelum mencatat absensi.</p>
                        </div>
                        <iconify-icon class="transition group-hover:translate-x-1" icon="solar:arrow-right-linear" width="28" height="28"></iconify-icon>
                    </div>
                </div>
            </a>
        @endif

        <!-- ABSEN HARDWARE SCANNER -->
        <a href="{{ route('scan-hardware') }}"
            class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-sky-500 to-blue-600 p-6 text-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-xl col-span-1 lg:col-span-2">

            <!-- ornament -->
            <div
                class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 transition group-hover:scale-110">
            </div>

            <div class="relative z-10 flex h-full flex-col justify-between">

                <!-- icon -->
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 backdrop-blur">

                    <iconify-icon
                        icon="solar:scanner-bold"
                        width="34"
                        height="34">
                    </iconify-icon>

                </div>

                <!-- content -->
                <div class="mt-10">

                    <div class="flex items-center justify-between gap-3">

                        <div>
                            <h1 class="text-2xl font-bold capitalize">
                                Absen Hardware Scanner
                            </h1>

                            <p class="mt-2 text-sm text-sky-50">
                                Gunakan scanner perangkat keras untuk melakukan absensi masuk atau keluar siswa.
                            </p>
                        </div>

                        <iconify-icon
                            class="transition group-hover:translate-x-1"
                            icon="solar:arrow-right-linear"
                            width="28"
                            height="28">
                        </iconify-icon>

                    </div>

                </div>

            </div>
        </a>

        <!-- ABSEN GURU -->
        {{-- <a href="{{ route('scan-qrcode') }}"
            class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-amber-500 to-orange-500 p-6 text-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-xl">

            <!-- ornament -->
            <div
                class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 transition group-hover:scale-110">
            </div>

            <div class="relative z-10 flex h-full flex-col justify-between">

                <!-- icon -->
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 backdrop-blur">

                    <iconify-icon
                        icon="solar:users-group-rounded-bold"
                        width="34"
                        height="34">
                    </iconify-icon>

                </div>

                <!-- content -->
                <div class="mt-10">

                    <div class="flex items-center justify-between gap-3">

                        <div>
                            <h1 class="text-2xl font-bold capitalize">
                                Absen Guru
                            </h1>

                            <p class="mt-2 text-sm text-amber-50">
                                Scan QR untuk melakukan absensi masuk atau keluar guru.
                            </p>
                        </div>

                        <iconify-icon
                            class="transition group-hover:translate-x-1"
                            icon="solar:arrow-right-linear"
                            width="28"
                            height="28">
                        </iconify-icon>

                    </div>
                </div>

            </div>
        </a> --}}

    </div>

</div>
