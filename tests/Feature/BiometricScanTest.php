<?php

namespace Tests\Feature;

use App\Models\Murid\Murid;
use App\Models\Murid\Rombel\Indeks;
use App\Models\Murid\Rombel\Jurusan;
use App\Models\Murid\Rombel\Rombel;
use App\Models\Murid\Rombel\Tingkat;
use App\Models\Role;
use App\Models\User;
use App\Services\Biometric\Contracts\FaceProcessingService;
use App\Services\Biometric\FaceEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\ScanQR\Livewire\Biometrik\Scan;
use Tests\Support\FakeFaceProcessingService;
use Tests\TestCase;

class BiometricScanTest extends TestCase
{
    use RefreshDatabase;

    private FakeFaceProcessingService $faceProcessor;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        config([
            'biometric.quality.min_sharpness' => 1.0,
            'biometric.matching.threshold' => 0.60,
        ]);

        $role = Role::query()->forceCreate(['id' => 1, 'name' => 'Super Admin']);
        $this->user = User::query()->create([
            'name' => 'Face Operator',
            'email' => 'face-operator@example.test',
            'password' => Hash::make('secret-password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $this->actingAs($this->user);

        $this->faceProcessor = new FakeFaceProcessingService;
        $this->app->instance(FaceProcessingService::class, $this->faceProcessor);
    }

    public function test_officer_can_identify_candidates_and_confirm_attendance(): void
    {
        $student = $this->student();
        $this->enroll($student);
        $this->travelTo(now()->setDate(2026, 9, 8)->setTime(7, 0));

        $component = Livewire::test(Scan::class)
            ->set('photo', UploadedFile::fake()->image('probe.jpg', 640, 640))
            ->call('identify');

        $candidates = $component->get('candidates');
        $this->assertCount(1, $candidates);
        $this->assertSame($student->id, $candidates[0]['id']);
        $this->assertSame([], Storage::disk('local')->files('biometric-probes'));
        $this->assertDatabaseCount('absen_murid', 0);

        $component->call('confirm', $student->id)
            ->assertSet('messageType', 'success');

        $this->assertDatabaseHas('absen_murid', [
            'murid_id' => $student->id,
            'tanggal' => '2026-09-08',
            'waktu_masuk' => '07:00:00',
        ]);
    }

    public function test_confirm_rejects_stale_candidate_ids(): void
    {
        $student = $this->student();

        Livewire::test(Scan::class)
            ->call('confirm', $student->id)
            ->assertSet('messageType', 'error');

        $this->assertDatabaseCount('absen_murid', 0);
    }

    private function student(): Murid
    {
        $rombel = Rombel::create([
            'tahun_masuk' => '2026',
            'tingkat_id' => Tingkat::create(['nama' => 'X'])->id,
            'jurusan_id' => Jurusan::create(['nama' => 'RPL'])->id,
            'indeks_id' => Indeks::create(['nama' => 'A'])->id,
        ]);

        $path = Storage::disk('public')->path('testing/face-student.jpg');
        @mkdir(dirname($path), 0755, true);
        $image = imagecreatetruecolor(640, 640);
        imagefill($image, 0, 0, imagecolorallocate($image, 128, 128, 128));

        for ($y = 0; $y < 640; $y += 16) {
            for ($x = 0; $x < 640; $x += 16) {
                $color = (($x + $y) / 16) % 2 === 0 ? 40 : 220;
                imagefilledrectangle($image, $x, $y, $x + 15, $y + 15, imagecolorallocate($image, $color, $color, $color));
            }
        }

        imagejpeg($image, $path, 92);
        imagedestroy($image);

        return Murid::create([
            'nama' => 'Face Candidate',
            'nipd' => 'FACE-1',
            'nisn' => 'FACE-NISN-1',
            'jk' => 'L',
            'tempat_lahir' => 'Makassar',
            'tanggal_lahir' => '2010-01-01',
            'rombel_id' => $rombel->id,
            'image_path' => 'testing/face-student.jpg',
        ]);
    }

    private function enroll(Murid $student): void
    {
        $service = app(FaceEnrollmentService::class);
        $service->grantConsent($student, $this->user);
        $service->enroll($student->fresh(), $this->user);
    }
}
