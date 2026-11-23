<div>

    <!-- tombol pemicu -->
    <button wire:click="openModal"
        class="w-full flex items-center justify-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">

        <iconify-icon icon="line-md:download-outline" width="22" height="22"></iconify-icon>

        Export XLSX

    </button>

    <!-- modal -->
    <div x-show="$wire.showModal" x-transition.opacity style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        @keydown.escape.window="$wire.closeModal()">

        <div x-show="$wire.showModal" x-transition
            @click.outside="$wire.closeModal()"
            class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">

            <!-- header -->
            <div class="flex items-start gap-4 border-b border-gray-100 p-6">

                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                    <iconify-icon icon="solar:file-download-bold" width="24" height="24"></iconify-icon>
                </div>

                <div class="flex-1">
                    <h2 class="text-lg font-bold text-gray-900">Export Data Murid</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Unduh data murid ke file Excel (.xlsx) sesuai filter yang dipilih.
                    </p>
                </div>

                <button wire:click="closeModal" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                    <iconify-icon icon="line-md:close" width="20" height="20"></iconify-icon>
                </button>
            </div>

            <!-- body -->
            <div class="space-y-5 p-6">

                <!-- kelas -->
                @if ($isWaliKelas && count($listRombel) === 0)
                    <div class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-700">
                        Akun Anda belum terhubung ke kelas mana pun, jadi export hanya bisa dilakukan oleh admin.
                    </div>
                @else
                    <livewire:components.searchable-select wire:model.live="filterRombelId" :options="$listRombel"
                        value-key="id" label-key="nama_lengkap" label="Kelas" placeholder="Cari kelas..."
                        all-label="Semua kelas" not-found-text="Pilihan tidak ditemukan." />
                @endif

                <div class="grid gap-4 sm:grid-cols-2">

                    <!-- status -->
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-700">Status Murid</label>

                        <select wire:model.live="filterStatus"
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm transition hover:border-blue-main focus:border-blue-main focus:outline-none focus:ring-4 focus:ring-blue-100">
                            <option value="aktif">Aktif saja</option>
                            <option value="nonaktif">Nonaktif saja</option>
                            <option value="semua">Semua status</option>
                        </select>
                    </div>

                    <!-- search -->
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-700">Pencarian (opsional)</label>

                        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Nama / NIPD / NISN"
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm transition hover:border-blue-main focus:border-blue-main focus:outline-none focus:ring-4 focus:ring-blue-100" />
                    </div>

                </div>

                <!-- opsi tambahan -->
                <label class="flex items-center gap-3 rounded-2xl bg-gray-50 p-4 text-sm text-gray-700">
                    <input type="checkbox" wire:model.live="includePhoto"
                        class="h-4 w-4 rounded border-gray-300 text-blue-main focus:ring-blue-main" />
                    Sertakan kolom URL foto murid
                </label>

                @error('filterRombelId')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror

                @error('filterStatus')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror

                <!-- preview -->
                <div class="flex items-center gap-3 rounded-2xl bg-blue-50 p-4 text-sm text-blue-main">
                    <iconify-icon icon="solar:info-circle-bold" width="20" height="20"></iconify-icon>

                    <span>
                        <span wire:loading.remove wire:target="filterRombelId,filterStatus,search">
                            {{ number_format($totalMurid, 0, ',', '.') }} murid akan diekspor.
                        </span>
                        <span wire:loading wire:target="filterRombelId,filterStatus,search">Menghitung data...</span>
                    </span>
                </div>
            </div>

            <!-- footer -->
            <div class="flex items-center justify-between gap-3 border-t border-gray-100 bg-gray-50 p-6">

                <button wire:click="resetFilter"
                    class="rounded-2xl px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-200">
                    Reset Filter
                </button>

                <div class="flex items-center gap-3">

                    <button wire:click="closeModal"
                        class="rounded-2xl bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-300">
                        Batal
                    </button>

                    <button wire:click="export" wire:loading.attr="disabled" wire:target="export"
                        @disabled($totalMurid === 0)
                        class="flex items-center gap-2 rounded-2xl bg-emerald-500 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-60">

                        <iconify-icon icon="line-md:download-outline" width="20" height="20"></iconify-icon>

                        <span wire:loading.remove wire:target="export">Download Excel</span>
                        <span wire:loading wire:target="export">Menyiapkan...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
