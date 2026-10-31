<?php

namespace Tests\Feature;

use App\Models\Murid\Murid;
use App\Models\Murid\Rombel\Indeks;
use App\Models\Murid\Rombel\Jurusan;
use App\Models\Murid\Rombel\Rombel;
use App\Models\Murid\Rombel\Tingkat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\DataMaster\Livewire\DataMurid\Components\Modal\PilihQr;
use Tests\TestCase;

class PilihModelKartuTest extends TestCase
{
    use RefreshDatabase;

    private function student(): Murid
    {
        $rombel = Rombel::create([
            'tahun_masuk' => '2026',
            'tingkat_id' => Tingkat::create(['nama' => 'X'])->id,
            'jurusan_id' => Jurusan::create(['nama' => 'RPL'])->id,
            'indeks_id' => Indeks::create(['nama' => 'A'])->id,
        ]);

        return Murid::create([
            'nama' => 'Model Test', 'nipd' => '100', 'nisn' => '200', 'jk' => 'L',
            'tempat_lahir' => 'Banjarmasin', 'tanggal_lahir' => '2010-01-01', 'rombel_id' => $rombel->id,
        ]);
    }

    public function test_memilih_orientasi_membuka_modal_model(): void
    {
        $murid = $this->student();

        Livewire::test(PilihQr::class)
            ->call('loadMurid', $murid->id)
            ->call('pilihOrientasi', 'vertical')
            ->assertSet('pendingOrientation', 'vertical')
            ->assertDispatched('close-download-modal')
            ->assertDispatched('open-model-modal');
    }

    public function test_unduh_model_mengirim_event_dengan_model(): void
    {
        $murid = $this->student();

        Livewire::test(PilihQr::class)
            ->call('loadMurid', $murid->id)
            ->call('pilihOrientasi', 'vertical')
            ->call('unduhDenganModel', 'standar')
            ->assertDispatched('generateStudentCardSinglePdf',
                orientation: 'vertical',
                model: 'standar',
                filename: null,
            )
            ->assertDispatched('close-model-modal')
            ->assertSet('pendingOrientation', null);
    }

    public function test_orientasi_dan_model_tidak_valid_tidak_dispatch(): void
    {
        $murid = $this->student();

        Livewire::test(PilihQr::class)
            ->call('loadMurid', $murid->id)
            ->call('pilihOrientasi', 'diagonal')
            ->assertSet('pendingOrientation', null)
            ->assertNotDispatched('open-model-modal')
            ->call('pilihOrientasi', 'vertical')
            ->call('unduhDenganModel', 'tak-ada')
            ->assertNotDispatched('generateStudentCardSinglePdf');
    }

    public function test_kembali_ke_orientasi_membuka_ulang_modal_pertama(): void
    {
        $murid = $this->student();

        Livewire::test(PilihQr::class)
            ->call('loadMurid', $murid->id)
            ->call('pilihOrientasi', 'horizontal')
            ->call('kembaliKeOrientasi')
            ->assertSet('pendingOrientation', null)
            ->assertDispatched('close-model-modal')
            ->assertDispatched('open-download-modal');
    }

    public function test_pratinjau_wireframe_sesuai_orientasi(): void
    {
        Livewire::test(PilihQr::class)
            ->call('pilihOrientasi', 'vertical')
            ->assertSee('data-model-preview="standar-vertical"', false)
            ->assertSee('data-model-preview="baru-vertical"', false)
            ->assertDontSee('solar:card-bold', false);

        Livewire::test(PilihQr::class)
            ->call('pilihOrientasi', 'horizontal')
            ->assertSee('data-model-preview="standar-horizontal"', false)
            ->assertSee('data-model-preview="baru-horizontal"', false)
            ->assertDontSee('solar:card-bold', false);
    }
}
