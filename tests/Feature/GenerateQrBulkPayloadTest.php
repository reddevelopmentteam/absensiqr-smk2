<?php

namespace Tests\Feature;

use App\Models\Murid\Murid;
use App\Models\Murid\Rombel\Indeks;
use App\Models\Murid\Rombel\Jurusan;
use App\Models\Murid\Rombel\Rombel;
use App\Models\Murid\Rombel\Tingkat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Manajemen\Livewire\GenerateQR\Generate;
use Tests\TestCase;

class GenerateQrBulkPayloadTest extends TestCase
{
    use RefreshDatabase;

    private function seedStudent(): Murid
    {
        $rombel = Rombel::create([
            'tahun_masuk' => '2026',
            'tingkat_id' => Tingkat::create(['nama' => 'X'])->id,
            'jurusan_id' => Jurusan::create(['nama' => 'RPL'])->id,
            'indeks_id' => Indeks::create(['nama' => 'A'])->id,
        ]);

        return Murid::create([
            'nama' => 'Bulk Test', 'nipd' => '100', 'nisn' => '200', 'jk' => 'L',
            'tempat_lahir' => 'Banjarmasin', 'tanggal_lahir' => '2010-01-01', 'rombel_id' => $rombel->id,
        ]);
    }

    public function test_chunk_memuat_tanggal_lahir_untuk_kartu(): void
    {
        $this->seedStudent();

        Livewire::test(Generate::class)
            ->call('startGenerate', 'horizontal', 'standar')
            ->assertDispatched('generate-qr', function (string $event, array $params) {
                if ($event !== 'generate-qr') {
                    return false;
                }

                $rows = $params['dataMurid'] ?? [];

                if (count($rows) !== 1) {
                    return false;
                }

                return ($rows[0]['tanggal_lahir'] ?? null) === '2010-01-01';
            });
    }

    public function test_chunk_memuat_nama_lengkap_rombel_untuk_group_pdf(): void
    {
        $this->seedStudent();

        Livewire::test(Generate::class)
            ->call('startGenerate', 'horizontal', 'standar')
            ->assertDispatched('generate-qr', function (string $event, array $params) {
                if ($event !== 'generate-qr') {
                    return false;
                }

                $rows = $params['dataMurid'] ?? [];

                return ($rows[0]['rombel']['nama_lengkap'] ?? null) === 'X RPL A';
            });
    }
}
