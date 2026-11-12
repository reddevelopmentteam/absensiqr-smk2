<?php

namespace Modules\ScanQR\Services;

use App\Enums\AttendanceStatus;
use App\Models\Murid\AbsenMurid;
use App\Models\Murid\IzinMurid;
use App\Models\Murid\Murid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class AttendanceProcessingService
{
    protected ?Murid $murid = null;

    protected bool $tersimpan = false;

    protected ?string $scanTitle = null;

    protected ?string $scanStatus = null;

    protected ?string $scanMessage = null;

    protected ?string $izinUuid = null;

    protected bool $isContainsIzin = false;

    protected array $jadwalHariIni = [];

    public function __construct(
        protected JadwalAbsensiService $jadwalAbsensiService,
        protected AutoAlpaMuridService $autoAlpaMuridService,
    ) {}

    /**
     * @return array{scanResult: array, murid: ?Murid, jadwal: array, absen: ?AbsenMurid}
     */
    public function process(string $payload): array
    {
        $scanResult = null;
        $absen = null;

        try {
            DB::transaction(function () use (&$absen, $payload): void {
                $absen = $this->processQRCode($payload);
            });
        } catch (Throwable $error) {
            report($error);
            $this->resetScanState();
            $this->rejectScan('Proses absensi gagal. Silakan coba kembali.');
        }

        $scanResult = $this->scanResult ?? $this->fallbackScanResult();

        return [
            'scanResult' => $scanResult,
            'murid' => $this->murid,
            'jadwal' => $this->jadwalHariIni,
            'absen' => $absen,
        ];
    }

    private ?array $scanResult = null;

    private function processQRCode(string $muridUuid): ?AbsenMurid
    {
        $this->resetScanState();

        if ($muridUuid === '') {
            $this->rejectScan('QR Code tidak valid.');

            return null;
        }

        $muridUuid = $this->parseMuridUuid($muridUuid);
        $murid = $this->findMurid($muridUuid);

        if (! $this->checkMuridState($murid)) {
            return null;
        }

        $now = now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');
        $jadwal = $this->jadwalAbsensiService->forRombel($murid->rombel_id, $today);

        $this->murid = $murid;
        $this->jadwalHariIni = $jadwal;

        $this->autoAlpaMuridService->syncExpiredIzinKeluar($now);

        if (! $this->processIzin($murid, $currentTime, $jadwal)) {
            return $this->currentAbsen($murid, $today);
        }

        if (! $this->jadwalAbsensiService->bolehScan($jadwal)) {
            $this->rejectScan('Absensi Tidak Disimpan. Sistem mengecek jadwal kelas dari Manajemen Waktu.');

            return $this->currentAbsen($murid, $today);
        }

        $this->autoAlpaMuridService->syncForRombel($murid->rombel_id, $today, $jadwal, $now);

        return $this->processAbsensi($murid, $today, $currentTime, $jadwal);
    }

    private function checkMuridState($murid): bool
    {
        if (! $murid) {
            $this->rejectScan('QR Code tidak valid atau murid tidak ditemukan.');

            return false;
        }

        if ($murid->status !== 'aktif') {
            $this->rejectScan('Murid tidak aktif, absensi tidak dapat disimpan.');

            return false;
        }

        if (! $murid->rombel_id) {
            $this->murid = $murid;
            $this->rejectScan('Murid belum memiliki kelas/rombel, absensi tidak dapat disimpan.');

            return false;
        }

        return true;
    }

    private function saveLateAttendance(Murid $murid, string $today, string $currentTime): ?AbsenMurid
    {
        $saved = AbsenMurid::updateOrCreate(
            ['murid_id' => $murid->id, 'tanggal' => $today],
            [
                'waktu_masuk' => $currentTime,
                'status' => AttendanceStatus::Terlambat->value,
                'keterangan' => null,
            ],
        );

        if (! $saved->exists || (! $saved->wasRecentlyCreated && ! $saved->wasChanged())) {
            $this->rejectScan('Absensi gagal disimpan.');

            return $saved;
        }

        $this->acceptScan('Absensi masuk berhasil disimpan.', 'late');

        return $saved;
    }

    private function parseMuridUuid($muridUuid)
    {
        if (str_starts_with($muridUuid, 's:')) {
            $decoded = @unserialize($muridUuid, ['allowed_classes' => false]);
            if (is_string($decoded)) {
                $muridUuid = $decoded;
            }
        }

        if (str_contains($muridUuid, '>')) {
            $this->isContainsIzin = true;
            [$muridUuid, $izinUuid] = explode('>', $muridUuid, 2);
            $this->izinUuid = $izinUuid;
        }

        return $muridUuid;
    }

    private function findMurid($muridUuid): ?Murid
    {
        return Murid::query()
            ->aktif()
            ->with('rombel')
            ->where('uuid', $muridUuid)
            ->first();
    }

    private function processIzin(Murid $murid, string $currentTime, array $jadwal): bool
    {
        $izin = IzinMurid::query()
            ->where('murid_id', $murid->id)
            ->latest('created_at')
            ->lockForUpdate()
            ->first();

        if ($izin && $izin?->status === AttendanceStatus::Izin->value && (! $this->isContainsIzin || $izin?->uuid !== $this->izinUuid)) {
            $this->rejectScan('Murid masih memiliki izin yang belum selesai. Silakan scan QR izin untuk menyelesaikan proses izin.');

            return false;
        }

        if ($izin && $this->isContainsIzin && $izin?->uuid === $this->izinUuid && $izin?->status === AttendanceStatus::Izin->value) {
            $isIzinHariIni = $izin->tanggal?->isSameDay(now());

            if ($isIzinHariIni) {
                $absen = AbsenMurid::query()
                    ->where('murid_id', $murid->id)
                    ->whereDate('tanggal', $izin->tanggal)
                    ->lockForUpdate()
                    ->first();

                if (! $absen || ! $izin->status_absensi_sebelumnya) {
                    $this->rejectScan('Data absensi sebelum izin tidak ditemukan. Hubungi pengawas untuk memeriksa izin ini.');

                    return false;
                }

                if (! $absen->update(['status' => $izin->status_absensi_sebelumnya])) {
                    throw new \RuntimeException('Status absensi gagal dipulihkan.');
                }
            }

            if (! $izin->update([
                'status' => AttendanceStatus::Selesai->value,
            ])) {
                throw new \RuntimeException('Izin gagal disimpan.');
            }

            $message = $isIzinHariIni
                ? 'Izin telah diproses dan status absensi dikembalikan.'
                : 'Izin telah diproses. Status absensi tidak diubah karena QR dipindai setelah tanggal izin.';
            if ($this->withinWindow(
                $currentTime,
                $jadwal['scan_masuk_mulai'] ?? null,
                $jadwal['scan_masuk_sampai'] ?? null,
            ) || $this->dalamJendelaPulang($currentTime, $jadwal)) {
                $message .= ' Silakan gunakan QR code absensi untuk absensi.';
            }

            $this->acceptMessage('Izin Berhasil Diperbarui', $message, 'permission_success');

            return false;
        }

        if ($izin && $this->isContainsIzin && $izin?->status === AttendanceStatus::Selesai->value) {
            $this->rejectScan('Murid sudah selesai izin. Silakan scan QR absensi.');

            return false;
        }

        if ($izin && $this->isContainsIzin && $izin?->status === AttendanceStatus::Alpa->value) {
            $this->rejectScan('Izin keluar telah berubah menjadi alpa karena QR izin tidak dipindai pada tanggal izin.');

            return false;
        }

        return true;
    }

    private function processAbsensi(Murid $murid, string $today, string $currentTime, array $jadwal): ?AbsenMurid
    {
        $absen = AbsenMurid::query()
            ->where('murid_id', $murid->id)
            ->where('tanggal', $today)
            ->first();

        if ($this->canReplaceAlpaWithLate($absen, $currentTime, $jadwal)) {
            return $this->saveLateAttendance($murid, $today, $currentTime);
        }

        if (! $absen?->waktu_masuk
            && $absen?->status !== AttendanceStatus::Alpa->value
            && $absen?->status !== AttendanceStatus::Terlambat->value
            && ! $this->dalamJendelaPulang($currentTime, $jadwal)) {
            $beradaSetelahScanMasuk = $currentTime > $this->normalizeTime($jadwal['scan_masuk_sampai'] ?? null);
            $belumScanKeluar = ! $this->isWindowStarted($currentTime, $jadwal['scan_keluar_mulai'] ?? null);
            if ($beradaSetelahScanMasuk && $belumScanKeluar) {
                return $this->saveLateAttendance($murid, $today, $currentTime);
            }
        }

        if (! $absen && ! $absen?->waktu_keluar) {
            return $this->processAbsensiPertama($murid, $today, $currentTime, $jadwal);
        }

        return $this->processAbsensiLanjutan($absen, $currentTime, $jadwal);
    }

    private function canReplaceAlpaWithLate(?AbsenMurid $absen, string $currentTime, array $jadwal): bool
    {
        $batasMasuk = $this->normalizeTime($jadwal['scan_masuk_sampai'] ?? null);
        $mulaiPulang = $this->normalizeTime($jadwal['scan_keluar_mulai'] ?? null);

        return $absen?->status === AttendanceStatus::Alpa->value
            && ! $absen->waktu_masuk
            && $batasMasuk !== null
            && $mulaiPulang !== null
            && $this->normalizeTime($currentTime) > $batasMasuk
            && $this->normalizeTime($currentTime) < $mulaiPulang;
    }

    private function processAbsensiPertama(Murid $murid, string $today, string $currentTime, array $jadwal): ?AbsenMurid
    {
        if (! $this->withinWindow($currentTime, $jadwal['scan_masuk_mulai'] ?? null, $jadwal['scan_masuk_sampai'] ?? null)
            && ! $this->isWindowStarted($currentTime, $jadwal['scan_masuk_mulai'] ?? null)) {
            $this->rejectScan('Jadwal absensi masuk belum dibuka.', 'attendance_not_open');

            return null;
        }

        if (! $this->withinWindow($currentTime, $jadwal['scan_masuk_mulai'] ?? null, $jadwal['scan_masuk_sampai'] ?? null)) {
            $this->rejectScan(sprintf(
                'Scan masuk dibuka pukul %s - %s.',
                $jadwal['scan_masuk_mulai'] ?? '--:--',
                $jadwal['scan_masuk_sampai'] ?? '--:--',
            ));

            return null;
        }

        $saved = AbsenMurid::create([
            'murid_id' => $murid->id,
            'tanggal' => $today,
            'waktu_masuk' => $currentTime,
            'status' => AttendanceStatus::Masuk->value,
            'keterangan' => $this->keteranganAbsensi($jadwal),
        ]);

        if (! $saved->exists) {
            $this->rejectScan('Absensi gagal disimpan.');

            return $saved;
        }

        $this->acceptScan('Absensi masuk berhasil disimpan.');

        return $saved;
    }

    private function processAbsensiLanjutan(?AbsenMurid $absen, string $currentTime, array $jadwal): ?AbsenMurid
    {
        if (! $absen?->waktu_masuk && $this->dalamJendelaPulang($currentTime, $jadwal)) {
            $this->rejectScan('Maaf kamu tidak bisa absen pulang karena belum absen masuk');

            return $absen;
        }

        if (! $absen?->waktu_keluar && $this->dalamJendelaPulang($currentTime, $jadwal)) {
            $status = $absen?->status === AttendanceStatus::Terlambat->value
                ? AttendanceStatus::Terlambat->value
                : AttendanceStatus::Hadir->value;
            if (! $absen->update([
                'waktu_keluar' => $currentTime,
                'status' => $status,
            ])) {
                $this->rejectScan('Absensi gagal disimpan.');

                return $absen;
            }

            $this->acceptScan('Absensi pulang berhasil disimpan.');

            return $absen;
        }

        if ($absen?->waktu_keluar) {
            $this->acceptMessage('Murid sudah pulang', 'Murid ini sudah absen masuk dan pulang hari ini.');

            return $absen;
        }

        $this->acceptMessage('Absensi masuk sudah tercatat', sprintf(
            'Absensi pulang dibuka pukul %s - %s.',
            $jadwal['scan_keluar_mulai'] ?? '--:--',
            $jadwal['scan_keluar_sampai'] ?? '--:--',
        ));

        return $absen;
    }

    private function dalamJendelaPulang(string $currentTime, array $jadwal): bool
    {
        return $this->withinWindow(
            $currentTime,
            $jadwal['scan_keluar_mulai'] ?? null,
            $jadwal['scan_keluar_sampai'] ?? null,
        );
    }

    private function withinWindow(string $currentTime, ?string $start, ?string $end): bool
    {
        if (! $start || ! $end) {
            return true;
        }

        $current = $this->normalizeTime($currentTime);
        $startTime = $this->normalizeTime($start);
        $endTime = $this->normalizeTime($end);

        if ($startTime <= $endTime) {
            return $current >= $startTime && $current <= $endTime;
        }

        return $current >= $startTime || $current <= $endTime;
    }

    private function isWindowStarted(string $currentTime, ?string $start): bool
    {
        return $start && $this->normalizeTime($currentTime) >= $this->normalizeTime($start);
    }

    private function normalizeTime(?string $time): ?string
    {
        if (! $time) {
            return null;
        }

        return strlen($time) === 5 ? $time.':00' : $time;
    }

    private function keteranganAbsensi(array $jadwal): ?string
    {
        $parts = array_filter([
            $jadwal['label'] ?? null,
            $jadwal['nama_acara'] ?? null,
            $jadwal['keterangan'] ?? null,
        ]);

        return $parts ? implode(' - ', $parts) : null;
    }

    private function acceptScan(string $message, string $status = 'success'): void
    {
        $this->scanStatus = 'success';
        $this->scanMessage = $message;
        $this->tersimpan = true;

        $this->setScanResult($status);
    }

    private function acceptMessage(string $title, string $message, string $status = 'already_recorded'): void
    {
        $this->scanTitle = $title;
        $this->scanStatus = 'message';
        $this->scanMessage = $message;
        $this->tersimpan = false;

        $this->setScanResult($status);
    }

    private function rejectScan(string $message, string $status = 'failed'): void
    {
        $this->scanStatus = 'error';
        $this->scanMessage = $message;
        $this->tersimpan = false;

        $this->setScanResult($status);
    }

    private function setScanResult(string $status, bool $autoClose = true): void
    {
        $this->scanResult = [
            'id' => (string) Str::uuid(),
            'status' => $status,
            'autoClose' => $autoClose,
            'modal' => $this->modalData(),
        ];
    }

    private function fallbackScanResult(): array
    {
        $this->rejectScan('Proses absensi gagal. Silakan coba kembali.');

        return $this->scanResult;
    }

    private function modalData(): array
    {
        $type = $this->tersimpan ? 'success' : $this->scanStatus;
        $modal = [
            'type' => $type,
            'message' => $this->scanMessage,
        ];

        if ($type === 'message') {
            $modal['title'] = $this->scanTitle;
        }

        if ($type === 'success' && $this->murid) {
            $modal['murid'] = [
                'uuid' => $this->murid->uuid,
                'nama' => $this->murid->nama,
                'rombel' => $this->murid->rombel?->nama_lengkap,
                'nisn' => $this->murid->nisn,
                'nipd' => $this->murid->nipd,
                'tempatLahir' => $this->murid->tempat_lahir,
                'tanggalLahir' => $this->murid->tanggal_lahir?->translatedFormat('d M Y'),
                'jenisKelamin' => $this->murid->jk === 'L' ? 'Laki-laki' : 'Perempuan',
                'agama' => $this->murid->agama,
                'hp' => $this->murid->hp,
                'alamat' => $this->murid->alamat,
                'imagePath' => $this->murid->image_path,
            ];
            $modal['jadwal'] = array_filter([
                'label' => $this->jadwalHariIni['label'] ?? null,
                'jam_masuk' => $this->jadwalHariIni['jam_masuk'] ?? null,
                'jam_pulang' => $this->jadwalHariIni['jam_pulang'] ?? null,
            ]);
        }

        if ($type === 'error') {
            if ($this->murid) {
                $modal['murid'] = [
                    'nama' => $this->murid->nama,
                    'rombel' => $this->murid->rombel?->nama_lengkap,
                ];
            }

            $modal['jadwal'] = array_filter([
                'label' => $this->jadwalHariIni['label'] ?? null,
                'nama_acara' => $this->jadwalHariIni['nama_acara'] ?? null,
                'keterangan' => $this->jadwalHariIni['keterangan'] ?? null,
            ]);
        }

        return $modal;
    }

    private function currentAbsen(Murid $murid, string $today): ?AbsenMurid
    {
        return AbsenMurid::query()
            ->where('murid_id', $murid->id)
            ->where('tanggal', $today)
            ->first();
    }

    private function resetScanState(): void
    {
        $this->scanResult = null;
        $this->tersimpan = false;
        $this->scanStatus = null;
        $this->scanMessage = null;
        $this->scanTitle = null;
        $this->murid = null;
        $this->izinUuid = null;
        $this->isContainsIzin = false;
        $this->jadwalHariIni = [];
    }
}
