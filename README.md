<p align="center">
  <img src="public/assets/img/logo_smkn_2.png" alt="Logo SMK Negeri 2 Banjarmasin" width="140">
</p>

<h1 align="center">ICT Project — Sistem Absensi Sekolah</h1>

<p align="center">
  SMK Negeri 2 Banjarmasin<br>
  <em>Absensi siswa berbasis QR, kartu pelajar, dan biometrik wajah</em>
</p>

---

Aplikasi web absensi siswa untuk sekolah (dipakai bersama APK Android "Absensi").
Mencatat kehadiran lewat **scan QR**, **scan hardware**, dan **biometrik wajah**, lalu
menyajikan rekap, riwayat, surat izin, kartu pelajar, serta API untuk integrasi
(aplikasi perpustakaan SKENDA Library).

> README ini ditulis sebagai panduan onboarding: baca sampai bagian **Konvensi kode**
> dan **Resep menambah fitur** sebelum mulai menulis kode.

---

## 1. Fitur utama

| Area | Isi |
| --- | --- |
| Absensi | Scan QR (kamera HP/kiosk), scan hardware USB, scan biometrik wajah, absen masuk/pulang, izin keluar & izin telat, auto alpa harian (scheduler) |
| Data master | Murid (termasuk import XLSX Dapodik/template & export Excel), Guru, Kelas (rombel), Jurusan, Tingkat, Indeks |
| Laporan | Rekap absensi per kelas / semua kelas (export Excel), riwayat absensi murid, surat izin (cetak & PDF) |
| Manajemen | Generate QR & kartu pelajar (PDF), foto murid, user, role, tahun ajaran, jadwal/waktu absensi |
| Pengaturan | Identitas aplikasi, jam absensi, tombol refresh semua browser (kiosk), profil staf |
| API | API read-only untuk SKENDA Library (token Sanctum) dan API internal riwayat absensi (session + dokumentasi Scalar di `/scalar`) |
| Klien | APK Android WebView (`android/absensi`) untuk mesin scanner |

## 2. Stack teknologi

| Komponen | Versi / catatan |
| --- | --- |
| PHP | `^8.2` (development terakhir di 8.5), ekstensi wajib: `mbstring`, `zip`, `gd`, `pdo` (+ `sqlite3` untuk test) |
| Laravel | 12.x |
| Livewire | 4.x (semua halaman UI adalah komponen Livewire) |
| Modul | `nwidart/laravel-modules` 13 — kode fitur dipisah ke `Modules/*` |
| Auth | Laravel Fortify + role/permission sendiri (lihat `app/Models/User.php`) |
| API | Laravel Sanctum 4 (token + ability) |
| Excel | `maatwebsite/excel` (import & export) |
| Frontend | Tailwind CSS 4 + Vite 7, Alpine.js (bawaan Livewire), ikon dari CDN Iconify |
| JS lib | `echarts` (grafik), `jspdf` + `modern-screenshot` (kartu), `zxing-wasm` (QR), `fflate` |
| Node | 20+ (development terakhir di Node 24) |
| Database | MySQL/MariaDB (produksi), SQLite in-memory untuk test |

## 3. Setup lokal (dari nol)

```bash
git clone https://github.com/reddevelopmentteam/absensiqr-smk2.git
cd absensiqr-smk2

composer install
cp .env.example .env
php artisan key:generate

# Atur koneksi database di .env (MySQL) atau biarkan sqlite untuk eksperimen
php artisan migrate --seed
php artisan storage:link

npm install
```

Jalankan dua terminal:

```bash
# terminal 1 — aset (Vite dev server, port 5173 tetap)
npm run dev

# terminal 2 — PHP
php artisan serve
```

Alternatif sekali jalan (server + queue + vite bersamaan):

```bash
composer dev
```

**Akun hasil seeder** (`DatabaseSeeder`): email `test@test.com`, password `test`,
role Super Admin. Ganti/ hapus di server produksi.

Catatan penting:

- Kalau memakai SQLite (default `.env.example`), buat filenya dulu:
  `touch database/database.sqlite`. Untuk MySQL, isi `DB_HOST`, `DB_PORT`,
  `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` dan set `DB_CONNECTION=mysql`.
  Ada juga `composer setup` (install + `.env` + key + migrate + npm) sebagai alternatif.
- `storage:link` wajib. Foto murid disimpan di disk `public` dan dibaca lewat
  `/storage/...`. Tanpa link, semua avatar/foto akan kosong.
- Repo ini biasanya dijalankan di domain `*.test` (Laragon/Apache). `vite.config.js`
  sudah memakai `port: 5173`, `strictPort` dan `allowedHosts: ['localhost', '*.test']`,
  jadi **jangan ganti port** saat `npm run dev`.
- Untuk produksi jalankan `npm run build` (lihat bagian Aset & build).
- Seeder data contoh (tingkat, jurusan, rombel, murid, absen) sengaja dikomentari di
  `database/seeders/DatabaseSeeder.php`. Aktifkan manual bila butuh data dummy.
- Biometrik butuh Python (opsional): `pip install face_recognition opencv-python-headless`
  dan atur `BIOMETRIC_*` di `.env`. Lihat `docs/biometric-face-enrollment.md`.

## 4. Perintah sehari-hari

| Perintah | Kegunaan |
| --- | --- |
| `composer dev` | Jalankan server + queue listener + Vite sekaligus |
| `npm run dev` / `npm run build` | Aset development / produksi |
| `php artisan test` | Semua test PHP (lihat bagian Testing) |
| `npm run test:scanner` | Test JS scanner (`tests/js/*.test.mjs`) |
| `./vendor/bin/pint` | Format PHP (Laravel Pint) |
| `php artisan migrate:fresh --seed` | Reset database + data awal |
| `php artisan module:list` | Daftar modul aktif (`modules_statuses.json`) |
| `php artisan route:list` | Semua route + middleware |
| `php artisan absensi:auto-alpa-murid` | Tandai alpa murid yang tidak absen (juga dijalankan scheduler) |
| `php artisan schedule:work` | Jalankan scheduler di lokal tanpa cron |
| `php artisan optimize:clear` | Bersihkan cache config/route/view saat perilaku aneh |

## 5. Struktur proyek

```
app/
├── Console/Commands/        # absensi:auto-alpa-murid, MakeHelper
├── Enums/                   # AttendanceStatus, StudentStatus
├── Exports/                 # export Excel (Murid, Guru, rekap kehadiran)
├── Helpers/                 # ToastMagic, DownloadFile, UploadFileNamer
├── Http/
│   ├── Controllers/Api/V1/  # controller API (Library/SKENDA)
│   ├── Middleware/          # AccessMiddleware, SuperAdminMiddleware, TestTime, ...
│   └── Resources/Api/V1/    # resource JSON API
├── Imports/                 # import Excel murid/guru
├── Livewire/                # komponen global (Login, komponen UI bersama)
├── Models/                  # Murid, Guru, Rombel, AbsenMurid, IzinMurid, Biometric*, User, Role
├── Observers/               # MuridObserver (mis. hapus template biometrik)
├── Services/                # logika bisnis (Api, Biometric, Murid, Rombel)
├── Support/                 # PermissionRegistry, LibraryApiResponse
└── Providers/

Modules/                     # fitur per domain (nwidart/laravel-modules)
├── General/                 # dashboard, pilih absen, pengaturan, refresh browser
├── Absensi/                 # daftar & edit absensi murid
├── DataMaster/              # murid, guru, kelas, jurusan
├── Laporan/                 # rekap, riwayat, izin keluar/telat (+ openapi/)
├── Manajemen/               # QR & kartu, foto murid, user, role, waktu, tahun ajaran
├── Profil/                  # profil staf
└── ScanQR/                  # scan QR, scan hardware, scan biometrik

resources/
├── css/app.css              # Tailwind 4 + style global
├── js/                      # app.js, generateCard.js, generateQR.js, face-recognition.js, ...
└── views/                   # layout app/auth + komponen Blade (x-nav-link, dsb.)

routes/
├── web.php                  # login, /mpanel, template kartu pelajar
├── api.php                  # API v1/library (Sanctum)
└── console.php              # scheduler

database/migrations|seeders    # skema & data awal (termasuk RoleSeeder)
docs/                          # dokumen integrasi & fitur (WAJIB dibaca sebelum menyentuh API)
scripts/                       # face_processor.py, schedule-cron.sh, validasi aset scanner
android/absensi/               # project Android Studio (WebView scanner)
tests/Feature|Unit, tests/js   # test PHP & JS
```

## 6. Modul & halaman

Semua halaman internal berada di bawah prefix `/mpanel` dan dilindungi `auth` +
middleware `access:<permission>`.

| Modul | Route penting |
| --- | --- |
| General | `dashboard`, `pilih-absen`, `pengaturan`, `refresh-all-browsers` (super admin) |
| ScanQR | `scan-qrcode`, `scan-hardware`, `scan-biometrik`, `scan-camera-status` |
| Absensi | `absensi-murid`, `edit-absen-murid` |
| DataMaster | `data-murid`, `data-guru`, `data-kelas`, `data-jurusan` (+ `create`/`edit`/`template`) |
| Laporan | `rekap-absen-murid`, `riwayat-absen-murid`, `riwayat-detail-absen-murid`, `laporan-izin-keluar`, `cetak-izin-keluar`, `surat-izin-keluar`, `cetak-izin-telat`, `surat-izin-telat` |
| Manajemen | `generate-QR`, `manajemen-foto-murid`, `manajemen-role`, `manajemen-user`, `manajemen-tahun-ajaran`, `manajemen-waktu` |
| Profil | `profil` |

## 7. Hak akses (role & permission)

- Permission **tidak** didefinisikan manual: `App\Support\PermissionRegistry`
  memindai semua route dan mengambil nilai dari middleware `access:<permission>`.
  Jadi menambah akses = menambah middleware di route.
- Role disimpan di tabel `role` (kolom JSON `permissions`), relasi many-to-many
  `role_user` **dan** kolom legacy `users.role_id`. Gunakan helper di `User`:
  `hasRole()`, `hasOnlyRoles([...])`, `canAccess('data-murid')`, `isSuperAdmin()`
  (role id 1 = Super Admin).
- Middleware: `access:<permission>` → `AccessMiddleware`, `super-admin` →
  `SuperAdminMiddleware`.

Role bawaan (`database/seeders/RoleSeeder.php`):

| id | Role | Ringkas permission |
| --- | --- | --- |
| 1 | Super Admin | Semua permission (`dashboard` … `profil`) |
| 2 | Pengawas | **kosong di seeder** — isi manual lewat menu Manajemen → Role |
| 3 | Guru | **kosong di seeder** — isi manual lewat menu Manajemen → Role |
| 4 | Wali Kelas | `dashboard`, `laporan`, `rekap-absen`, `rekap-absen-murid`, `riwayat`, `riwayat-murid`, `absensi`, `absensi-murid`, `data-master`, `data-murid`, `manajemen`, `manajemen-murid`, `profil` |
| 5 | Operator | `pilih-absen`, `scan-qrcode`, `scan-biometrik`, `generate-qr`, `manajemen-waktu`, `profil`, `izin-keluar`, `izin-telat`, `laporan-pengawas` |
| 6 | scanner | `pilih-absen`, `scan-qrcode`, `profil` |

> Catatan: `RoleSeeder` mencocokkan nama role dengan `Str::slug($nama)`. Role
> **Pengawas** dan **Guru** belum punya daftar permission (`'pengawas'` dan `'guru'`
> tidak ada di array seeder), jadi user dengan role itu tidak bisa membuka menu apa
> pun sampai permission-nya diisi. Ini sering jadi sumber kebingungan saat membuat
> user baru.

**Aturan wali kelas** (penting, jangan dilanggar saat menambah fitur): user yang
`hasOnlyRoles(['Wali Kelas', 'Wali Murid'])` hanya boleh melihat/mengekspor data
rombel di mana `rombel.wali_guru_id` = guru miliknya. Contoh implementasi:
`Modules/DataMaster/app/Livewire/DataMurid/Index.php`,
`.../Components/Modal/Export.php`, dan `App\Services\Api\Library\LibraryStudentService`.

## 8. Konvensi kode

1. **Fitur baru = modul baru atau komponen di modul yang sudah ada.** Jangan menaruh
   logika domain di `app/Http/Controllers` kecuali untuk API.
2. **Halaman = satu komponen Livewire full-page** (`class ... extends Component` +
   method `render()`), didaftarkan di `Modules/<Modul>/routes/web.php` dengan
   `->middleware('access:<permission>')` dan `->name('nama-route')`.
3. **View modul memakai namespace modul**: `view('datamaster::livewire.data-murid.data')`
   → file `Modules/DataMaster/resources/views/livewire/data-murid/data.blade.php`.
4. **Modal = komponen Livewire terpisah** di `.../Components/Modal/`, state buka/tutup
   disimpan di public property (`$showModal`) dan dipakai `x-show="$wire.showModal"`,
   bukan Alpine lokal, supaya tidak reset saat re-render.
5. **Logika bisnis di `app/Services/...`**, bukan di komponen. Query yang dipakai
   lebih dari satu tempat (halaman + export + API) wajib dipindahkan ke service
   (contoh: `App\Services\Murid\MuridExportService`).
6. **Validasi selalu di komponen/controller** (`$this->validate([...])` atau
   `Validator::make`), pesan error ditampilkan di view.
7. **UI berbahasa Indonesia**, komentar kode boleh Indonesia atau Inggris (ikuti file
   sekitarnya). Styling memakai Tailwind 4 langsung di Blade, ikon lewat
   `<iconify-icon icon="...">`.
8. **Jangan pernah** mengembalikan data sensitif murid (email, hp, alamat, nama orang
   tua) ke API publik — lihat daftar kolom yang dikecualikan di
   `docs/API-SKENDA-LIBRARY.md`.

## 9. Resep menambah fitur

### a. Halaman baru di modul yang ada

1. Buat komponen: `php artisan make:livewire` (atau salin pola komponen yang ada),
   taruh di `Modules/<Modul>/app/Livewire/...`.
2. Buat view di `Modules/<Modul>/resources/views/livewire/...`.
3. Daftarkan route di `Modules/<Modul>/routes/web.php`:

```php
Route::prefix('/laporan-siswa')->middleware('access:laporan-siswa')->group(function () {
    Route::get('/', LaporanSiswa::class)->name('laporan-siswa');
});
```

4. Permission `laporan-siswa` otomatis dikenali `PermissionRegistry`. Tambahkan ke
   role yang berhak di `RoleSeeder` **dan** di database yang sudah berjalan lewat menu
   Manajemen → Role. Hati-hati: `php artisan db:seed --class=RoleSeeder` memakai
   `updateOrCreate`, jadi perintah itu **menimpa** permission role yang sudah diubah
   manual di server.
5. Tambahkan menu di `resources/views/livewire/components/sidebar.blade.php`
   memakai `$canAccessRoute('nama-route')` / `$canAccessGroup('Nama Grup')`.

### b. Export Excel baru

Ikuti pola fitur export murid:

1. Buat class di `app/Exports/...` (`FromQuery` + `WithHeadings` + `WithMapping`,
   tambahkan `WithCustomValueBinder` bila ada NIPD/NISN agar nol di depan tidak hilang).
2. Buat service query agar filter dan pembatasan role konsisten.
3. Panggil `Excel::download(new XxxExport($query), 'nama-file.xlsx')` dari komponen.
4. Tulis test: baca ulang file dengan `PhpOffice\PhpSpreadsheet\IOFactory` lalu cek
   header dan baris (contoh `tests/Feature/MuridExportTest.php`).

### c. Endpoint API baru

1. Route di `routes/api.php` (API publik/klien eksternal) atau
   `Modules/<Modul>/routes/api.php` (API internal berbasis session).
2. API eksternal: pakai `auth:sanctum` + `abilities:<ability>` + throttle, kembalikan
   envelope `App\Support\Api\LibraryApiResponse` (`success`, `message`, `data`).
3. Dokumentasikan: API perpustakaan di `docs/API-SKENDA-LIBRARY.md`, API internal di
   `Modules/Laporan/openapi/*.json` (tampil di `/scalar`).
4. Jangan lupa test 401/403/404/422 seperti `tests/Feature/LibraryApiTest.php`.

### d. Migrasi & model

- Migrasi baru di `database/migrations` (domain inti) atau
  `Modules/<Modul>/database/migrations` (domain modul). Selalu tulis `down()`.
- Nama tabel/kolom aplikasi ini berbahasa Indonesia (`murid`, `nisn`, `rombel`,
  `tanggal_lahir`, ...) — ikuti, jangan campur dengan bahasa Inggris di DB.

## 10. Testing

```bash
php artisan test                     # semua test PHP
php artisan test --filter=MuridExportTest
npm run test:scanner                 # test JS scanner
```

- Test memakai SQLite in-memory (`phpunit.xml`), jadi tidak menyentuh DB lokal.
- Referensi test yang bagus untuk ditiru: `tests/Feature/LibraryApiTest.php`,
  `tests/Feature/MuridExportTest.php`, `tests/Feature/RekapKehadiranExportTest.php`.
- **Ada 9 test yang sudah gagal sejak sebelum perubahan terakhir** (biometrik
  consent/template/identifikasi, remember cookie, modal scan audio, surat izin telat).
  Kegagalan itu bukan regresi; bandingkan dengan `git stash` bila ragu.

## 11. Integrasi, API & klien

| Dokumen | Isi |
| --- | --- |
| `docs/API-SKENDA-LIBRARY.md` | API read-only perpustakaan: token Sanctum ability `library-read`, rate limit 120/menit, field `uuid`, `nis`, `nipd`, `nisn`, `name`, `photo_url`, `birth_date`, `class`, `status`, `updated_at` |
| `Modules/Laporan/openapi/student-attendance.json` (`/scalar`) | API riwayat absensi internal (session + permission `riwayat-murid`) |
| `docs/biometric-face-enrollment.md` | Alur enrollment biometrik, kualitas foto, engine `scripts/face_processor.py` |
| `docs/scan-qr-android.md` | Build APK `android/absensi` (JDK 17, SDK 35), mode fullscreen kiosk |
| `docs/qr-scan-audio.md` | Panduan aset audio scan (6 file mp3 di `Modules/ScanQR/resources/assets/audio`) |

Membuat token API untuk integrasi:

```php
// php artisan tinker
$user = App\Models\User::where('email', 'admin@sekolah.sch.id')->first();
$token = $user->createToken('skenda-library', ['library-read'])->plainTextToken;
```

Token hanya tampil sekali; simpan di environment aplikasi klien, jangan di repo.

## 12. Scheduler, queue, dan penyimpanan

- **Scheduler**: `routes/console.php` menjalankan `absensi:auto-alpa-murid` setiap
  menit. Di server pasang cron dengan `scripts/schedule-cron.sh install`
  (`status` / `remove` juga tersedia).
- **Queue**: driver default `database` (`composer dev` sudah menjalankan
  `queue:listen`). Saat ini alur utama (termasuk import murid) berjalan sinkron per
  chunk lewat request Livewire, jadi aplikasi tetap jalan tanpa worker — tetapi
  jalankan worker bila nanti menambah job/notifikasi antre.
- **Storage**:
  - `storage/app/private` → template kartu pelajar (`card/*.html`) yang di-render route
    `mpanel/card-template/{orientation}`.
  - disk `public` (`public/storage/...`) → foto murid. `murid.image_path` menyimpan URL
    hasil `Storage::url()`, sehingga API mengembalikannya sebagai `photo_url` absolut.
- **Cache/session**: driver `database` (tabel `cache`, `sessions`).

## 13. Aset & build

- `npm run dev` menjalankan hook `predev`: validasi 6 file audio scan
  (`Modules/ScanQR/resources/assets/audio/*.mp3`, MP3 valid dan ≤ 5 MB).
- `npm run build` menjalankan `prebuild` (validasi audio) dan `postbuild`
  (`scripts/validate-scanner-build.mjs`: memastikan entry scanner mengekspor
  `createScanAudio` dan `createScanResultController`). Jangan menghapus export itu.
- Hasil build ada di `public/build` (**tidak** di-commit). Di server produksi wajib
  `npm run build` setelah `git pull`.

## 14. Troubleshooting

| Gejala | Penyebab & solusi |
| --- | --- |
| Halaman tanpa style, HMR gagal | `npm run dev` belum jalan / port 5173 dipakai proses lain (`strictPort`) |
| Foto/avatar kosong | `php artisan storage:link` belum dijalankan |
| 403 saat buka menu | Role tidak punya permission route tsb; cek `access:<permission>` dan `role.permissions` |
| Perubahan tidak muncul | `php artisan optimize:clear` (config/route/view cache) |
| Import XLSX tidak selesai | Proses chunk berhenti di tengah (timeout/refresh). Import berjalan lewat request Livewire, bukan queue: ulangi import atau kecilkan file |
| Import murid salah baris | Format hasil export Dapodik berbeda dengan template — lihat `Modules/DataMaster/.../Modal/Import.php` (deteksi header row 5 vs 6) |
| Export Excel error | Ekstensi PHP `zip`/`gd` belum aktif |
| Scan biometrik menolak semua wajah | Engine python belum terpasang; cek diagnostik di menu foto murid, lihat `config/biometric.php` |
| Waktu absensi tidak sesuai | Jadwal diatur di menu Manajemen → Waktu & `config/waktu-absensi.php` |
| Tombol super admin tidak muncul | `isSuperAdmin()` = role id 1 |

Debug cepat: `php artisan route:list`, `storage/logs/laravel.log`, `php artisan pail`,
dan `_debugbar` (aktif di lokal).

## 15. Alur kerja Git

- Branch utama `main` dilindungi (disarankan lewat Pull Request).
- Buat branch fitur: `git checkout -b feature/export-murid`, commit kecil dan jelas
  (contoh: `Add student data export to Excel`), lalu buka PR ke `main`.
- Jangan commit: `.env`, `storage/backups-local/`, `node_modules`, `public/build`,
  `vendor`, file backup/dump database. Semua sudah ada di `.gitignore`.
- Sebelum push: `php artisan test` + `npm run build` harus bersih (kecuali 9 test
  lama yang memang gagal).

## 16. Ide pengembangan lanjutan

- Export kartu pelajar massal per kelas (PDF gabungan).
- Notifikasi WhatsApp/email ke orang tua saat murid alpa.
- Dashboard statistik kehadiran per bulan (ECharts sudah tersedia).
- Endpoint API untuk aplikasi mobile guru (rekap & izin).
- Refactor kolom legacy `users.role_id` → sepenuhnya `role_user`.

---

Kalau ada yang belum jelas: cek `docs/`, jalankan `php artisan route:list`, dan baca
komponen Livewire terdekat dengan fitur yang ingin diubah — pola di proyek ini cukup
konsisten.
