# Face Identification — Photo Enrollment Strategy

Dokumen ini menjelaskan implementasi pipeline enrollment biometrik wajah berbasis `murid.image_path`.
QR dan hardware scanner tetap menjadi metode utama dan tidak diubah.

## 1. Ringkasan audit

- Framework: Laravel 12 + Livewire 4 + `nwidart/laravel-modules`.
- Model siswa: `App\Models\Murid\Murid`, tabel `murid`, memakai `SoftDeletes`, `uuid`, dan `image_path`.
- Foto siswa dibaca dari disk `public` (`storage/app/public`) atau path legacy `storage/...`.
- Absensi QR dan hardware memakai service yang sama: `Modules\ScanQR\Services\AttendanceProcessingService`.
- Izin, jadwal, dan auto-alpa tetap memakai service yang sudah ada:
  - `JadwalAbsensiService`
  - `AutoAlpaMuridService`
- Otorisasi halaman memakai middleware `access:<permission>` dan `User::canAccess()`.
- Permission halaman baru didaftarkan otomatis oleh `App\Support\PermissionRegistry` dari middleware route.
- Belum ada tabel biometrik, library face recognition, atau model ML di repository.
- Lingkungan audit saat ini:
  - PHP 8.4, ekstensi `gd` dan `fileinfo` tersedia.
  - `imagick` tidak tersedia.
  - Python tersedia, tetapi `cv2`, `numpy`, dan `face_recognition` belum terpasang.

## 2. Keputusan teknis

1. **Tidak mengubah tabel `murid`.** Persetujuan, evaluasi, template, dan audit disimpan di tabel terpisah.
2. **Tidak menggandakan aturan absensi.** Konfirmasi kandidat memanggil `AttendanceProcessingService::process()`.
3. **Engine wajah bersifat pluggable.** Interface `FaceProcessingService` memisahkan kualitas/enrollment dari library tertentu.
4. **Enrollment diblokir jika engine belum tervalidasi.** Foto tetap diperiksa dengan GD, tetapi status menjadi `needs_review`.
5. **Foto bukan persetujuan.** Persetujuan harus dicatat eksplisit oleh petugas melalui halaman admin.
6. **Template disimpan terenkripsi.** Cast `encrypted:array` memakai `APP_KEY`; field disembunyikan dari serialisasi.
7. **Embedding tidak disimpan di tabel evaluasi.** Evaluasi hanya menyimpan geometri, skor, dan alasan.
8. **Tidak ada penyimpanan frame permanen.** Halaman identifikasi menghapus file probe segera setelah diproses.
9. **Hanya kandidat.** Identifikasi tidak menulis absensi; petugas wajib menekan konfirmasi.
10. **Perubahan foto memicu peninjauan.** Observer `MuridObserver` menandai enrollment lama `needs_review`.
11. **Siswa tidak aktif/terhapus/persetujuan dicabut** menangguhkan template.
12. **QR dan hardware scanner tidak diubah.**

## 3. Arsitektur

### Tabel baru

| Tabel | Fungsi |
|---|---|
| `biometric_consents` | Persetujuan biometrik per siswa, versi persetujuan, siapa yang mencatat/mencabut |
| `biometric_evaluations` | Hasil pemeriksaan kualitas foto dan alasan kegagalan |
| `biometric_templates` | Template embedding terenkripsi, status, masa berlaku, alasan penangguhan |
| `biometric_enrollment_audits` | Audit trail tindakan enrollment, persetujuan, penangguhan, dan konfirmasi absensi |

### Enum

- `App\Enums\BiometricStatus`: `pending`, `eligible`, `needs_review`, `rejected`, `enrolled`, `suspended`.
- `App\Enums\BiometricConsentStatus`: `pending`, `active`, `revoked`, `expired`.

### Service

- `PhotoQualityInspector`
  - File ada dan dapat dibaca.
  - MIME harus JPG/PNG/WEBP.
  - Resolusi minimum.
  - Deteksi wajah melalui engine.
  - Jumlah wajah harus tepat satu.
  - Kecerahan, ketajaman, ukuran wajah, dan jarak dari tepi.
  - Menyimpan alasan gagal dalam bentuk kode dan pesan.
- `FaceEnrollmentService`
  - Mencatat/mencabut persetujuan.
  - Evaluasi kualitas.
  - Enrollment hanya jika siswa aktif + persetujuan aktif + hasil `eligible`.
  - Menangguhkan template saat persetujuan dicabut, siswa nonaktif/terhapus, atau foto berubah.
  - Menulis audit.
- `FaceIdentificationService`
  - Membuat probe embedding.
  - Mencari hanya template `usable` (status `enrolled`, belum kedaluwarsa, siswa aktif, persetujuan aktif).
  - Mengembalikan kandidat di atas threshold. Tidak menulis absensi.
  - `confirm()` memeriksa ulang kebijakan lalu memanggil `AttendanceProcessingService`.
- `FaceProcessingService`
  - `PythonFaceProcessingService`: adapter `scripts/face_processor.py`.
  - `UnavailableFaceProcessingService`: fallback aman saat engine tidak tersedia.

## 4. Alur status

```text
pending
  └─ evaluasi foto
       ├─ eligible  → enrollment → enrolled
       ├─ needs_review
       └─ rejected

enrolled
  ├─ foto berubah        → needs_review
  ├─ persetujuan dicabut → suspended
  ├─ siswa nonaktif      → suspended
  ├─ siswa dihapus       → suspended
  ├─ template kedaluwarsa → tidak dipakai untuk identifikasi
  └─ penangguhan manual  → suspended
```

## 5. Kebutuhan server

Engine default memakai Python:

```bash
pip install face_recognition opencv-python-headless numpy
```

`face_recognition` membutuhkan `dlib` dan build tools pada sebagian server Linux.
Validasi dengan:

```bash
python3 -c "import face_recognition, cv2; print('ok')"
```

Jika engine tidak siap:

- Halaman admin menampilkan diagnostik.
- Evaluasi foto tetap mencatat alasan `face_processor_unavailable`.
- Enrollment dan identifikasi diblokir.
- QR dan hardware scanner tetap berfungsi.

Konfigurasi tersedia di `config/biometric.php` dan `.env.example`.
Jika ingin memakai engine lain, implementasikan `FaceProcessingService` lalu ganti binding di
`AppServiceProvider`.

Pencocokan default memakai **Euclidean distance** dengan threshold `0.60`, sesuai perilaku
`face_recognition`. Jika engine lain menghasilkan vektor ternormalisasi, ubah
`BIOMETRIC_MATCH_METRIC=cosine` dan sesuaikan threshold.

## 6. UI

- Admin: `/mpanel/manajemen/biometrik`
  - Permission: `manajemen-biometrik`.
  - Melihat status persetujuan, status template, skor, jumlah wajah, dan alasan gagal.
  - Mencatat/mencabut persetujuan.
  - Memeriksa foto, enrollment, dan penangguhan.
- Operator: `/mpanel/scan-biometrik`
  - Permission: `scan-biometrik`.
  - Unggah foto probe.
  - Melihat kandidat beserta skor.
  - Konfirmasi identitas sebelum absensi dicatat.
  - Foto probe dihapus pada request yang sama.

Permission baru perlu diberikan ke role yang berwenang. Seeder menambahkan permission ke
`super-admin` dan `operator`; database yang sudah berjalan perlu diperbarui melalui halaman role.
Role `scanner` sengaja tidak otomatis diberi akses biometrik; berikan hanya setelah kebijakan
privasi sekolah disetujui.

## 7. Risiko privasi dan keamanan

- Template biometrik adalah data sensitif. Akses halaman dibatasi permission.
- `APP_KEY` harus dijaga. Kehilangan kunci membuat template tidak dapat didekripsi.
- Template tidak ditampilkan di UI dan tidak diserialisasi ke frontend.
- Embedding tidak disimpan pada tabel evaluasi.
- Foto probe tidak disimpan permanen; file sementara dihapus setelah identifikasi.
- Foto profil bukan bukti persetujuan. Persetujuan dicatat eksplisit dan dapat dicabut.
- Identifikasi hanya menghasilkan kandidat; keputusan akhir ada pada petugas.
- Engine pihak ketiga harus dievaluasi untuk kepatuhan privasi sebelum dipakai di produksi.
- Retensi template diatur `BIOMETRIC_TEMPLATE_TTL_MONTHS` (default 12 bulan).

## 8. Pengujian

File:

- `tests/Feature/BiometricEnrollmentTest.php`
- `tests/Feature/BiometricAdminPageTest.php`
- `tests/Feature/BiometricScanTest.php`
- `tests/Support/FakeFaceProcessingService.php`

Cakupan:

- Foto tidak ditemukan.
- File bukan gambar / rusak.
- Tidak ada wajah.
- Lebih dari satu wajah.
- Foto buram dan pencahayaan buruk.
- Persetujuan belum ada atau dicabut.
- Siswa nonaktif dan soft-deleted.
- Foto referensi berubah.
- Template kedaluwarsa/tidak valid.
- Kandidat tidak meyakinkan.
- Absensi sudah tercatat.
- Template terenkripsi dan tidak muncul di serialisasi.
- Halaman admin dan halaman operator memerlukan permission.
- Konfirmasi operator mencatat absensi melalui service absensi yang sama.
- File probe dihapus setelah identifikasi.

Hasil:

```text
BiometricEnrollmentTest  14 passed
BiometricAdminPageTest    3 passed
BiometricScanTest         2 passed
```

Catatan: suite penuh masih menunjukkan kegagalan yang tidak terkait perubahan biometrik:
dua kegagalan lama pada `ScanAudioTest` (template modal kamera dan alur surat izin telat)
serta satu kegagalan pada `RefreshRememberCookieTest` saat verifikasi terakhir.
Pengujian biometrik sendiri lulus 19/19.

## 9. File yang dibuat/diubah

Dibuat:

- `app/Enums/BiometricStatus.php`
- `app/Enums/BiometricConsentStatus.php`
- `app/Models/Biometric/BiometricConsent.php`
- `app/Models/Biometric/BiometricEvaluation.php`
- `app/Models/Biometric/BiometricTemplate.php`
- `app/Models/Biometric/BiometricEnrollmentAudit.php`
- `app/Observers/MuridObserver.php`
- `app/Services/Biometric/Contracts/FaceProcessingService.php`
- `app/Services/Biometric/FaceEvaluationResult.php`
- `app/Services/Biometric/ImagePathResolver.php`
- `app/Services/Biometric/PhotoQualityInspector.php`
- `app/Services/Biometric/FaceEnrollmentService.php`
- `app/Services/Biometric/FaceIdentificationService.php`
- `app/Services/Biometric/PythonFaceProcessingService.php`
- `app/Services/Biometric/UnavailableFaceProcessingService.php`
- `app/Services/Biometric/BiometricAuditLogger.php`
- `config/biometric.php`
- `database/migrations/2026_11_13_000001_create_biometric_consents_table.php`
- `database/migrations/2026_11_13_000002_create_biometric_evaluations_table.php`
- `database/migrations/2026_11_13_000003_create_biometric_templates_table.php`
- `database/migrations/2026_11_13_000004_create_biometric_enrollment_audits_table.php`
- `scripts/face_processor.py`
- `Modules/Manajemen/app/Livewire/Biometrik/Manajemen.php`
- `Modules/Manajemen/resources/views/livewire/biometrik/manajemen.blade.php`
- `Modules/ScanQR/app/Livewire/Biometrik/Scan.php`
- `Modules/ScanQR/resources/views/livewire/biometrik/scan.blade.php`
- `tests/Feature/BiometricEnrollmentTest.php`
- `tests/Feature/BiometricAdminPageTest.php`
- `tests/Feature/BiometricScanTest.php`
- `tests/Support/FakeFaceProcessingService.php`

Diubah:

- `app/Models/Murid/Murid.php` — relasi biometrik.
- `app/Providers/AppServiceProvider.php` — binding engine dan observer.
- `app/Models/User.php` — permission default route mapping.
- `database/seeders/RoleSeeder.php` — permission baru.
- `Modules/Manajemen/routes/web.php` — route admin biometrik.
- `Modules/ScanQR/routes/web.php` — route scan biometrik.
- `resources/views/components/nav-link.blade.php` — ikon navigasi.
- `resources/views/livewire/components/sidebar.blade.php` — menu.
- `.env.example` — konfigurasi biometrik.
