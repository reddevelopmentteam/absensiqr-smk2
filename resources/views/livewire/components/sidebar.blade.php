<div @keydown.escape.window="openside = false">
    @php
        $user = auth()->user();
        $siteSettings = $siteSettings ?? [];
        $siteLogo = \App\Models\Setting::resolveAssetUrl(
            $siteSettings['logo'] ?? null,
            asset('assets/img/logo_smkn_2.png'),
        );
        $siteName = $siteSettings['nama_website'] ?? 'ICT Absensi';
        $siteCopyright = $siteSettings['copyright'] ?? 'SMKN 2 Banjarmasin © 2026';
        $canAccessRoute = fn(string $routeName) => \App\Support\PermissionRegistry::canAccessRoute($user, $routeName);
        $canAccessGroup = fn(string $groupName) => \App\Support\PermissionRegistry::canAccessGroup($user, $groupName);
        $canAccessPrefix = fn(string $prefix) => \App\Support\PermissionRegistry::canAccessUriPrefix($user, $prefix);
        // Server-readable sidebar state (mirror of the client localStorage key,
        // written by Alpine $watch on every toggle). Lets Blade render the correct
        // collapsed class BEFORE any client JS runs, so neither full loads nor
        // wire:navigate swaps ever paint the wrong state first.
        $isSidebarCollapsed = request()->cookie('sidebar-collapsed') === '1';
    @endphp
    <aside id="app-sidebar" x-cloak aria-label="Sidebar navigasi utama"
        class="fixed md:sticky top-0 right-0 md:right-auto md:left-0
            z-50 flex h-screen w-64 sm:w-72 md:w-72 lg:w-80
            max-w-[85vw] shrink-0 flex-col p-1
            rounded-tl-4xl md:rounded-tl-none md:rounded-tr-4xl
            border-l md:border-l-0 md:border-r border-white/10
            bg-gradient-to-b from-blue-deep to-[#03152d]
            shadow-[8px_0_30px_rgba(0,0,0,0.15)]
            {{ $isSidebarCollapsed ? 'sidebar-collapsed' : '' }}"
        :class="{
            'translate-x-0': openside,
            'translate-x-full md:translate-x-0': !openside,
            'sidebar-collapsed': isSidebarCollapsed
        }"
        @click="if ($event.target.closest('a[href]')) openside = false">
        <div class="sidebar-header flex items-center justify-start px-2 gap-4 pt-4">
            <img src="{{ $siteLogo }}" class="sidebar-logo w-10 shrink-0" alt="">

            <div class="sidebar-header-text sidebar-label min-w-0">
                <h1 class="text-start text-sm font-semibold uppercase text-white text-shadow-2xs truncate">
                    {{ $siteName }}
                </h1>

                <p class="hidden text-[10px] uppercase text-gray-400 md:block">
                    smkn 2 banjarmasin
                </p>
            </div>

            <!-- close drawer (mobile only) -->
            <button type="button" @click="openside = false" aria-label="Tutup menu navigasi"
                aria-controls="app-sidebar"
                class="sidebar-close ml-auto flex shrink-0 items-center justify-center rounded-lg p-2 text-gray-200 transition-colors hover:bg-blue-deep-solid hover:text-white focus-visible:outline-2 focus-visible:outline-white md:hidden">
                <iconify-icon icon="lineicons:xmark" width="24" height="24"></iconify-icon>
            </button>

            <!-- toggle collapse (tablet/desktop only, mobile uses drawer) -->
            <button type="button" @click="isSidebarCollapsed = !isSidebarCollapsed"
                :aria-expanded="(!isSidebarCollapsed).toString()"
                :aria-label="isSidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                :title="isSidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'" aria-controls="app-sidebar"
                class="sidebar-toggle ml-auto mr-2 hidden shrink-0 items-center justify-center rounded-lg p-2 text-gray-200 transition-colors hover:bg-blue-deep-solid hover:text-white focus-visible:outline-2 focus-visible:outline-white md:flex">
                <iconify-icon :icon="isSidebarCollapsed ? 'lucide:panel-left-open' : 'lucide:panel-left-close'"
                    width="24" height="24"></iconify-icon>
            </button>
        </div>

        <hr class="mt-5 text-white">

        <!-- menu -->
        <ul class="mt-5 md:pb-20 pb-40 flex-1 overflow-y-auto pr-0 md:pr-2 scroll-thin space-y-2">
            @if ($canAccessRoute('dashboard'))
                <!-- dashboard -->
                <li>
                    <x-nav-link href="{{ route('dashboard') }}" icon="dashboard">
                        Dashboard
                    </x-nav-link>
                </li>
            @endif

            @if ($canAccessRoute('pilih-absen'))
                <!-- pilih absen -->
                <li>
                    <x-nav-link href="{{ route('pilih-absen') }}" icon="pilihAbsen">
                        Pilih Absen
                    </x-nav-link>
                </li>
            @endif

            <!-- laporan -->
            @if ($canAccessGroup('Laporan'))
                <li class="sidebar-group-wrap relative" x-data="{ open: $persist({{ request()->routeIs(['rekap-*', 'laporan-*', 'riwayat-absen-*', 'cetak-izin-*']) ? 'true' : 'false' }}).using(sessionStorage).as('sidebar-laporan') }">
                    <button x-bind:aria-expanded="open.toString()" aria-haspopup="true" @click="open = !open"
                        class="sidebar-group-btn relative flex w-full items-center justify-between rounded-lg px-4 py-2 text-white hover:bg-blue-deep-solid">
                        <span class="flex items-center gap-2 capitalize min-w-0">
                            <iconify-icon :icon="open ? 'mdi:folder-text' : 'mdi:folder-text-outline'" width="24"
                                height="24"></iconify-icon><span class="sidebar-label">laporan</span></span>

                        <iconify-icon class="sidebar-chevron shrink-0" :class="{ 'rotate-180': open }"
                            icon="lineicons:chevron-up" width="25" height="24"></iconify-icon>
                        <span class="sidebar-tooltip" aria-hidden="true">laporan</span></button>

                    <ul x-show="open" x-transition:enter="sidebar-submenu-enter"
                        x-transition:enter-start="sidebar-submenu-enter-start"
                        x-transition:enter-end="sidebar-submenu-enter-end" x-transition:leave="sidebar-submenu-leave"
                        x-transition:leave-start="sidebar-submenu-leave-start"
                        x-transition:leave-end="sidebar-submenu-leave-end"
                        class="sidebar-submenu ml-4 mt-2 flex flex-col gap-2" style="display: none;">
                        <!-- laporan pengawas -->
                        @if ($canAccessPrefix('/laporan-pengawas'))
                            <li class="sidebar-group-wrap relative" x-data="{ open: $persist({{ request()->routeIs(['laporan-*', 'cetak-izin-*']) ? 'true' : 'false' }}).using(sessionStorage).as('sidebar-pengawas') }">

                                <button x-bind:aria-expanded="open.toString()" aria-haspopup="true"
                                    @click="open = !open"
                                    class="sidebar-group-btn relative flex w-full items-center justify-between rounded-lg px-4 py-2 text-white hover:bg-blue-deep-solid">
                                    <span class="flex items-center gap-2 capitalize min-w-0">
                                        <iconify-icon :icon="open ? 'mdi:folder-eye' : 'mdi:folder-eye-outline'"
                                            width="24" height="24"></iconify-icon><span
                                            class="sidebar-label">pengawas</span></span>
                                    <iconify-icon class="sidebar-chevron shrink-0" :class="{ 'rotate-180': open }"
                                        icon="lineicons:chevron-up" width="25" height="24"></iconify-icon>
                                    <span class="sidebar-tooltip" aria-hidden="true">pengawas</span></button>

                                <ul x-show="open" x-transition:enter="sidebar-submenu-enter"
                                    x-transition:enter-start="sidebar-submenu-enter-start"
                                    x-transition:enter-end="sidebar-submenu-enter-end"
                                    x-transition:leave="sidebar-submenu-leave"
                                    x-transition:leave-start="sidebar-submenu-leave-start"
                                    x-transition:leave-end="sidebar-submenu-leave-end"
                                    class="sidebar-submenu ml-4 mt-2 flex flex-col gap-2" style="display: none;">
                                    {{-- izin keluar --}}
                                    <li class="sidebar-group-wrap relative" x-data="{ open: $persist({{ request()->routeIs(['laporan-izin-keluar', 'cetak-izin-keluar']) ? 'true' : 'false' }}).using(sessionStorage).as('sidebar-izin-keluar') }">
                                        <button x-bind:aria-expanded="open.toString()" aria-haspopup="true"
                                            @click="open = !open"
                                            class="sidebar-group-btn relative flex w-full items-center justify-between rounded-lg px-4 py-2 text-white hover:bg-blue-deep-solid">
                                            <span class="flex items-center gap-2 capitalize min-w-0">
                                                <iconify-icon
                                                    :icon="open ? 'mdi:folder-file' : 'mdi:folder-file-outline'"
                                                    width="24" height="24"></iconify-icon><span
                                                    class="sidebar-label">izin keluar</span></span>
                                            <iconify-icon class="sidebar-chevron shrink-0"
                                                :class="{ 'rotate-180': open }" icon="lineicons:chevron-up"
                                                width="25" height="24"></iconify-icon>
                                            <span class="sidebar-tooltip" aria-hidden="true">izin keluar</span></button>
                                        {{-- sub menu izin keluar --}}
                                        <ul x-show="open" x-transition:enter="sidebar-submenu-enter"
                                            x-transition:enter-start="sidebar-submenu-enter-start"
                                            x-transition:enter-end="sidebar-submenu-enter-end"
                                            x-transition:leave="sidebar-submenu-leave"
                                            x-transition:leave-start="sidebar-submenu-leave-start"
                                            x-transition:leave-end="sidebar-submenu-leave-end"
                                            class="sidebar-submenu ml-4 mt-2 flex flex-col gap-2"
                                            style="display: none;">
                                            {{-- menu laporam izin keluar --}}
                                            @if ($canAccessRoute('laporan-izin-keluar'))
                                                <li>
                                                    <x-nav-link href="{{ route('laporan-izin-keluar') }}"
                                                        icon="rekapAbsenMurid">
                                                        laporan izin keluar
                                                    </x-nav-link>
                                                </li>
                                            @endif
                                            {{-- menu cetak izin keluar --}}
                                            @if ($canAccessRoute('cetak-izin-keluar'))
                                                <li>
                                                    <x-nav-link href="{{ route('cetak-izin-keluar') }}"
                                                        icon="cetak-izin">
                                                        cetak izin keluar
                                                    </x-nav-link>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                                    {{-- izin telat --}}
                                    <li class="sidebar-group-wrap relative" x-data="{ open: $persist({{ request()->routeIs('cetak-izin-telat') ? 'true' : 'false' }}).using(sessionStorage).as('sidebar-izin-telat') }">
                                        <button x-bind:aria-expanded="open.toString()" aria-haspopup="true"
                                            @click="open = !open"
                                            class="sidebar-group-btn relative flex w-full items-center justify-between rounded-lg px-4 py-2 text-white hover:bg-blue-deep-solid">
                                            <span class="flex items-center gap-2 capitalize min-w-0">
                                                <iconify-icon
                                                    :icon="open ? 'mdi:folder-alert' : 'mdi:folder-alert-outline'"
                                                    width="24" height="24"></iconify-icon><span
                                                    class="sidebar-label">izin telat</span></span>
                                            <iconify-icon class="sidebar-chevron shrink-0"
                                                :class="{ 'rotate-180': open }" icon="lineicons:chevron-up"
                                                width="25" height="24"></iconify-icon>
                                            <span class="sidebar-tooltip" aria-hidden="true">izin
                                                telat</span></button>
                                        {{-- sub menu izin telat --}}
                                        <ul x-show="open" x-transition:enter="sidebar-submenu-enter"
                                            x-transition:enter-start="sidebar-submenu-enter-start"
                                            x-transition:enter-end="sidebar-submenu-enter-end"
                                            x-transition:leave="sidebar-submenu-leave"
                                            x-transition:leave-start="sidebar-submenu-leave-start"
                                            x-transition:leave-end="sidebar-submenu-leave-end"
                                            class="sidebar-submenu ml-4 mt-2 flex flex-col gap-2"
                                            style="display: none;">
                                            {{-- menu cetak izin tekat --}}
                                            @if ($canAccessRoute('cetak-izin-telat'))
                                                <li>
                                                    <x-nav-link href="{{ route('cetak-izin-telat') }}"
                                                        icon="rekapAbsenMurid">
                                                        cetak izin telat
                                                    </x-nav-link>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        <!-- rekap absen -->
                        @if ($canAccessPrefix('/rekap'))
                            <li class="sidebar-group-wrap relative" x-data="{ open: $persist({{ request()->routeIs('rekap-absen-*') ? 'true' : 'false' }}).using(sessionStorage).as('sidebar-rekap-absen') }">
                                <button x-bind:aria-expanded="open.toString()" aria-haspopup="true"
                                    @click="open = !open"
                                    class="sidebar-group-btn relative flex w-full items-center justify-between rounded-lg px-4 py-2 text-white hover:bg-blue-deep-solid">
                                    <span class="flex items-center gap-2 capitalize min-w-0">
                                        <iconify-icon :icon="open ? 'mdi:folder-check' : 'mdi:folder-check-outline'"
                                            width="24" height="24"></iconify-icon><span
                                            class="sidebar-label">rekap absen</span></span>

                                    <iconify-icon class="sidebar-chevron shrink-0" :class="{ 'rotate-180': open }"
                                        icon="lineicons:chevron-up" width="25" height="24"></iconify-icon>
                                    <span class="sidebar-tooltip" aria-hidden="true">rekap absen</span></button>

                                <ul x-show="open" x-transition:enter="sidebar-submenu-enter"
                                    x-transition:enter-start="sidebar-submenu-enter-start"
                                    x-transition:enter-end="sidebar-submenu-enter-end"
                                    x-transition:leave="sidebar-submenu-leave"
                                    x-transition:leave-start="sidebar-submenu-leave-start"
                                    x-transition:leave-end="sidebar-submenu-leave-end"
                                    class="sidebar-submenu ml-4 mt-2 flex flex-col gap-2" style="display: none;">
                                    @if ($canAccessRoute('rekap-absen-murid'))
                                        <li>
                                            <x-nav-link href="{{ route('rekap-absen-murid') }}"
                                                icon="rekapAbsenMurid">
                                                absen murid
                                            </x-nav-link>
                                        </li>
                                    @endif

                                    {{-- @if ($user?->canAccess('rekap-absen-guru'))
                            <li>
                                <x-nav-link href="{{ route('rekap-absen-guru') }}" icon="rekapAbsenGuru">
                                    absen guru
                                </x-nav-link>
                            </li>
                            @endif --}}
                                </ul>
                            </li>
                        @endif

                        <!-- riwayat -->
                        @if ($canAccessPrefix('/riwayat'))
                            <li class="sidebar-group-wrap relative" x-data="{ open: $persist({{ request()->routeIs('riwayat-absen-*') ? 'true' : 'false' }}).using(sessionStorage).as('sidebar-riwayat-absen') }">
                                <button x-bind:aria-expanded="open.toString()" aria-haspopup="true"
                                    @click="open = !open"
                                    class="sidebar-group-btn relative flex w-full items-center justify-between rounded-lg px-4 py-2 text-white hover:bg-blue-deep-solid">
                                    <span class="flex items-center gap-2 capitalize min-w-0">
                                        <iconify-icon :icon="open ? 'mdi:folder-clock' : 'mdi:folder-clock-outline'"
                                            width="24" height="24"></iconify-icon><span
                                            class="sidebar-label">riwayat absen</span></span>

                                    <iconify-icon class="sidebar-chevron shrink-0" :class="{ 'rotate-180': open }"
                                        icon="lineicons:chevron-up" width="25" height="24"></iconify-icon>
                                    <span class="sidebar-tooltip" aria-hidden="true">riwayat absen</span></button>

                                <ul x-show="open" x-transition:enter="sidebar-submenu-enter"
                                    x-transition:enter-start="sidebar-submenu-enter-start"
                                    x-transition:enter-end="sidebar-submenu-enter-end"
                                    x-transition:leave="sidebar-submenu-leave"
                                    x-transition:leave-start="sidebar-submenu-leave-start"
                                    x-transition:leave-end="sidebar-submenu-leave-end"
                                    class="sidebar-submenu ml-4 mt-2 flex flex-col gap-2" style="display: none;">
                                    @if ($canAccessRoute('riwayat-absen-murid'))
                                        <li>
                                            <x-nav-link href="{{ route('riwayat-absen-murid') }}"
                                                icon="riwayatAbsenMurid">
                                                absen murid
                                            </x-nav-link>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            <!-- absensi -->
            @if ($canAccessGroup('Absensi') || $canAccessRoute('scan-biometrik'))
                <li class="sidebar-group-wrap relative" x-data="{ open: $persist({{ (request()->routeIs('absensi-*') || request()->routeIs('scan-biometrik')) ? 'true' : 'false' }}).using(sessionStorage).as('sidebar-absensi') }">
                    <button x-bind:aria-expanded="open.toString()" aria-haspopup="true" @click="open = !open"
                        class="sidebar-group-btn relative flex w-full items-center justify-between rounded-lg px-4 py-2 text-white hover:bg-blue-deep-solid">
                        <span class="flex items-center gap-2 capitalize min-w-0">
                            <iconify-icon :icon="open ? 'mdi:folder-edit' : 'mdi:folder-edit-outline'" width="24"
                                height="24"></iconify-icon><span class="sidebar-label">absensi</span></span>

                        <iconify-icon class="sidebar-chevron shrink-0" :class="{ 'rotate-180': open }"
                            icon="lineicons:chevron-up" width="25" height="24"></iconify-icon>
                        <span class="sidebar-tooltip" aria-hidden="true">absensi</span></button>

                    <ul x-show="open" x-transition:enter="sidebar-submenu-enter"
                        x-transition:enter-start="sidebar-submenu-enter-start"
                        x-transition:enter-end="sidebar-submenu-enter-end" x-transition:leave="sidebar-submenu-leave"
                        x-transition:leave-start="sidebar-submenu-leave-start"
                        x-transition:leave-end="sidebar-submenu-leave-end"
                        class="sidebar-submenu ml-4 mt-2 flex flex-col gap-2" style="display: none;">
                        @if ($canAccessRoute('absensi-murid'))
                            <li>
                                <x-nav-link href="{{ route('absensi-murid') }}" icon="absenMurid">
                                    absensi murid
                                </x-nav-link>
                            </li>
                        @endif

                        {{-- @if ($user?->canAccess('absensi-guru'))
                    <li>
                        <x-nav-link href="{{ route('absensi-guru') }}" icon="absenGuru">
                            absensi guru
                        </x-nav-link>
                    </li>
                    @endif --}}
                    </ul>
                </li>
            @endif

            <!-- data -->
            @if ($canAccessGroup('Data Master'))
                <li class="sidebar-group-wrap relative" x-data="{ open: $persist({{ request()->routeIs('data-*') ? 'true' : 'false' }}).using(sessionStorage).as('sidebar-data-master') }">
                    <button x-bind:aria-expanded="open.toString()" aria-haspopup="true" @click="open = !open"
                        class="sidebar-group-btn relative flex w-full items-center justify-between rounded-lg px-4 py-2 text-white hover:bg-blue-deep-solid">
                        <span class="flex items-center gap-2 capitalize min-w-0">
                            <iconify-icon :icon="open ? 'mdi:folder-account' : 'mdi:folder-account-outline'"
                                width="24" height="24"></iconify-icon><span class="sidebar-label">data
                                master</span></span>

                        <iconify-icon class="sidebar-chevron shrink-0" :class="{ 'rotate-180': open }"
                            icon="lineicons:chevron-up" width="25" height="24"></iconify-icon>
                        <span class="sidebar-tooltip" aria-hidden="true">data master</span></button>

                    <ul x-show="open" x-transition:enter="sidebar-submenu-enter"
                        x-transition:enter-start="sidebar-submenu-enter-start"
                        x-transition:enter-end="sidebar-submenu-enter-end" x-transition:leave="sidebar-submenu-leave"
                        x-transition:leave-start="sidebar-submenu-leave-start"
                        x-transition:leave-end="sidebar-submenu-leave-end"
                        class="sidebar-submenu ml-4 mt-2 flex flex-col gap-2" style="display: none;">
                        @if ($canAccessRoute('data-murid'))
                            <li>
                                <x-nav-link href="{{ route('data-murid') }}" icon="dataMurid">
                                    data murid
                                </x-nav-link>
                            </li>
                        @endif

                        @if ($canAccessRoute('data-guru'))
                            <li>
                                <x-nav-link href="{{ route('data-guru') }}" icon="dataGuru">
                                    data guru
                                </x-nav-link>
                            </li>
                        @endif

                        @if ($canAccessRoute('data-kelas'))
                            <li>
                                <x-nav-link href="{{ route('data-kelas') }}" icon="dataKelas">
                                    data kelas
                                </x-nav-link>
                            </li>
                        @endif

                        @if ($canAccessRoute('data-jurusan'))
                            <li>
                                <x-nav-link href="{{ route('data-jurusan') }}" icon="dataJurusan">
                                    data jurusan
                                </x-nav-link>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            <!-- manajemen -->
            @if ($canAccessGroup('Manajemen'))
                <li class="sidebar-group-wrap relative" x-data="{ open: $persist({{ request()->routeIs(['manajemen-waktu', 'manajemen-foto-murid', 'generate-QR', 'manajemen-user', 'manajemen-tahun-ajaran', 'manajemen-role']) ? 'true' : 'false' }}).using(sessionStorage).as('sidebar-manajemen') }">
                    <button x-bind:aria-expanded="open.toString()" aria-haspopup="true" @click="open = !open"
                        class="sidebar-group-btn relative flex w-full items-center justify-between rounded-lg px-4 py-2 text-white hover:bg-blue-deep-solid">
                        <span class="flex items-center gap-2 capitalize min-w-0">
                            <iconify-icon :icon="open ? 'mdi:folder-wrench' : 'mdi:folder-wrench-outline'"
                                width="24" height="24"></iconify-icon><span
                                class="sidebar-label">manajemen</span></span>

                        <iconify-icon class="sidebar-chevron shrink-0" :class="{ 'rotate-180': open }"
                            icon="lineicons:chevron-up" width="25" height="24"></iconify-icon>
                        <span class="sidebar-tooltip" aria-hidden="true">manajemen</span></button>

                    <ul x-show="open" x-transition:enter="sidebar-submenu-enter"
                        x-transition:enter-start="sidebar-submenu-enter-start"
                        x-transition:enter-end="sidebar-submenu-enter-end" x-transition:leave="sidebar-submenu-leave"
                        x-transition:leave-start="sidebar-submenu-leave-start"
                        x-transition:leave-end="sidebar-submenu-leave-end"
                        class="sidebar-submenu ml-4 mt-2 flex flex-col gap-2" style="display: none;">
                        @if ($canAccessRoute('manajemen-waktu'))
                            <li>
                                <x-nav-link href="{{ route('manajemen-waktu') }}" icon="manajemenWaktu">
                                    waktu
                                </x-nav-link>
                            </li>
                        @endif

                        @if ($canAccessRoute('manajemen-foto-murid'))
                            <li>
                                <x-nav-link href="{{ route('manajemen-foto-murid') }}" icon="manajemenMurid">
                                    Foto Murid
                                </x-nav-link>
                            </li>
                        @endif

                        @if ($canAccessRoute('generate-QR'))
                            <li>
                                <x-nav-link href="{{ route('generate-QR') }}" icon="manajemenQR">
                                    generate QR
                                </x-nav-link>
                            </li>
                        @endif

                        @if ($canAccessRoute('manajemen-role'))
                            <li>
                                <x-nav-link href="{{ route('manajemen-role') }}" icon="manajemenRole">
                                    role akses
                                </x-nav-link>
                            </li>
                        @endif

                        @if ($canAccessPrefix('/manajemen/tahun-ajaran') || $canAccessPrefix('/manajemen/user'))
                            <li>
                                <x-nav-link href="{{ route('manajemen-tahun-ajaran') }}" icon="manajemenTahunAjaran">
                                    tahun ajaran
                                </x-nav-link>
                            </li>

                            <li>
                                <x-nav-link href="{{ route('manajemen-user') }}" icon="manajemenUser">
                                    user
                                </x-nav-link>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if ($canAccessGroup('Pengaturan'))
                <li class="sidebar-group-wrap relative" x-data="{ open: $persist({{ request()->routeIs(['pengaturan', 'refresh-all-browsers']) ? 'true' : 'false' }}).using(sessionStorage).as('sidebar-pengaturan') }">
                    <button x-bind:aria-expanded="open.toString()" aria-haspopup="true" @click="open = !open"
                        class="sidebar-group-btn relative flex w-full items-center justify-between rounded-lg px-4 py-2 text-white hover:bg-blue-deep-solid">
                        <span class="flex items-center gap-2 capitalize min-w-0">
                            <iconify-icon :icon="open ? 'mdi:folder-cog-outline' : 'mdi:folder-cog'" width="24"
                                height="24"></iconify-icon><span class="sidebar-label">Pengaturan</span></span>

                        <iconify-icon class="sidebar-chevron shrink-0" :class="{ 'rotate-180': open }"
                            icon="lineicons:chevron-up" width="25" height="24"></iconify-icon>
                        <span class="sidebar-tooltip" aria-hidden="true">Pengaturan</span>
                    </button>
                    <ul x-show="open" x-transition:enter="sidebar-submenu-enter"
                        x-transition:enter-start="sidebar-submenu-enter-start"
                        x-transition:enter-end="sidebar-submenu-enter-end" x-transition:leave="sidebar-submenu-leave"
                        x-transition:leave-start="sidebar-submenu-leave-start"
                        x-transition:leave-end="sidebar-submenu-leave-end"
                        class="sidebar-submenu ml-4 mt-2 flex flex-col gap-2" style="display: none;">
                        @if ($canAccessRoute('pengaturan'))
                            <!-- pengaturan -->
                            <li>
                                <x-nav-link href="{{ route('pengaturan') }}" icon="pengaturan">
                                    Pengaturan website
                                </x-nav-link>
                            </li>
                        @endif

                        @if ((int) $user?->role_id === 1)
                            <li>
                                <x-nav-link href="{{ route('refresh-all-browsers') }}" icon="refresh">
                                    Refresh Semua Browser
                                </x-nav-link>
                            </li>
                        @endif

                    </ul>
                </li>
            @endif

        </ul>

        <!-- footer -->
        <div class="sidebar-footer absolute bottom-0 inset-x-0 bg-[#03152E] shrink-0 border-t border-white/10 py-2">
            <a href="{{ route('profil') }}" wire:navigate>
                <div class="flex md:hidden items-center gap-2 p-2 px-5">
                    <div
                        class="cursor-pointer w-8 h-8 rounded-full bg-white shadow-sm overflow-hidden flex items-center justify-center">
                        @if ($user?->profile_photo_path)
                            <img src="{{ $user->profile_photo_url }}" alt="Profile"
                                class="w-full h-full object-cover">
                        @else
                            <iconify-icon icon="lineicons:user-4" width="25" height="24"
                                class="text-blue-deep-solid"></iconify-icon>
                        @endif
                    </div>

                    <div class="flex flex-col">
                        <h1 class="text-sm font-semibold capitalize text-white">
                            {{ auth()->user()->name }}
                        </h1>

                        <p class="text-xs capitalize text-gray-200">
                            {{ auth()->user()->role->name }}
                        </p>
                    </div>
                </div>
            </a>

            <p class="sidebar-footer-text mt-4 text-center text-[8px] uppercase text-white">
                {{ $siteCopyright }}
            </p>
        </div>
    </aside>
    <!-- overlay mobile (drawer kanan, <md) -->
    <div x-show="openside" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="openside = false"
        style="display: none;" class="fixed inset-0 z-40 bg-black/40 md:hidden"></div>
</div>
