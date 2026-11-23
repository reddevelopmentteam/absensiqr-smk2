# API Integrasi SKENDA Library

Dokumentasi REST API read-only untuk SKENDA Library. Website Absensi tetap menjadi
Single Source of Truth (SSOT) data siswa; SKENDA Library hanya menyimpan salinan
yang diperlukan.

- **Base URL**: `https://absensi.sekolah.sch.id/api/v1/library`
- **Protokol**: HTTPS saat production.
- **Autentikasi**: `Authorization: Bearer <token>` (Laravel Sanctum).
- **Ability wajib**: `library-read`.
- **Akses**: hanya method `GET`. Tidak ada endpoint tulis (POST/PUT/PATCH/DELETE → HTTP 405).
- **Rate limit**: 120 request/menit per token.

## Pemetaan kolom

API memakai nama field yang ramah integrasi, bukan nama kolom mentah.

| Field API | Sumber di database | Catatan |
| --- | --- | --- |
| `uuid` | `murid.uuid` | UUID v4, primary identifier |
| `nis` | `murid.nipd` | **NIS disimpan di kolom `nipd`** |
| `nipd` | `murid.nipd` | nilai mentah kolom `nipd`, sama dengan `nis` |
| `nisn` | `murid.nisn` | |
| `name` | `murid.nama` | |
| `birth_date` | `murid.tanggal_lahir` | format `YYYY-MM-DD`, `null` jika kosong |
| `photo_url` | `murid.image_path` | URL absolut foto murid; `null` jika belum ada foto |
| `status` | `murid.status` | `aktif` → `active`, selain itu → `inactive` |
| `is_active` | `murid.status` | boolean, `true` hanya bila `status = aktif` |
| `class.id` | `rombel.id` | |
| `class.name` | `rombel.nama_lengkap` | gabungan tingkat + jurusan + indeks |
| `updated_at` | `murid.updated_at` | ISO 8601 |
| `deleted_at` | `murid.deleted_at` | null jika aktif; terisi jika soft delete |

Kolom sensitif (`email`, `hp`, `alamat`, `nama_ayah`, `nama_ibu`, `nama_wali`, dsb.)
tidak pernah dikembalikan.

## Membuat token Sanctum

Token dibuat untuk userwebsite Absensi yang akan dipakai SKENDA Library. Contoh via
Tinker (`php artisan tinker`):

```php
$user = App\Models\User::where('email', 'nama.user@example.sch.id')->first();

$token = $user->createToken('skenda-library', ['library-read'])->plainTextToken;

// Simpan di environment SKENDA Library, jangan di repository:
// SKENDA_LIBRARY_TOKEN=...
```

Token hanya tampil sekali saat dibuat. Simpan baik-baik, lalu hapus dan buat ulang
jika token bocor. Token **tidak** disimpan di frontend maupun repository Git.

## Format response

Kesuksesan:

```json
{
  "success": true,
  "message": "Student data retrieved successfully",
  "data": {
    "uuid": "11111111-2222-3333-4444-555555555555",
    "nis": "123456",
    "nipd": "123456",
    "nisn": "0012345678",
    "name": "Nama Siswa",
    "birth_date": "2010-01-01",
    "photo_url": "https://absensi.sekolah.sch.id/storage/murid/xi-pplg-a/nama-siswa-123456.png",
    "status": "active",
    "is_active": true,
    "class": {
      "id": 1,
      "name": "XI PPLG A"
    },
    "updated_at": "2026-11-16T08:00:00+08:00",
    "deleted_at": null
  }
}
```

Kegagalan:

```json
{
  "success": false,
  "message": "Student not found",
  "data": null
}
```

## Endpoint

Semua endpoint wajib header:

```
Authorization: Bearer <token>
Accept: application/json
```

### 1. Daftar siswa

`GET /api/v1/library/students`

Query parameter:

| Parameter | Wajib | Keterangan |
| --- | --- | --- |
| `page` | tidak | Nomor halaman (default 1) |
| `per_page` | tidak | 1–100 (default 20) |
| `search` | tidak | Cari pada `nama`, `nipd` (NIS), atau `nisn` |
| `status` | tidak | `active`/`aktif`, `inactive`/`nonaktif`, atau status mentah (`lulus`, dst.) |
| `class_id` | tidak | Filter `rombel_id` |

Contoh:

```bash
curl -sS "https://absensi.sekolah.sch.id/api/v1/library/students?page=1&per_page=20&search=Ahmad" \
  -H "Authorization: Bearer $SKENDA_LIBRARY_TOKEN" \
  -H "Accept: application/json"
```

Response memakai struktur pagination Laravel di dalam `data`:

```json
{
  "success": true,
  "message": "Student data retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "uuid": "11111111-2222-3333-4444-555555555555",
        "nis": "123456",
        "nipd": "123456",
        "nisn": "0012345678",
        "name": "Ahmad",
        "birth_date": "2010-01-01",
        "photo_url": "https://absensi.sekolah.sch.id/storage/murid/xi-pplg-a/nama-siswa-123456.png",
        "status": "active",
        "is_active": true,
        "class": { "id": 1, "name": "XI PPLG A" },
        "updated_at": "2026-11-16T08:00:00+08:00",
        "deleted_at": null
      }
    ],
    "first_page_url": "https://absensi.sekolah.sch.id/api/v1/library/students?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "https://absensi.sekolah.sch.id/api/v1/library/students?page=1",
    "links": [],
    "next_page_url": null,
    "path": "https://absensi.sekolah.sch.id/api/v1/library/students",
    "per_page": 20,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

### 2. Detail siswa berdasarkan UUID

`GET /api/v1/library/students/{uuid}`

```bash
curl -sS "https://absensi.sekolah.sch.id/api/v1/library/students/11111111-2222-3333-4444-555555555555" \
  -H "Authorization: Bearer $SKENDA_LIBRARY_TOKEN" \
  -H "Accept: application/json"
```

Tidak ditemukan → HTTP 404 `{ success: false, message: "Student not found", data: null }`.

### 3. Cari siswa berdasarkan NIS

`GET /api/v1/library/students/by-nis/{nis}`

NIS dicari pada kolom `murid.nipd`.

```bash
curl -sS "https://absensi.sekolah.sch.id/api/v1/library/students/by-nis/123456" \
  -H "Authorization: Bearer $SKENDA_LIBRARY_TOKEN" \
  -H "Accept: application/json"
```

### 4. Daftar kelas/rombel

`GET /api/v1/library/classes`

```bash
curl -sS "https://absensi.sekolah.sch.id/api/v1/library/classes" \
  -H "Authorization: Bearer $SKENDA_LIBRARY_TOKEN" \
  -H "Accept: application/json"
```

```json
{
  "success": true,
  "message": "Class data retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "XI PPLG A",
      "tingkat": "XI",
      "jurusan": "PPLG",
      "indeks": "A"
    }
  ]
}
```

### 5. Perubahan data (sinkronisasi)

`GET /api/v1/library/students/changes`

Query parameter:

| Parameter | Wajib | Keterangan |
| --- | --- | --- |
| `updated_since` | ya | Timestamp ISO 8601, mis. `2026-11-16T00:00:00+08:00` |
| `page` | tidak | Nomor halaman |
| `per_page` | tidak | 1–100 |

Endpoint mengembalikan siswa yang:

- dibuat atau diubah sejak `updated_since` (`updated_at >= updated_since`), **atau**
- di-soft-delete sejak `updated_since` (`deleted_at >= updated_since`).

Soft delete tetap dilaporkan melalui `deleted_at` pada response, sehingga SKENDA
Library dapat menonaktifkan/menghapus salinannya. Perubahan `status` (mis.
`aktif` → `lulus`/`nonaktif`) ikut terdeteksi.

```bash
curl -sS "https://absensi.sekolah.sch.id/api/v1/library/students/changes?updated_since=2026-11-16T00%3A00%3A00%2B08%3A00" \
  -H "Authorization: Bearer $SKENDA_LIBRARY_TOKEN" \
  -H "Accept: application/json"
```

Mekanisme sinkronisasi yang disarankan:

1. Sinkron awal: ambil semua halaman `GET /students`, simpan beserta `uuid` dan `updated_at`.
2. Sinkron berkala: panggil `GET /students/changes?updated_since=<timestamp sinkron terakhir>`.
3. Untuk record dengan `deleted_at` tidak null: nonaktifkan/hapus salinan di SKENDA Library.
4. Proses halaman hingga `current_page == last_page`. Simpan timestamp setelah semua halaman selesai.
5. Karena soft delete tidak selalu mengubah `updated_at`, gunakan `deleted_at` pada response
   dan never lewatkan record hasil `changes`.

Catatan: `updated_since` wajib, sehingga server tidak pernah mengirim seluruh tabel
tanpa diminta eksplisit.

### 6. Identifikasi siswa melalui QR

`GET /api/v1/library/students/by-qr/{token}`

**Format QR aktual** yang dipakai sistem absensi:

- QR kartu siswa berisi **`murid.uuid`** polos.
- QR kartu izin berisi **`{murid_uuid}>{izin_uuid}`**.

Endpoint ini mengikuti format tersebut: bagian sebelum `>` dipakai sebagai UUID
siswa; bagian izin diabaikan. Token harus berupa UUID yang valid, jika tidak → HTTP 422.

```bash
curl -sS "https://absensi.sekolah.sch.id/api/v1/library/students/by-qr/11111111-2222-3333-4444-555555555555" \
  -H "Authorization: Bearer $SKENDA_LIBRARY_TOKEN" \
  -H "Accept: application/json"

# QR izin:
curl -sS "https://absensi.sekolah.sch.id/api/v1/library/students/by-qr/11111111-2222-3333-4444-555555555555%3E22222222-3333-4444-5555-666666666666" \
  -H "Authorization: Bearer $SKENDA_LIBRARY_TOKEN" \
  -H "Accept: application/json"
```

## HTTP status code

| Status | Arti |
| --- | --- |
| 200 | Berhasil |
| 401 | Token tidak ada/kadaluarsa/salah |
| 403 | Token tidak punya ability `library-read` |
| 404 | Siswa tidak ditemukan |
| 422 | Parameter tidak valid / token QR bukan UUID |
| 405 | Method tulis tidak diizinkan |
| 429 | Melebihi rate limit |

## Contoh environment SKENDA Library

```env
SKENDA_LIBRARY_API_BASE_URL=https://absensi.sekolah.sch.id
SKENDA_LIBRARY_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
SKENDA_LIBRARY_SYNC_CRON=*/15 * * * *
```

Contoh pemakaian environment:

```bash
BASE_URL="${SKENDA_LIBRARY_API_BASE_URL}/api/v1/library"
curl -sS "$BASE_URL/students?per_page=100" \
  -H "Authorization: Bearer $SKENDA_LIBRARY_TOKEN" \
  -H "Accept: application/json"
```

## Keamanan

- Token disimpan hanya di server SKENDA Library (environment variable/secret manager).
- Jangan menaruh token di frontend, log, atau Git.
- Gunakan HTTPS production.
- API hanya membaca; tidak ada endpoint mengubah/menghapus data siswa.
