<div class="space-y-6">

    <!-- HEADER -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-main to-blue-deep p-6 shadow-lg">
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-1 -left-1 h-32 w-32 rounded-full bg-white/5"></div>

        <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div
                    class="hidden md:flex h-16 w-16 items-center justify-center rounded-2xl border border-white/10 bg-white/10 backdrop-blur">
                    <iconify-icon icon="solar:scanner-bold" width="34" height="34" class="text-white"></iconify-icon>
                </div>

                <div>
                    <h1 class="text-3xl font-bold text-white">
                        Absen Hardware Scanner
                    </h1>
                    <p class="mt-1 text-sm text-blue-100">
                        Gunakan perangkat scanner untuk mencatat absensi siswa.
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-white/15 bg-white/10 px-5 py-4 backdrop-blur">
                <p class="text-xs uppercase tracking-[0.2em] text-blue-100">
                    Total Hari Ini
                </p>
                <h2 class="mt-1 text-2xl font-bold text-white">
                    {{ $history->total() }}
                </h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <!-- SCANNER INPUT & STATUS -->
        <div class="space-y-6 xl:col-span-2">
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">
                            Status Scanner
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Pastikan kursor aktif pada kolom input sebelum melakukan scan.
                        </p>
                    </div>

                    <div class="inline-flex items-center gap-2 rounded-full bg-green-100 px-4 py-1.5 text-xs font-semibold text-green-600">
                        <span class="h-2 w-2 rounded-full bg-green-500"></span>
                        Siap
                    </div>
                </div>

                @php
                    $statusStyles = [
                        'success' => 'border-green-200 bg-green-50 text-green-700',
                        'error' => 'border-rose-200 bg-rose-50 text-rose-700',
                        'info' => 'border-blue-100 bg-blue-50 text-blue-700',
                    ];
                @endphp
                <div
                    wire:key="scanner-status-{{ md5($scannerStatus . $scannerStatusType) }}"
                    class="rounded-2xl border px-4 py-3 text-sm {{ $statusStyles[$scannerStatusType] ?? $statusStyles['info'] }}">
                    {{ $scannerStatus }}
                </div>

                <form wire:submit.prevent="submitScan" class="mt-5 space-y-4">
                    <div>
                        <label for="scan-input" class="mb-2 block text-sm font-semibold text-gray-700">
                            Input Scanner
                        </label>
                        <div class="relative">
                            <input
                                id="scan-input"
                                type="text"
                                wire:model.defer="scanInput"
                                placeholder="Arahkan scanner ke kartu atau kode siswa..."
                                autocomplete="off"
                                autofocus
                                class="w-full rounded-2xl border border-gray-300 bg-gray-50 py-3 pl-4 pr-12 text-sm transition placeholder:text-gray-400 hover:border-blue-main focus:border-blue-main focus:outline-none focus:ring-4 focus:ring-blue-100" />
                            <div class="absolute inset-y-0 right-4 flex items-center text-gray-400">
                                <iconify-icon icon="solar:scanner-linear" width="22" height="22"></iconify-icon>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">
                            Scanner hardware USB HID akan mengirim payload QR sebagai input teks diikuti Enter.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" wire:loading.attr="disabled" wire:target="submitScan"
                            class="inline-flex items-center gap-2 rounded-2xl bg-blue-deep-solid px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-deep focus:outline-none focus:ring-4 focus:ring-blue-100 disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="submitScan" class="inline-flex items-center gap-2">
                                <iconify-icon icon="solar:check-circle-linear" width="20" height="20"></iconify-icon>
                                Proses Scan
                            </span>
                            <span wire:loading wire:target="submitScan" class="inline-flex items-center gap-2">
                                <iconify-icon icon="svg-spinners:180-ring-with-bg" width="20" height="20"></iconify-icon>
                                Memproses...
                            </span>
                        </button>

                        <button type="button" wire:click="clearStatus"
                            class="inline-flex items-center gap-2 rounded-2xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition hover:border-blue-main hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-blue-100">
                            <iconify-icon icon="solar:refresh-linear" width="20" height="20"></iconify-icon>
                            Bersihkan Status
                        </button>
                    </div>
                </form>
            </div>

            <!-- RIWAYAT SCAN -->
            <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">
                                Riwayat Scan Hari Ini
                            </h2>
                            <p class="mt-1 text-sm text-gray-500">
                                Data absensi hari ini dari database ({{ now()->format('d M Y') }}).
                            </p>
                        </div>

                        <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-3 lg:w-auto">
                            <div class="relative sm:col-span-2 lg:w-72">
                                <input type="search" wire:model.live.debounce.500ms="search"
                                    placeholder="Cari nama / NIS / NISN..."
                                    class="w-full rounded-2xl border border-gray-300 bg-gray-50 py-3 pl-4 pr-12 text-sm transition placeholder:text-gray-400 hover:border-blue-main focus:border-blue-main focus:outline-none focus:ring-4 focus:ring-blue-100" />
                                <div class="absolute inset-y-0 right-4 flex items-center text-gray-400">
                                    <iconify-icon icon="mdi:account-search-outline" width="22" height="22"></iconify-icon>
                                </div>
                            </div>

                            <select wire:model.live="filterStatus"
                                class="w-full rounded-2xl border border-gray-300 bg-gray-50 px-4 py-3 text-sm transition hover:border-blue-main focus:border-blue-main focus:outline-none focus:ring-4 focus:ring-blue-100">
                                <option value="">Semua Status</option>
                                @foreach ($statusOptions as $statusOption)
                                    <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="px-5 py-4 text-center font-semibold">No</th>
                                <th class="px-5 py-4 text-left font-semibold">Siswa</th>
                                <th class="px-5 py-4 text-left font-semibold">Kelas</th>
                                <th class="px-5 py-4 text-left font-semibold">Waktu Scan</th>
                                <th class="px-5 py-4 text-center font-semibold">Status Absensi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($history as $index => $item)
                                @php
                                    $scanTime = $item->waktu_keluar ?: $item->waktu_masuk ?: '-';
                                    $badge = match ($item->status) {
                                        'Hadir' => 'bg-green-100 text-green-600',
                                        'Masuk' => 'bg-blue-100 text-blue-600',
                                        'Izin' => 'bg-cyan-100 text-cyan-700',
                                        'Sakit' => 'bg-amber-100 text-amber-600',
                                        'Alpa' => 'bg-rose-100 text-rose-600',
                                        'Terlambat' => 'bg-orange-100 text-orange-600',
                                        default => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-4 text-center text-gray-700">{{ $history->firstItem() + $index }}</td>
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-gray-800">{{ $item->murid?->nama ?? '-' }}</div>
                                        <div class="text-xs text-gray-500">NIS/NISN: {{ $item->murid?->nipd ?? '-' }} / {{ $item->murid?->nisn ?? '-' }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-gray-700">{{ $item->murid?->rombel?->nama_lengkap ?? '-' }}</td>
                                    <td class="px-5 py-4 text-gray-700">
                                        <div class="font-medium text-gray-800">{{ $scanTime }}</div>
                                        <div class="text-xs text-gray-500">{{ optional($item->tanggal)->format('d-m-Y') }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-flex rounded-full px-4 py-1.5 text-xs font-semibold {{ $badge }}">
                                            {{ $item->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="px-6 py-14 text-center">
                                            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-gray-100 text-gray-400">
                                                <iconify-icon icon="solar:history-linear" width="36" height="36"></iconify-icon>
                                            </div>
                                            <h3 class="mt-5 text-lg font-bold text-gray-700">Belum ada riwayat absensi hari ini</h3>
                                            <p class="mt-2 text-sm text-gray-500">Riwayat scan yang sesuai filter akan ditampilkan di sini.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($history->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $history->links('livewire.components.pagination') }}
                    </div>
                @endif
            </div>
        </div>

        <!-- STUDENT INFORMATION -->
        <div class="space-y-6">
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">
                            Informasi Siswa
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Detail hasil scan terakhir.
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <iconify-icon icon="solar:user-id-bold" width="24" height="24"></iconify-icon>
                    </div>
                </div>

                @if ($lastResult && !empty($lastResult['murid']))
                    @php
                        $m = $lastResult['murid'];
                        $foto = is_string($m['imagePath'] ?? null) ? trim($m['imagePath']) : '';
                        $nama = $m['nama'] ?? '-';
                        $initial = mb_strtoupper(mb_substr((string) $nama, 0, 1, 'UTF-8'), 'UTF-8');
                        $statusAbsen = $lastResult['absen']['status'] ?? ($lastResult['status'] ?? '-');
                        $resultBadge = match ($lastResult['type'] ?? '') {
                            'success' => 'bg-green-100 text-green-600',
                            'message' => 'bg-blue-100 text-blue-600',
                            default => 'bg-rose-100 text-rose-600',
                        };
                    @endphp
                    <div class="space-y-4">
                        <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-gray-50 p-4">
                            <div class="relative flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-blue-100 text-lg font-bold text-blue-700">
                                <span>{{ $initial }}</span>
                                @if ($foto !== '')
                                    <img src="{{ $foto }}" alt="" loading="lazy" decoding="async"
                                        class="absolute inset-0 h-full w-full object-cover"
                                        onerror="this.style.display='none'">
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold text-gray-800">{{ $nama }}</p>
                                <p class="mt-1 text-sm text-gray-600">NIS/NISN: {{ $m['nipd'] ?? '-' }} / {{ $m['nisn'] ?? '-' }}</p>
                                <p class="text-sm text-gray-600">Kelas: {{ $m['rombel'] ?? '-' }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3">
                            <div class="rounded-2xl border border-gray-200 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Status Transaksi</p>
                                <span class="mt-2 inline-flex rounded-full px-4 py-1.5 text-xs font-semibold {{ $resultBadge }}">
                                    {{ $lastResult['title'] ?? $lastResult['message'] ?? 'Diproses' }}
                                </span>
                            </div>

                            <div class="rounded-2xl border border-gray-200 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Waktu Absensi</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800">
                                    {{ $lastResult['absen']['waktu'] ?? '-' }}
                                    @if (!empty($lastResult['absen']['tanggal']))
                                        <span class="text-gray-500">• {{ $lastResult['absen']['tanggal'] }}</span>
                                    @endif
                                </p>
                            </div>

                            <div class="rounded-2xl border border-gray-200 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Jenis Absensi</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800">{{ $lastResult['absen']['jenis'] ?? '-' }}</p>
                            </div>

                            <div class="rounded-2xl border border-gray-200 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Status Absensi</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800">{{ $statusAbsen ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                @elseif ($lastResult)
                    <div class="space-y-4">
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {{ $lastResult['message'] ?? 'Data siswa tidak ditemukan.' }}
                        </div>
                    </div>
                @else
                    <div class="px-2 py-10 text-center">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-gray-100 text-gray-400">
                            <iconify-icon icon="solar:card-search-linear" width="36" height="36"></iconify-icon>
                        </div>
                        <h3 class="mt-5 text-lg font-bold text-gray-700">Belum ada data siswa</h3>
                        <p class="mt-2 text-sm text-gray-500">Informasi siswa akan muncul setelah scanner mengirimkan data.</p>
                    </div>
                @endif
            </div>

            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-gray-800">Panduan</h2>
                <ul class="mt-4 space-y-3 text-sm text-gray-600">
                    <li class="flex items-start gap-3">
                        <iconify-icon icon="solar:check-circle-bold" width="20" height="20" class="mt-0.5 text-blue-main"></iconify-icon>
                        <span>Pastikan perangkat scanner terhubung dan mode input aktif.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <iconify-icon icon="solar:check-circle-bold" width="20" height="20" class="mt-0.5 text-blue-main"></iconify-icon>
                        <span>Klik kolom input scanner jika kursor tidak berada di dalamnya.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <iconify-icon icon="solar:check-circle-bold" width="20" height="20" class="mt-0.5 text-blue-main"></iconify-icon>
                        <span>Hasil transaksi dan riwayat diambil langsung dari database.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
