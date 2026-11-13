<div class="space-y-6">

    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-main via-blue-deep to-[#07162f] p-6 shadow-lg">
        <div class="absolute -top-10 -right-10 h-44 w-44 rounded-full bg-white/10 blur-2xl"></div>
        <div class="absolute bottom-0 left-0 h-36 w-36 rounded-full bg-cyan-300/10 blur-2xl"></div>

        <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="hidden h-16 w-16 items-center justify-center rounded-2xl border border-white/10 bg-white/10 backdrop-blur sm:flex">
                    <iconify-icon icon="solar:face-scan-circle-bold" width="34" height="34" class="text-white"></iconify-icon>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Face Recognition</h1>
                    <p class="mt-1 text-sm text-blue-100">
                        Kenali murid melalui kamera dengan membandingkan wajahnya ke foto pada data murid.
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-white/15 bg-white/10 px-5 py-4 backdrop-blur">
                <p class="text-xs uppercase tracking-[0.2em] text-blue-100">Mode</p>
                <h2 class="mt-1 text-lg font-bold text-white">Absensi Otomatis</h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Pengenalan Wajah Langsung</h2>
                        <p class="mt-1 text-sm text-gray-500">Pengenalan berjalan langsung di browser dan mencocokkan kamera dengan foto pada data murid.</p>
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-4 py-1.5 text-xs font-semibold text-blue-700">
                        <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                        Pengenalan di browser
                    </div>
                </div>

                <div
                    x-data="faceRecognitionScanner(@js($students), @js(route('scan-camera-status')))"
                    x-init="$nextTick(() => watchSchedule())"
                    x-on:livewire:navigating.window="stopScheduleWatch(); stopCamera()"
                    x-on:face-attendance-result.window="playAttendanceAudio($event.detail.status)"
                    class="space-y-5">
                    <div class="space-y-4 text-center">
                        <div class="relative flex min-h-64 w-full items-center justify-center overflow-hidden rounded-2xl bg-slate-900">
                            <video x-ref="cameraVideo" x-show="cameraReady" autoplay muted playsinline class="block max-h-[70vh] min-h-64 w-full object-contain"></video>
                            <canvas x-ref="overlay" x-show="cameraReady" class="pointer-events-none absolute inset-0 h-full w-full object-contain"></canvas>
                            <div x-show="!cameraReady" class="p-8 text-center text-white">
                                <iconify-icon icon="solar:camera-add-bold" width="42" height="42"></iconify-icon>
                                <p class="mt-3 text-sm" x-text="scheduleOpen ? 'Kamera siap dimulai. Izinkan akses kamera jika diminta.' : 'Kamera otomatis aktif pada jadwal absensi.'"></p>
                            </div>
                        </div>
                        <p x-show="status" x-text="status" class="mt-3 text-sm font-medium text-blue-700" aria-live="polite"></p>
                        <p x-show="cameraError" x-text="cameraError" class="mt-3 text-sm text-rose-600" role="alert"></p>
                        <div class="mt-5 flex flex-wrap justify-center gap-3">
                            <button type="button" x-show="scheduleOpen && !scanning" x-on:click="startCamera()" x-bind:disabled="busy"
                                class="inline-flex items-center gap-2 rounded-2xl bg-blue-deep-solid px-5 py-3 text-sm font-semibold text-white hover:bg-blue-deep disabled:opacity-60">
                                <iconify-icon icon="solar:camera-bold" width="19" height="19"></iconify-icon> Mulai Face Recognition
                            </button>
                            <button type="button" x-show="scanning" x-on:click="stopCamera()" class="inline-flex items-center gap-2 rounded-2xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                                Hentikan Pengenalan
                            </button>
                            <button type="button" x-show="scanning" x-on:click="enableAudio()" class="inline-flex items-center gap-2 rounded-2xl border border-blue-200 bg-white px-5 py-3 text-sm font-semibold text-blue-700 hover:bg-blue-50">
                                <iconify-icon icon="solar:volume-loud-bold" width="19" height="19"></iconify-icon> Aktifkan Suara Absensi
                            </button>
                        </div>
                    </div>

                    @if ($message !== '')
                        @php
                            $messageStyles = [
                                'success' => 'border-green-200 bg-green-50 text-green-700',
                                'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
                                'error' => 'border-rose-200 bg-rose-50 text-rose-700',
                                'info' => 'border-blue-100 bg-blue-50 text-blue-700',
                            ];
                        @endphp
                        <div class="rounded-2xl border px-4 py-3 text-sm {{ $messageStyles[$messageType] ?? $messageStyles['info'] }}">
                            {{ $message }}
                        </div>
                    @endif

                </div>
            </div>

            <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h2 class="text-xl font-bold text-gray-800">Murid Terdeteksi</h2>
                    <p class="mt-1 text-sm text-gray-500">Kecocokan di atas 45% akan langsung diproses sebagai absensi masuk atau pulang sesuai jadwal.</p>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse ($candidates as $candidate)
                        <div wire:key="candidate-{{ $candidate['id'] }}" class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-center gap-4">
                                <div class="relative flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-blue-100 text-lg font-bold text-blue-700">
                                    <span>{{ mb_strtoupper(mb_substr((string) $candidate['nama'], 0, 1, 'UTF-8'), 'UTF-8') }}</span>
                                    @if (! empty($candidate['image_path']))
                                        <img src="{{ $candidate['image_url'] }}" alt="" loading="lazy" decoding="async"
                                            class="absolute inset-0 h-full w-full object-cover" onerror="this.style.display='none'">
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <h3 class="truncate font-bold capitalize text-gray-800">{{ $candidate['nama'] }}</h3>
                                    <p class="mt-1 text-xs text-gray-500">{{ $candidate['nipd'] ?: '-' }} / {{ $candidate['nisn'] ?: '-' }} · {{ $candidate['rombel'] ?? 'Tanpa rombel' }}</p>
                                    <span class="mt-2 inline-flex rounded-full bg-green-100 px-3 py-1 text-[11px] font-semibold text-green-600">
                                        Kecocokan {{ number_format((float) $candidate['score'], 1) }}%
                                    </span>
                                </div>
                            </div>

                        </div>
                    @empty
                        <div class="px-6 py-14 text-center">
                            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-gray-100 text-gray-400">
                                <iconify-icon icon="solar:face-scan-circle-outline" width="36" height="36"></iconify-icon>
                            </div>
                            <h3 class="mt-5 text-lg font-bold text-gray-700">Belum ada murid terdeteksi</h3>
                            <p class="mt-2 text-sm text-gray-500">Murid akan tercatat otomatis setelah wajah cocok di atas 45%.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Hasil Absensi Otomatis</h2>
                        <p class="mt-1 text-sm text-gray-500">Jenis masuk atau pulang mengikuti jadwal absensi yang berlaku.</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <iconify-icon icon="solar:user-check-bold" width="24" height="24"></iconify-icon>
                    </div>
                </div>

                @if ($lastResult)
                    @php
                        $murid = $lastResult['murid'];
                        $absen = $lastResult['absen'];
                        $badge = match ($lastResult['type']) {
                            'success' => 'bg-green-100 text-green-600',
                            'message' => 'bg-blue-100 text-blue-600',
                            default => 'bg-rose-100 text-rose-600',
                        };
                    @endphp
                    <div class="space-y-4">
                        <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-gray-50 p-4">
                            <div class="relative flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-blue-100 text-lg font-bold text-blue-700">
                                <span>{{ mb_strtoupper(mb_substr((string) $murid->nama, 0, 1, 'UTF-8'), 'UTF-8') }}</span>
                                @if ($murid->image_path)
                                    <img src="{{ $lastResult['murid_image_url'] ?? $murid->image_path }}" alt="" loading="lazy" decoding="async"
                                        class="absolute inset-0 h-full w-full object-cover" onerror="this.style.display='none'">
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold text-gray-800">{{ $murid->nama }}</p>
                                <p class="mt-1 text-sm text-gray-600">{{ $murid->nipd ?: '-' }} / {{ $murid->nisn ?: '-' }}</p>
                                <p class="text-sm text-gray-600">{{ $murid->rombel?->nama_lengkap ?? 'Tanpa rombel' }}</p>
                            </div>
                        </div>

                        <div class="rounded-2xl border px-4 py-3 text-sm {{ $badge }}">
                            {{ $lastResult['message'] ?? 'Proses absensi selesai.' }}
                        </div>

                        @if ($absen)
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="rounded-2xl bg-gray-50 p-3">
                                    <p class="text-xs text-gray-500">Status</p>
                                    <p class="mt-1 font-semibold text-gray-800">{{ $absen->status }}</p>
                                </div>
                                <div class="rounded-2xl bg-gray-50 p-3">
                                    <p class="text-xs text-gray-500">Tanggal</p>
                                    <p class="mt-1 font-semibold text-gray-800">{{ $absen->tanggal?->format('d M Y') ?? '-' }}</p>
                                </div>
                                <div class="rounded-2xl bg-gray-50 p-3">
                                    <p class="text-xs text-gray-500">Masuk</p>
                                    <p class="mt-1 font-semibold text-gray-800">{{ $absen->waktu_masuk ?? '-' }}</p>
                                </div>
                                <div class="rounded-2xl bg-gray-50 p-3">
                                    <p class="text-xs text-gray-500">Pulang</p>
                                    <p class="mt-1 font-semibold text-gray-800">{{ $absen->waktu_keluar ?? '-' }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
                        Belum ada hasil absensi.
                    </div>
                @endif
            </div>

            <div class="rounded-3xl border border-blue-100 bg-blue-50 p-5">
                <h3 class="font-bold text-blue-800">QR dan hardware scanner tetap tersedia</h3>
                <p class="mt-2 text-sm text-blue-700">Gunakan metode tersebut jika kamera tidak dapat mengenali wajah dengan jelas.</p>
            </div>
        </div>
    </div>
</div>
