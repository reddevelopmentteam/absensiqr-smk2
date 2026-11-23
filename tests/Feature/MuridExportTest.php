<?php

namespace Tests\Feature;

use App\Exports\Murid\MuridExport;
use App\Models\Guru\Guru;
use App\Models\Murid\Murid;
use App\Models\Murid\Rombel\Indeks;
use App\Models\Murid\Rombel\Jurusan;
use App\Models\Murid\Rombel\Rombel;
use App\Models\Murid\Rombel\Tingkat;
use App\Models\Role;
use App\Models\User;
use App\Services\Murid\MuridExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Modules\DataMaster\Livewire\DataMurid\Components\Modal\Export;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class MuridExportTest extends TestCase
{
    use RefreshDatabase;

    private function rombel(string $tingkat = 'X', string $indeks = 'A', ?Guru $wali = null): Rombel
    {
        return Rombel::create([
            'tahun_masuk' => '2026',
            'tingkat_id' => Tingkat::create(['nama' => $tingkat])->id,
            'jurusan_id' => Jurusan::create(['nama' => 'RPL-'.$tingkat.$indeks])->id,
            'indeks_id' => Indeks::create(['nama' => $indeks.'-'.$tingkat])->id,
            'wali_guru_id' => $wali?->id,
        ]);
    }

    private function murid(Rombel $rombel, string $nama, string $nipd, string $nisn, string $status = 'aktif'): Murid
    {
        return Murid::create([
            'nama' => $nama,
            'nipd' => $nipd,
            'nisn' => $nisn,
            'jk' => 'L',
            'tempat_lahir' => 'Banjarmasin',
            'tanggal_lahir' => '2010-01-01',
            'status' => $status,
            'rombel_id' => $rombel->id,
        ]);
    }

    public function test_query_filters_by_rombel_status_and_search(): void
    {
        $kelasA = $this->rombel('X', 'A');
        $kelasB = $this->rombel('XI', 'B');

        $this->murid($kelasA, 'Ahmad Aktif', '100', '9001');
        $this->murid($kelasA, 'Budi Lulus', '101', '9002', 'lulus');
        $this->murid($kelasB, 'Citra Aktif', '102', '9003');

        $service = app(MuridExportService::class);

        $this->assertSame(
            ['Ahmad Aktif'],
            $service->query(['rombel_id' => $kelasA->id, 'status' => 'aktif'])->pluck('nama')->all(),
        );

        $this->assertSame(
            ['Budi Lulus'],
            $service->query(['rombel_id' => $kelasA->id, 'status' => 'nonaktif'])->pluck('nama')->all(),
        );

        $this->assertCount(3, $service->query(['status' => 'semua'])->pluck('nama')->all());

        $this->assertSame(
            ['Citra Aktif'],
            $service->query(['search' => 'Citra', 'status' => 'semua'])->pluck('nama')->all(),
        );

        // Batasan akses (mis. wali kelas) selalu dipakai.
        $this->assertSame(
            ['Ahmad Aktif', 'Budi Lulus'],
            $service->query(['status' => 'semua'], [$kelasA->id])->pluck('nama')->all(),
        );
    }

    public function test_excel_file_contains_headers_and_student_rows(): void
    {
        $rombel = $this->rombel();
        $this->murid($rombel, 'Ahmad Export', '100', '9001');

        $query = app(MuridExportService::class)->query(['status' => 'aktif']);

        $content = Excel::raw(new MuridExport($query, true), \Maatwebsite\Excel\Excel::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'murid-export').'.xlsx';
        file_put_contents($path, $content);

        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame('No', $sheet->getCell('A1')->getValue());
        $this->assertSame('Nama', $sheet->getCell('B1')->getValue());
        $this->assertSame('NIPD', $sheet->getCell('C1')->getValue());
        $this->assertSame('Tanggal Lahir', $sheet->getCell('G1')->getValue());
        $this->assertSame('Rombel Saat Ini', $sheet->getCell('T1')->getValue());
        $this->assertSame('Status', $sheet->getCell('U1')->getValue());
        $this->assertSame('Foto (URL)', $sheet->getCell('V1')->getValue());

        $this->assertSame(1, $sheet->getCell('A2')->getValue());
        $this->assertSame('Ahmad Export', $sheet->getCell('B2')->getValue());
        $this->assertSame('100', $sheet->getCell('C2')->getValue());
        $this->assertSame('9001', $sheet->getCell('D2')->getValue());
        $this->assertSame('2010-01-01', $sheet->getCell('G2')->getValue());
        $this->assertSame($rombel->nama_lengkap, $sheet->getCell('T2')->getValue());
        $this->assertSame('Aktif', $sheet->getCell('U2')->getValue());

        @unlink($path);
    }

    public function test_modal_exports_xlsx_and_reports_matching_total(): void
    {
        $rombel = $this->rombel();
        $this->murid($rombel, 'Ahmad Export', '100', '9001');
        $this->murid($rombel, 'Budi Export', '101', '9002');

        Livewire::test(Export::class)
            ->assertSet('totalMurid', 2)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->set('filterRombelId', $rombel->id)
            ->assertSet('totalMurid', 2)
            ->call('export')
            ->assertFileDownloaded();
    }

    public function test_wali_kelas_only_exports_their_own_class(): void
    {
        $role = Role::create([
            'name' => 'Wali Kelas',
            'permissions' => ['data-murid'],
        ]);

        $user = User::create([
            'name' => 'Wali Kelas Uji',
            'email' => 'wali-kelas-export@example.test',
            'password' => 'rahasia-uji',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $user->roles()->sync([$role->id]);
        $guru = Guru::create([
            'user_id' => $user->id,
            'nama' => 'Wali Kelas Uji',
            'jk' => 'L',
            'nip' => '1987654321',
        ]);

        $kelasWali = $this->rombel('X', 'A', $guru);
        $kelasLain = $this->rombel('XI', 'B');

        $this->murid($kelasWali, 'Murid Kelas Wali', '100', '9001');
        $this->murid($kelasLain, 'Murid Kelas Lain', '101', '9002');

        $this->actingAs($user);

        $component = Livewire::test(Export::class)
            ->assertSet('isWaliKelas', true)
            ->assertSet('totalMurid', 1)
            ->call('export')
            ->assertFileDownloaded();

        $this->assertSame($kelasWali->id, $component->get('filterRombelId'));
    }

    public function test_export_button_is_rendered_on_the_student_page(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'permissions' => ['data-murid'],
        ]);

        $user = User::create([
            'name' => 'Admin Export',
            'email' => 'admin-export@example.test',
            'password' => 'rahasia-uji',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('data-murid'))
            ->assertOk()
            ->assertSee('Export XLSX')
            ->assertSee('Export Data Murid');
    }
}
