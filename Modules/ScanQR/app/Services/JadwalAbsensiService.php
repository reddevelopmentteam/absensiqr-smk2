<?php

namespace Modules\ScanQR\Services;

use App\Models\JadwalAbsen;
use App\Models\Murid\Rombel\Rombel;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class JadwalAbsensiService
{
    public function forRombel(?int $rombelId, Carbon|string|null $date = null): array
    {
        $date = $this->resolveDate($date);

        $settings = $this->settings();
        $default = $this->defaultForDate($date, $settings);

        // Tanpa rombel, jadwal default tanggal tersebut menjadi fallback utama.
        if (! $rombelId) {
            return $default;
        }

        $jadwal = JadwalAbsen::query()
            ->withExists('rombelJadwal')
            ->with(['rombelJadwal' => function ($query) use ($rombelId) {
                $query->where('rombel_id', $rombelId);
            }])
            ->where('tanggal', $date->toDateString())
            ->first();

        if (! $jadwal) {
            return $default;
        }

        $detailRombel = $jadwal->rombelJadwal->first();

        if ($detailRombel) {
            // Detail rombel memiliki prioritas atas jadwal event dan jadwal default.
            $scanWindow = $this->scanWindowForRombel($detailRombel, $default);

            return $this->normalize([
                'tanggal' => $date->toDateString(),
                'tipe' => $detailRombel->tipe ?: $jadwal->tipe ?: $default['tipe'],
                'jam_masuk' => null,
                'jam_pulang' => null,
                'scan_masuk_mulai' => $scanWindow['scan_masuk_mulai'],
                'scan_masuk_sampai' => $scanWindow['scan_masuk_sampai'],
                'scan_keluar_mulai' => $scanWindow['scan_keluar_mulai'],
                'scan_keluar_sampai' => $scanWindow['scan_keluar_sampai'],
                'scan_window_source' => $scanWindow['source'],
                'nama_acara' => $jadwal->nama_acara,
                'keterangan' => $detailRombel->keterangan ?: $jadwal->keterangan ?: $default['keterangan'],
                'source' => 'rombel',
            ]);
        }

        // Event dengan detail rombel hanya berlaku untuk rombel yang terdaftar.
        if ($jadwal->rombel_jadwal_exists) {
            return $default;
        }

        // Data lama tanpa detail rombel tetap diperlakukan sebagai jadwal global.
        return $this->normalize([
            'tanggal' => $date->toDateString(),
            'tipe' => $jadwal->tipe ?: $default['tipe'],
            'jam_masuk' => null,
            'jam_pulang' => null,
            'scan_masuk_mulai' => $default['scan_masuk_mulai'] ?? null,
            'scan_masuk_sampai' => $default['scan_masuk_sampai'] ?? null,
            'scan_keluar_mulai' => $default['scan_keluar_mulai'] ?? null,
            'scan_keluar_sampai' => $default['scan_keluar_sampai'] ?? null,
            'nama_acara' => $jadwal->nama_acara,
            'keterangan' => $jadwal->keterangan ?: $default['keterangan'],
            'source' => 'global',
        ]);
    }

    public function forRombels(iterable $rombelIds, Carbon|string|null $date = null): array
    {
        $date = $this->resolveDate($date);

        $rombelIds = collect($rombelIds)
            ->filter()
            ->map(fn ($rombelId) => (int) $rombelId)
            ->unique()
            ->values();

        if ($rombelIds->isEmpty()) {
            return [];
        }

        $settings = $this->settings();
        $default = $this->defaultForDate($date, $settings);
        $jadwalRombel = $rombelIds
            ->mapWithKeys(fn (int $rombelId) => [$rombelId => $default])
            ->all();

        $jadwal = JadwalAbsen::query()
            ->withExists('rombelJadwal')
            ->with(['rombelJadwal' => function ($query) use ($rombelIds) {
                $query->whereIn('rombel_id', $rombelIds);
            }])
            ->where('tanggal', $date->toDateString())
            ->first();

        if (! $jadwal) {
            return $jadwalRombel;
        }

        // Terapkan detail event hanya kepada rombel yang memang memilikinya.
        foreach ($jadwal->rombelJadwal as $detailRombel) {
            $scanWindow = $this->scanWindowForRombel($detailRombel, $default);

            $jadwalRombel[$detailRombel->rombel_id] = $this->normalize([
                'tanggal' => $date->toDateString(),
                'tipe' => $detailRombel->tipe ?: $jadwal->tipe ?: $default['tipe'],
                'jam_masuk' => null,
                'jam_pulang' => null,
                'scan_masuk_mulai' => $scanWindow['scan_masuk_mulai'],
                'scan_masuk_sampai' => $scanWindow['scan_masuk_sampai'],
                'scan_keluar_mulai' => $scanWindow['scan_keluar_mulai'],
                'scan_keluar_sampai' => $scanWindow['scan_keluar_sampai'],
                'scan_window_source' => $scanWindow['source'],
                'nama_acara' => $jadwal->nama_acara,
                'keterangan' => $detailRombel->keterangan ?: $jadwal->keterangan ?: $default['keterangan'],
                'source' => 'rombel',
            ]);
        }

        if ($jadwal->rombel_jadwal_exists) {
            return $jadwalRombel;
        }

        // Tanpa detail rombel, satu jadwal global berlaku untuk semua rombel yang diminta.
        $global = $this->normalize([
            'tanggal' => $date->toDateString(),
            'tipe' => $jadwal->tipe ?: $default['tipe'],
            'jam_masuk' => null,
            'jam_pulang' => null,
            'scan_masuk_mulai' => $default['scan_masuk_mulai'] ?? null,
            'scan_masuk_sampai' => $default['scan_masuk_sampai'] ?? null,
            'scan_keluar_mulai' => $default['scan_keluar_mulai'] ?? null,
            'scan_keluar_sampai' => $default['scan_keluar_sampai'] ?? null,
            'nama_acara' => $jadwal->nama_acara,
            'keterangan' => $jadwal->keterangan ?: $default['keterangan'],
            'source' => 'global',
        ]);

        return $rombelIds
            ->mapWithKeys(fn (int $rombelId) => [$rombelId => $global])
            ->all();
    }

    public function bolehScan(array $jadwal): bool
    {
        return ! in_array($jadwal['tipe'], ['libur', 'pjj'], true);
    }

    public function isScanWindowOpen(?Carbon $now = null): bool
    {
        $now ??= now();
        $rombelIds = Rombel::query()->pluck('id');

        if ($rombelIds->isEmpty()) {
            return false;
        }

        $currentTime = $now->format('H:i:s');

        foreach ($this->forRombels($rombelIds, $now) as $jadwal) {
            if (! $this->bolehScan($jadwal)) {
                continue;
            }

            foreach ([['scan_masuk_mulai', 'scan_masuk_sampai'], ['scan_keluar_mulai', 'scan_keluar_sampai']] as [$startKey, $endKey]) {
                $start = $this->normalizeWindowTime($jadwal[$startKey] ?? null);
                $end = $this->normalizeWindowTime($jadwal[$endKey] ?? null);

                if ($start === null || $end === null) {
                    continue;
                }

                $withinWindow = $start <= $end
                    ? $currentTime >= $start && $currentTime <= $end
                    : $currentTime >= $start || $currentTime <= $end;

                if ($withinWindow) {
                    return true;
                }
            }
        }

        return false;
    }

    private function normalizeWindowTime($time): ?string
    {
        if (! $time) {
            return null;
        }

        $time = substr((string) $time, 0, 8);

        return strlen($time) === 5 ? $time.':00' : $time;
    }

    public function labelTipe(?string $tipe): string
    {
        return match ($tipe) {
            'normal' => 'Masuk Normal',
            'pulang_cepat' => 'Pulang Cepat',
            'pjj' => 'PJJ',
            'libur' => 'Libur',
            'khusus' => 'Hari Spesial',
            default => 'Jadwal Khusus',
        };
    }

    private function settings(): array
    {
        return Setting::query()
            ->whereIn('key', [
                'jadwal.scan_masuk_mulai',
                'jadwal.scan_masuk_sampai',
                'jadwal.scan_keluar_mulai',
                'jadwal.scan_keluar_sampai',
                'jadwal.scan_keluar_jumat_mulai',
                'jadwal.scan_keluar_jumat_sampai',
            ])
            ->pluck('value', 'key')
            ->all();
    }

    private function defaultForDate(Carbon $date, array $settings): array
    {
        $scanWindow = $this->scanWindow($settings, $date->isFriday());

        // Akhir pekan selalu memakai jadwal libur default.
        if ($date->isSaturday() || $date->isSunday()) {
            return $this->normalize([
                'tanggal' => $date->toDateString(),
                'tipe' => 'libur',
                'jam_masuk' => null,
                'jam_pulang' => null,
                'scan_masuk_mulai' => null,
                'scan_masuk_sampai' => null,
                'scan_keluar_mulai' => null,
                'scan_keluar_sampai' => null,
                'nama_acara' => null,
                'keterangan' => 'Libur default Sabtu/Minggu',
                'source' => 'default',
            ]);
        }

        return $this->normalize([
            'tanggal' => $date->toDateString(),
            'tipe' => 'normal',
            'jam_masuk' => null,
            'jam_pulang' => null,
            'scan_masuk_mulai' => $scanWindow['scan_masuk_mulai'],
            'scan_masuk_sampai' => $scanWindow['scan_masuk_sampai'],
            'scan_keluar_mulai' => $scanWindow['scan_keluar_mulai'],
            'scan_keluar_sampai' => $scanWindow['scan_keluar_sampai'],
            'nama_acara' => $date->isFriday() ? 'Jadwal Jumat' : null,
            'keterangan' => $date->isFriday() ? 'Jadwal default khusus Jumat' : null,
            'source' => 'default',
        ]);
    }

    private function scanWindow(array $settings, bool $isFriday): array
    {
        $scanMasukSampai = $settings['jadwal.scan_masuk_sampai']
            ?? $this->addMinutes(
                config('waktu-absensi.scan.masuk.sampai'),
                (int) config('waktu-absensi.scan.masuk.toleransi', 5),
            );

        return [
            'scan_masuk_mulai' => $settings['jadwal.scan_masuk_mulai'] ?? config('waktu-absensi.scan.masuk.mulai'),
            'scan_masuk_sampai' => $scanMasukSampai,
            'scan_keluar_mulai' => $isFriday
                ? ($settings['jadwal.scan_keluar_jumat_mulai'] ?? config('waktu-absensi.scan.jumat.pulang.mulai'))
                : ($settings['jadwal.scan_keluar_mulai'] ?? config('waktu-absensi.scan.pulang.mulai')),
            'scan_keluar_sampai' => $isFriday
                ? ($settings['jadwal.scan_keluar_jumat_sampai'] ?? config('waktu-absensi.scan.jumat.pulang.sampai'))
                : ($settings['jadwal.scan_keluar_sampai'] ?? config('waktu-absensi.scan.pulang.sampai')),
        ];
    }

    private function scanWindowForRombel(object $detailRombel, array $default): array
    {
        // Gunakan window default bila skema atau pilihan window rombel belum tersedia.
        if (! $this->supportsRombelScanWindow() || ! ($detailRombel->gunakan_window_scan ?? false)) {
            return [
                'scan_masuk_mulai' => $default['scan_masuk_mulai'] ?? null,
                'scan_masuk_sampai' => $default['scan_masuk_sampai'] ?? null,
                'scan_keluar_mulai' => $default['scan_keluar_mulai'] ?? null,
                'scan_keluar_sampai' => $default['scan_keluar_sampai'] ?? null,
                'source' => 'default',
            ];
        }

        return [
            'scan_masuk_mulai' => $this->formatTime($detailRombel->scan_masuk_mulai ?? null) ?? ($default['scan_masuk_mulai'] ?? null),
            'scan_masuk_sampai' => $this->formatTime($detailRombel->scan_masuk_sampai ?? null) ?? ($default['scan_masuk_sampai'] ?? null),
            'scan_keluar_mulai' => $this->formatTime($detailRombel->scan_keluar_mulai ?? null) ?? ($default['scan_keluar_mulai'] ?? null),
            'scan_keluar_sampai' => $this->formatTime($detailRombel->scan_keluar_sampai ?? null) ?? ($default['scan_keluar_sampai'] ?? null),
            'source' => 'rombel',
        ];
    }

    private function supportsRombelScanWindow(): bool
    {
        static $supported = null;

        if ($supported !== null) {
            return $supported;
        }

        if (! Schema::hasTable('jadwal_absen_rombel')) {
            return $supported = false;
        }

        return $supported = Schema::hasColumns('jadwal_absen_rombel', [
            'gunakan_window_scan',
            'scan_masuk_mulai',
            'scan_masuk_sampai',
            'scan_keluar_mulai',
            'scan_keluar_sampai',
        ]);
    }

    private function formatTime($time): ?string
    {
        if (! $time) {
            return null;
        }

        return substr((string) $time, 0, 5);
    }

    private function addMinutes(string $time, int $minutes): string
    {
        return Carbon::createFromFormat('H:i', $this->formatTime($time))
            ->addMinutes($minutes)
            ->format('H:i');
    }

    private function normalize(array $jadwal): array
    {
        $tipe = $jadwal['tipe'] ?: 'normal';

        if (in_array($tipe, ['libur', 'pjj'], true)) {
            $jadwal['jam_masuk'] = null;
            $jadwal['jam_pulang'] = null;
            $jadwal['scan_masuk_mulai'] = null;
            $jadwal['scan_masuk_sampai'] = null;
            $jadwal['scan_keluar_mulai'] = null;
            $jadwal['scan_keluar_sampai'] = null;
        }

        $jadwal['tipe'] = $tipe;
        $jadwal['label'] = $this->labelTipe($tipe);

        return $jadwal;
    }

    private function resolveDate(Carbon|string|null $date): Carbon
    {
        return $date instanceof Carbon
            ? $date->copy()
            : Carbon::parse($date ?: now()->toDateString());
    }
}
