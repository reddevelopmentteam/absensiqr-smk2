<div class="space-y-6">

    <!-- HEADER -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-main to-blue-deep p-6 shadow-lg">

        <!-- ornament -->
        <div class="absolute -top-10 -right-10 h-40 w-40 rounded-full bg-white/10"></div>
        <div class="absolute bottom-0 left-0 h-32 w-32 rounded-full bg-white/5"></div>

        <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

            <!-- title -->
            <div class="flex items-center gap-4">

                <div
                    class="hidden sm:flex h-16 w-16 items-center justify-center rounded-2xl border border-white/10 bg-white/10 backdrop-blur">

                    <iconify-icon icon="solar:users-group-rounded-bold" width="34" height="34" class="text-white">
                    </iconify-icon>

                </div>

                <div>

                    <h1 class="text-3xl font-bold text-white">
                        Data Murid
                    </h1>

                    <p class="mt-1 text-sm text-blue-100">
                        Kelola data siswa, filter kelas, dan generate QR absensi.
                    </p>

                </div>

            </div>

            <!-- actions -->
            <div class="sm:flex grid grid-cols-1  gap-3 sm:flex-row">

                <!-- tambah -->
                <a href="{{ route('tambah-murid') }}" wire:navigate>

                    <button
                        class="w-full group flex items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-blue-main shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl">

                        <iconify-icon icon="line-md:plus" width="22" height="22"
                            class="transition duration-300 group-hover:rotate-90">
                        </iconify-icon>

                        Tambah Murid

                    </button>

                </a>

                <!-- import  excel-->
                <div x-data="{ openModalImport: false }">

                    <button @click="openModalImport = true"
                        class="w-full flex items-center justify-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">

                        <iconify-icon icon="line-md:file-import" width="22" height="22">
                        </iconify-icon>

                        Import XLSX

                    </button>

                    <livewire:datamaster.data-murid.components.modal.import />

                </div>

                <!-- export excel -->
                <livewire:datamaster.data-murid.components.modal.export />
            </div>

        </div>

    </div>

    <!-- FILTER -->
    <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">

        <!-- top -->
        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            <div>

                <h2 class="text-lg font-bold text-gray-800">
                    Filter Data
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Filter berdasarkan tingkat, jurusan, dan kelas.
                </p>

            </div>

            <!-- search -->
            <div class="relative w-full lg:w-80">

                <input type="search" name="search" wire:model.live.debounce.500ms="search"
                    placeholder="Cari nama murid..."
                    class="w-full rounded-2xl border border-gray-300 bg-gray-50 py-3 pl-4 pr-12 text-sm transition placeholder:text-gray-400 hover:border-blue-main focus:border-blue-main focus:outline-none focus:ring-4 focus:ring-blue-100" />

                <div class="absolute inset-y-0 right-4 flex items-center text-gray-400">

                    <iconify-icon icon="mdi:account-search-outline" width="22" height="22">
                    </iconify-icon>

                </div>

            </div>

        </div>

        <!-- dropdown -->
        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">

            <!-- tingkat -->
            <livewire:components.searchable-select wire:model.live="filterTingkat" label="Tingkat"
                placeholder="Cari tingkat..." :options="$listRombel->pluck('tingkat')->filter()->sortBy('nama')->unique('id')->values()" value-key="id" label-key="nama"
                all-label="Semua Tingkat" not-found-text="Tingkat tidak ditemukan." input-size="md" width="full"
                icon="mdi:magnify" icon-size="22" icon-position="right" icon-offset="4" icon-class="text-gray-500"
                dropdown-height="md" input-class="bg-white" dropdown-class="shadow-2xl" option-class="font-medium"
                check-icon="mdi:check" check-icon-size="20" check-icon-class="text-black-500" />

            <!-- jurusan -->
            <livewire:components.searchable-select wire:model.live="filterJurusan" label="Jurusan"
                placeholder="Cari jurusan..." :options="$filteredJurusan" value-key="id" label-key="nama"
                all-label="Semua Jurusan" not-found-text="Jurusan tidak ditemukan." input-size="md" width="full"
                icon="mdi:magnify" icon-size="22" icon-position="right" icon-offset="4" icon-class="text-gray-500"
                dropdown-height="md" input-class="bg-white" dropdown-class="shadow-2xl" option-class="font-medium"
                check-icon="mdi:check" check-icon-size="20" check-icon-class="text-black-500" />

            <!-- kelas / indexs -->
            <livewire:components.searchable-select wire:model.live="filterIndeks" label="Kelas"
                placeholder="Cari kelas..." :options="$filteredIndeks" value-key="id" label-key="nama" all-label="Semua Kelas"
                not-found-text="Kelas tidak ditemukan." input-size="md" width="full" icon="mdi:magnify" icon-size="22"
                icon-position="right" icon-offset="4" icon-class="text-gray-500" dropdown-height="md"
                input-class="bg-white" dropdown-class="shadow-2xl" option-class="font-medium" check-icon="mdi:check"
                check-icon-size="20" check-icon-class="text-black-500" />

        </div>

    </div>

    <!-- TABLE -->
    <div x-data="{
        selected: [],
    
        getPageUuids() {
            return Array.from(
                this.$root.querySelectorAll('.murid-row-checkbox')
            ).map(el => el.value)
        },
    
        toggleAll(e) {
            if (e.target.checked) {
                this.selected = this.getPageUuids()
            } else {
                this.selected = []
            }
        }
    }" class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">

        <!-- top table -->
        <div
            class="flex flex-col gap-4 border-b border-gray-200 px-6 py-5 md:flex-row md:items-center md:justify-between">

            <div>

                <h2 class="text-xl font-bold text-gray-800">
                    Daftar Murid
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Total data siswa yang tersedia pada sistem.
                </p>

            </div>

            <!-- selected -->
            <div class="flex items-center gap-3">

                <button
                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gray-100 text-gray-600 transition hover:bg-blue-main hover:text-white">

                    <iconify-icon icon="lineicons:menu-meatballs-1" width="20" height="20">
                    </iconify-icon>

                </button>

                <div class="rounded-full bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-main">

                    <span x-text="selected.length"></span>
                    dipilih

                </div>

            </div>

        </div>

        {{-- DESKTOP TABLE --}}

        <div class="hidden lg:block overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead class="bg-gray-100 text-gray-700">

                    <tr>

                        <th class="px-5 py-4 text-center">
                            <input type="checkbox" @click="toggleAll">
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            No
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            Murid
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            NIPD
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            NISN
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            Kelas
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            Gender
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            Tempat Lahir
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            Tanggal Lahir
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            Agama
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            Alamat
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            RT
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            RW
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            Kelurahan
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            Kecamatan
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            Kontak
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            Nama Ayah
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            Nama Ibu
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            Nama Wali
                        </th>

                        <th class="px-5 py-4 text-center font-semibold">
                            Aksi
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse ($listMurid as $index => $murid)
                        <tr class="border-t border-gray-100 transition hover:bg-gray-50">

                            {{-- checkbox --}}
                            <td class="px-5 py-5 text-center">

                                <input type="checkbox" class="murid-row-checkbox rounded border-gray-300"
                                    value="{{ $murid->uuid }}" x-model="selected">

                            </td>

                            {{-- no --}}
                            <td class="px-5 py-5 text-center">
                                {{ $index + 1 }}
                            </td>

                            {{-- murid --}}
                            <td class="px-5 py-5">

                                <div class="flex items-center gap-4">

                                    <x-murid-avatar :murid="$murid"
                                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 font-bold uppercase text-blue-main" />

                                    <div>

                                        <h2 class="font-semibold capitalize text-gray-800">
                                            {{ $murid->nama }}
                                        </h2>

                                        <p class="text-xs text-gray-400">
                                            {{ $murid->email }}
                                        </p>

                                    </div>

                                </div>

                            </td>

                            {{-- nipd --}}
                            <td class="px-5 py-5 text-center text-gray-600">
                                {{ $murid->nipd }}
                            </td>

                            {{-- nisn --}}
                            <td class="px-5 py-5 text-center text-gray-600">
                                {{ $murid->nisn }}
                            </td>

                            {{-- kelas --}}
                            <td class="px-5 py-5 text-center">

                                <div
                                    class="inline-flex w-24 items-center justify-center rounded-full bg-blue-100 px-4 py-1 text-xs font-semibold text-blue-600">

                                    {{ $murid->rombel?->nama_lengkap ?? '-' }}

                                </div>

                            </td>

                            {{-- gender --}}
                            <td class="px-5 py-5 text-center text-gray-600">
                                {{ $murid->jk }}
                            </td>

                            {{-- tempat lahir --}}
                            <td class="px-5 py-5 text-center text-gray-600">
                                {{ $murid->tempat_lahir }}
                            </td>

                            {{-- tanggal lahir --}}
                            <td class="px-5 py-5 text-center text-gray-600">
                                {{ $murid->tanggal_lahir->format('d-m-Y') }}
                            </td>

                            {{-- agama --}}
                            <td class="px-5 py-5 text-center text-gray-600">
                                {{ $murid->agama }}
                            </td>

                            {{-- alamat --}}
                            <td class="px-5 py-5 text-center text-gray-600">
                                {{ $murid->alamat }}
                            </td>

                            {{-- rt --}}
                            <td class="px-5 py-5 text-center text-gray-600">
                                {{ $murid->rt }}
                            </td>

                            {{-- rw --}}
                            <td class="px-5 py-5 text-center text-gray-600">
                                {{ $murid->rw }}
                            </td>

                            {{-- kelurahan --}}
                            <td class="px-5 py-5 text-center text-gray-600">
                                {{ $murid->kelurahan }}
                            </td>

                            {{-- kecamatan --}}
                            <td class="px-5 py-5 text-center text-gray-600">
                                {{ $murid->kecamatan }}
                            </td>

                            {{-- kontak --}}
                            <td class="px-5 py-5 text-center text-gray-600">
                                {{ $murid->hp }}
                            </td>

                            {{-- ayah --}}
                            <td class="px-5 py-5 text-center text-gray-600">
                                {{ $murid->nama_ayah }}
                            </td>

                            {{-- ibu --}}
                            <td class="px-5 py-5 text-center text-gray-600">
                                {{ $murid->nama_ibu }}
                            </td>

                            {{-- wali --}}
                            <td class="px-5 py-5 text-center text-gray-600">
                                {{ $murid->nama_wali }}
                            </td>

                            {{-- aksi --}}
                            <td class="px-5 py-5">

                                <div class="flex items-center justify-center gap-2">

                                    {{-- edit --}}
                                    <a href="{{ route('edit-murid', $murid->uuid) }}" wire:navigate>

                                        <button
                                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-400 text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-amber-500">

                                            <iconify-icon icon="lineicons:pencil-1" width="20" height="20">
                                            </iconify-icon>

                                        </button>

                                    </a>

                                    {{-- qr --}}
                                    <button @click="$dispatch('open-download-modal', { id: {{ $murid->id }} })"
                                        class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-main text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-blue-deep-solid">

                                        <iconify-icon icon="la:qrcode" width="20" height="20">
                                        </iconify-icon>

                                    </button>

                                    {{-- delete --}}
                                    <button @click="$dispatch('open-delete-murid', { id: {{ $murid->id }} })"
                                        class="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-500 text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-rose-600">

                                        <iconify-icon icon="lineicons:trash-3" width="20" height="20">
                                        </iconify-icon>

                                    </button>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <x-empty-state icon="mdi:user-card-details-outline" title="Data murid kosong"
                            description="Belum ada data murid yang tersedia." :colspan="20">
                            <a href="{{ route('tambah-murid') }}" wire:navigate>
                                <button
                                    class="group flex items-center justify-center gap-2 rounded-2xl bg-blue-main px-5 py-3 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-blue-deep hover:shadow-xl active:scale-95">
                                    <iconify-icon icon="line-md:plus" width="22" height="22"
                                        class="transition duration-300 group-hover:rotate-90">
                                    </iconify-icon>
                                    Tambah Murid
                                </button>
                            </a>
                        </x-empty-state>
                    @endforelse

            </table>

        </div>

        {{-- MOBILE + TABLET COLLAPSIBLE --}}

        <div class="space-y-3 lg:hidden">

            @forelse ($listMurid as $index => $murid)
                <div x-data="{ open: false }" class="overflow-hidden border border-gray-100 border-t-0 bg-white">

                    {{-- SUMMARY --}}
                    <div class="flex items-center gap-3 p-4">

                        {{-- checkbox --}}
                        <input type="checkbox" class="murid-row-checkbox shrink-0 rounded border-gray-300"
                            value="{{ $murid->uuid }}" x-model="selected">

                        {{-- avatar --}}
                        <x-murid-avatar :murid="$murid"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 font-bold uppercase text-blue-main" />

                        {{-- identity --}}
                        <div class="min-w-0 flex-1">

                            <h2 class="truncate font-semibold capitalize text-gray-800">
                                {{ $murid->nama }}
                            </h2>

                            <p class="truncate text-xs text-gray-400">
                                {{ $murid->email }}
                            </p>

                            <div class="mt-1">

                                <span
                                    class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-[11px] font-semibold text-blue-600">
                                    {{ $murid->rombel?->nama_lengkap ?? '-' }}
                                </span>

                            </div>

                        </div>

                        {{-- expand --}}
                        <button type="button" @click="open = !open"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-gray-500 transition hover:bg-gray-100 hover:text-gray-700"
                            :aria-expanded="open">

                            <iconify-icon icon="lineicons:chevron-down" width="20" height="20"
                                class="transition-transform duration-200" :class="{ 'rotate-180': open }">
                            </iconify-icon>

                        </button>

                    </div>

                    {{-- COLLAPSED CONTENT --}}
                    <div x-show="open" x-collapse class="border-t border-gray-100 bg-gray-50">

                        <div class="grid gap-x-6 gap-y-5 p-5 grid-cols-2">

                            {{-- no --}}
                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    No
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $index + 1 }}
                                </p>
                            </div>

                            {{-- nipd --}}
                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    NIPD
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $murid->nipd ?: '-' }}
                                </p>
                            </div>

                            {{-- nisn --}}
                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    NISN
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $murid->nisn ?: '-' }}
                                </p>
                            </div>

                            {{-- gender --}}
                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    Gender
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $murid->jk ?: '-' }}
                                </p>
                            </div>

                            {{-- tempat lahir --}}
                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    Tempat Lahir
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $murid->tempat_lahir ?: '-' }}
                                </p>
                            </div>

                            {{-- tanggal lahir --}}
                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    Tanggal Lahir
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $murid->tanggal_lahir?->format('d-m-Y') ?? '-' }}
                                </p>
                            </div>

                            {{-- agama --}}
                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    Agama
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $murid->agama ?: '-' }}
                                </p>
                            </div>

                            {{-- kontak --}}
                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    Kontak
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $murid->hp ?: '-' }}
                                </p>
                            </div>

                            {{-- alamat --}}
                            <div class="sm:col-span-2">
                                <p class="text-xs font-medium text-gray-400">
                                    Alamat
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $murid->alamat ?: '-' }}
                                </p>
                            </div>

                            {{-- rt --}}
                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    RT
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $murid->rt ?: '-' }}
                                </p>
                            </div>

                            {{-- rw --}}
                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    RW
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $murid->rw ?: '-' }}
                                </p>
                            </div>

                            {{-- kelurahan --}}
                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    Kelurahan
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $murid->kelurahan ?: '-' }}
                                </p>
                            </div>

                            {{-- kecamatan --}}
                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    Kecamatan
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $murid->kecamatan ?: '-' }}
                                </p>
                            </div>

                            {{-- ayah --}}
                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    Nama Ayah
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $murid->nama_ayah ?: '-' }}
                                </p>
                            </div>

                            {{-- ibu --}}
                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    Nama Ibu
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $murid->nama_ibu ?: '-' }}
                                </p>
                            </div>

                            {{-- wali --}}
                            <div>
                                <p class="text-xs font-medium text-gray-400">
                                    Nama Wali
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-700">
                                    {{ $murid->nama_wali ?: '-' }}
                                </p>
                            </div>

                        </div>

                        {{-- ACTIONS --}}

                        <div class="flex items-center gap-2 border-t border-gray-100 p-4">

                            {{-- edit --}}
                            <a href="{{ route('edit-murid', $murid->uuid) }}" wire:navigate
                                class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-amber-400 px-4 py-3 text-sm font-semibold text-white transition hover:bg-amber-500">

                                <iconify-icon icon="lineicons:pencil-1" width="18" height="18">
                                </iconify-icon>

                                Edit

                            </a>

                            {{-- QR --}}
                            <button type="button"
                                @click="$dispatch('open-download-modal', { id: {{ $murid->id }} })"
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-main text-white transition hover:bg-blue-deep-solid">

                                <iconify-icon icon="la:qrcode" width="20" height="20">
                                </iconify-icon>

                            </button>

                            {{-- delete --}}
                            <button type="button"
                                @click="$dispatch('open-delete-murid', { id: {{ $murid->id }} })"
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-500 text-white transition hover:bg-rose-600">

                                <iconify-icon icon="lineicons:trash-3" width="20" height="20">
                                </iconify-icon>

                            </button>

                        </div>

                    </div>

                </div>

            @empty

                <x-empty-state icon="mdi:user-card-details-outline" title="Data murid kosong"
                    description="Belum ada data murid yang tersedia.">
                    <a href="{{ route('tambah-murid') }}" wire:navigate>
                        <button
                            class="group flex items-center justify-center gap-2 rounded-2xl bg-blue-main px-5 py-3 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-blue-deep hover:shadow-xl active:scale-95">
                            <iconify-icon icon="line-md:plus" width="22" height="22"
                                class="transition duration-300 group-hover:rotate-90">
                            </iconify-icon>
                            Tambah Murid
                        </button>
                    </a>
                </x-empty-state>
            @endforelse

        </div>

        <!-- pagination -->
        <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">

            {{ $listMurid->links('livewire.components.pagination') }}

        </div>

    </div>

    <!-- modal pilih download qr -->
    <livewire:datamaster.data-murid.components.modal.pilih-qr />

    <livewire:datamaster.data-murid.components.modal.hapus />
</div>

@script
    <script>
        Promise.all([
            import('{{ Vite::asset('resources/js/generateQR.js') }}'),
            import('{{ Vite::asset('resources/js/generateCard.js') }}')
        ]).then(() => {});
    </script>
@endscript
