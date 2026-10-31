<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Manajemen\Livewire\GenerateQR\Generate;
use Tests\TestCase;

class GenerateQrModelPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_memilih_orientasi_membuka_modal_model(): void
    {
        Livewire::test(Generate::class)
            ->call('pilihModelUntukGenerate', 'horizontal')
            ->assertSet('pendingOrientation', 'horizontal')
            ->assertDispatched('open-generate-model-modal');
    }

    public function test_start_generate_mengirim_event_dengan_model(): void
    {
        Livewire::test(Generate::class)
            ->call('pilihModelUntukGenerate', 'horizontal')
            ->call('startGenerate', 'horizontal', 'baru')
            ->assertDispatched('generate-qr', model: 'baru')
            ->assertDispatched('close-generate-model-modal')
            ->assertSet('pendingOrientation', null);
    }

    public function test_model_tidak_valid_tidak_dispatch(): void
    {
        Livewire::test(Generate::class)
            ->call('pilihModelUntukGenerate', 'diagonal')
            ->assertSet('pendingOrientation', null)
            ->assertNotDispatched('open-generate-model-modal')
            ->call('startGenerate', 'horizontal', 'tak-ada')
            ->assertNotDispatched('generate-qr');
    }
}
