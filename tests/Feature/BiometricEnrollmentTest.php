<?php

namespace Tests\Feature;

use App\Models\Biometric\BiometricTemplate;
use App\Models\Murid\Murid;
use App\Models\Murid\Rombel\Indeks;
use App\Models\Murid\Rombel\Jurusan;
use App\Models\Murid\Rombel\Rombel;
use App\Models\Murid\Rombel\Tingkat;
use App\Models\Role;
use App\Models\User;
use App\Services\Biometric\Contracts\FaceProcessingService;
use App\Services\Biometric\FaceEnrollmentService;
use App\Services\Biometric\FaceIdentificationService;
use App\Services\Biometric\ImagePathResolver;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeFaceProcessingService;
use Tests\TestCase;

class BiometricEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private FakeFaceProcessingService $faceProcessor;

    private User $actor;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config([
            'biometric.quality.min_width' => 320,
            'biometric.quality.min_height' => 320,
            'biometric.quality.min_sharpness' => 1.0,
            'biometric.quality.min_brightness' => 45,
            'biometric.quality.max_brightness' => 215,
            'biometric.quality.min_face_ratio' => 0.10,
            'biometric.quality.edge_margin_ratio' => 0.02,
            'biometric.templates.ttl_months' => 12,
            'biometric.matching.threshold' => 0.60,
        ]);

        $role = Role::query()->create([
            'name' => 'Biometric Test',
            'permissions' => [],
        ]);

        $this->actor = User::query()->create([
            'name' => 'Biometric Officer',
            'email' => 'biometric-officer@example.test',
            'password' => Hash::make('secret-password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->actor);

        $this->faceProcessor = new FakeFaceProcessingService;
        $this->app->instance(FaceProcessingService::class, $this->faceProcessor);
    }

    public function test_missing_photo_is_rejected(): void
    {
        $student = $this->student(['image_path' => null]);
        $service = app(FaceEnrollmentService::class);
        $service->grantConsent($student, $this->actor);

        $evaluation = $service->evaluate($student, $this->actor);

        $this->assertSame('rejected', $evaluation->status);
        $this->assertContains('photo_missing', $this->reasonCodes($evaluation->reasons));
    }

    public function test_invalid_mime_and_corrupt_image_are_rejected(): void
    {
        $student = $this->student();
        $service = app(FaceEnrollmentService::class);
        $service->grantConsent($student, $this->actor);

        $path = Storage::disk('public')->path('testing/not-an-image.jpg');
        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, 'this is not an image');

        $student->update(['image_path' => 'testing/not-an-image.jpg']);
        $evaluation = $service->evaluate($student->fresh(), $this->actor);

        $this->assertSame('rejected', $evaluation->status);
        $this->assertContains('mime_invalid', $this->reasonCodes($evaluation->reasons));
    }

    public function test_no_face_is_rejected(): void
    {
        $student = $this->student();
        $service = app(FaceEnrollmentService::class);
        $service->grantConsent($student, $this->actor);
        $this->faceProcessor->faces = [];

        $evaluation = $service->evaluate($student, $this->actor);

        $this->assertSame('rejected', $evaluation->status);
        $this->assertContains('no_face', $this->reasonCodes($evaluation->reasons));
    }

    public function test_multiple_faces_are_rejected(): void
    {
        $student = $this->student();
        $service = app(FaceEnrollmentService::class);
        $service->grantConsent($student, $this->actor);
        $this->faceProcessor->faces = [$this->face(), $this->face(350, 180, 160, 190)];

        $evaluation = $service->evaluate($student, $this->actor);

        $this->assertSame('rejected', $evaluation->status);
        $this->assertContains('multiple_faces', $this->reasonCodes($evaluation->reasons));
    }

    public function test_blurry_or_dark_photo_is_rejected(): void
    {
        $service = app(FaceEnrollmentService::class);

        $blurry = $this->student(['image_path' => $this->createImage('blurry.jpg', 'flat')]);
        $service->grantConsent($blurry, $this->actor);
        $blurryEvaluation = $service->evaluate($blurry, $this->actor);
        $this->assertSame('rejected', $blurryEvaluation->status);
        $this->assertContains('image_blurry', $this->reasonCodes($blurryEvaluation->reasons));

        $dark = $this->student(['image_path' => $this->createImage('dark.jpg', 'dark')]);
        $service->grantConsent($dark, $this->actor);
        $darkEvaluation = $service->evaluate($dark, $this->actor);
        $this->assertSame('rejected', $darkEvaluation->status);
        $this->assertContains('lighting_too_dark', $this->reasonCodes($darkEvaluation->reasons));
    }

    public function test_consent_missing_or_revoked_blocks_evaluation_and_enrollment(): void
    {
        $student = $this->student();
        $service = app(FaceEnrollmentService::class);

        $evaluation = $service->evaluate($student, $this->actor);
        $this->assertSame('rejected', $evaluation->status);
        $this->assertContains('consent_missing', $this->reasonCodes($evaluation->reasons));

        $this->expectException(DomainException::class);
        $service->enroll($student, $this->actor);
    }

    public function test_revoked_consent_suspends_existing_enrollment(): void
    {
        $student = $this->student();
        $service = app(FaceEnrollmentService::class);
        $this->enrollStudent($student);

        $service->revokeConsent($student->fresh(), $this->actor);

        $this->assertSame('suspended', $student->fresh()->biometricTemplate->status);
        $this->assertSame('consent_revoked', $student->fresh()->biometricTemplate->suspension_reason);

        $identification = app(FaceIdentificationService::class)->identify($this->absolutePath($student));
        $this->assertSame([], $identification['candidates']);
    }

    public function test_inactive_or_deleted_student_cannot_be_enrolled_and_existing_profile_is_suspended(): void
    {
        $inactive = $this->student();
        $service = app(FaceEnrollmentService::class);
        $service->grantConsent($inactive, $this->actor);
        $inactive->update(['status' => 'nonaktif']);

        $evaluation = $service->evaluate($inactive->fresh(), $this->actor);
        $this->assertSame('rejected', $evaluation->status);
        $this->assertContains('student_inactive', $this->reasonCodes($evaluation->reasons));

        try {
            $service->enroll($inactive->fresh(), $this->actor);
            $this->fail('Enrollment should be blocked for inactive students.');
        } catch (DomainException $error) {
            $this->assertStringContainsString('tidak aktif', $error->getMessage());
        }

        $deleted = $this->student();
        $this->enrollStudent($deleted);
        $deleted->delete();

        $this->assertSame('suspended', BiometricTemplate::query()->where('murid_id', $deleted->id)->first()->status);
    }

    public function test_reference_photo_change_marks_enrollment_needs_review(): void
    {
        $student = $this->student();
        $this->enrollStudent($student);

        $student->update(['image_path' => $this->createImage('replacement.jpg')]);

        $template = BiometricTemplate::query()->where('murid_id', $student->id)->first();

        $this->assertSame('needs_review', $template->status);
        $this->assertSame('reference_photo_changed', $template->suspension_reason);
    }

    public function test_expired_or_invalid_template_is_not_eligible_for_identification(): void
    {
        $student = $this->student();
        $this->enrollStudent($student);
        $this->travelTo(now()->setDate(2026, 9, 8)->setTime(7, 0));

        $template = BiometricTemplate::query()->where('murid_id', $student->id)->first();
        $template->update(['expires_at' => now()->subDay()]);

        $identification = app(FaceIdentificationService::class);
        $result = $identification->identify($this->absolutePath($student));

        $this->assertSame([], $result['candidates']);

        $this->expectException(DomainException::class);
        $identification->confirm($student->fresh(), $this->actor, $this->absolutePath($student));
    }

    public function test_low_confidence_candidate_is_not_returned_and_cannot_be_confirmed(): void
    {
        $student = $this->student();
        $this->enrollStudent($student);
        $this->travelTo(now()->setDate(2026, 9, 8)->setTime(7, 0));

        $this->faceProcessor->faces = [$this->face(220, 160, 200, 240, [0.0, 1.0, 0.0])];

        $identification = app(FaceIdentificationService::class);
        $result = $identification->identify($this->absolutePath($student));

        $this->assertSame([], $result['candidates']);

        $this->expectException(DomainException::class);
        $identification->confirm($student->fresh(), $this->actor, $this->absolutePath($student));
    }

    public function test_identification_only_returns_candidates_and_confirmation_uses_attendance_service(): void
    {
        $student = $this->student();
        $this->enrollStudent($student);
        $this->travelTo(now()->setDate(2026, 9, 8)->setTime(7, 0));

        $identification = app(FaceIdentificationService::class);
        $result = $identification->identify($this->absolutePath($student));

        $this->assertCount(1, $result['candidates']);
        $this->assertSame($student->id, $result['candidates'][0]['murid']->id);
        $this->assertDatabaseCount('absen_murid', 0);

        $confirmed = $identification->confirm($student->fresh(), $this->actor, $this->absolutePath($student));

        $this->assertSame('success', $confirmed['scanResult']['status']);
        $this->assertDatabaseHas('absen_murid', [
            'murid_id' => $student->id,
            'tanggal' => '2026-09-08',
        ]);

        $again = $identification->confirm($student->fresh(), $this->actor, $this->absolutePath($student));
        $this->assertSame('already_recorded', $again['scanResult']['status']);
        $this->assertDatabaseCount('absen_murid', 1);
    }

    public function test_enrollment_template_is_encrypted_and_hidden(): void
    {
        $student = $this->student();
        $this->enrollStudent($student);

        $raw = DB::table('biometric_templates')->where('murid_id', $student->id)->value('template_payload');

        $this->assertIsString($raw);
        $this->assertStringNotContainsString('embedding', $raw);
        $this->assertArrayNotHasKey('template_payload', $student->fresh()->biometricTemplate->toArray());

        $faces = $student->fresh()->latestBiometricEvaluation->faces;
        $this->assertIsArray($faces);
        $this->assertArrayNotHasKey('embedding', $faces[0] ?? []);
    }

    public function test_unavailable_processor_blocks_enrollment_but_stores_review_reason(): void
    {
        $this->faceProcessor->available = false;
        $student = $this->student();
        $service = app(FaceEnrollmentService::class);
        $service->grantConsent($student, $this->actor);

        $evaluation = $service->evaluate($student, $this->actor);

        $this->assertSame('needs_review', $evaluation->status);
        $this->assertContains('face_processor_unavailable', $this->reasonCodes($evaluation->reasons));

        $this->expectException(DomainException::class);
        $service->enroll($student->fresh(), $this->actor);
    }

    private function enrollStudent(Murid $student): BiometricTemplate
    {
        $service = app(FaceEnrollmentService::class);
        $service->grantConsent($student, $this->actor);

        return $service->enroll($student->fresh(), $this->actor);
    }

    private function student(array $overrides = []): Murid
    {
        $this->sequence++;

        $rombel = Rombel::create([
            'tahun_masuk' => '2026',
            'tingkat_id' => Tingkat::create(['nama' => 'X '.$this->sequence])->id,
            'jurusan_id' => Jurusan::create(['nama' => 'RPL '.$this->sequence])->id,
            'indeks_id' => Indeks::create(['nama' => 'A '.$this->sequence])->id,
        ]);

        return Murid::create(array_merge([
            'nama' => 'Biometric Student '.$this->sequence,
            'nipd' => 'BIO-'.$this->sequence,
            'nisn' => 'NISN-'.$this->sequence,
            'jk' => 'L',
            'tempat_lahir' => 'Makassar',
            'tanggal_lahir' => '2010-01-01',
            'rombel_id' => $rombel->id,
            'image_path' => $this->createImage('student-'.$this->sequence.'.jpg'),
        ], $overrides));
    }

    private function createImage(string $name, string $mode = 'normal'): string
    {
        $relative = 'testing/'.$name;
        $path = Storage::disk('public')->path($relative);
        @mkdir(dirname($path), 0755, true);

        $image = imagecreatetruecolor(640, 640);

        if ($mode === 'dark') {
            imagefill($image, 0, 0, imagecolorallocate($image, 20, 20, 20));
        } elseif ($mode === 'flat') {
            imagefill($image, 0, 0, imagecolorallocate($image, 128, 128, 128));
        } else {
            imagefill($image, 0, 0, imagecolorallocate($image, 128, 128, 128));

            for ($y = 0; $y < 640; $y += 16) {
                for ($x = 0; $x < 640; $x += 16) {
                    $color = (($x + $y) / 16) % 2 === 0 ? 40 : 220;
                    imagefilledrectangle($image, $x, $y, $x + 15, $y + 15, imagecolorallocate($image, $color, $color, $color));
                }
            }
        }

        imagejpeg($image, $path, 92);
        imagedestroy($image);

        return $relative;
    }

    private function absolutePath(Murid $student): string
    {
        $path = app(ImagePathResolver::class)->resolve($student->image_path);

        $this->assertNotNull($path);

        return $path;
    }

    /** @param array<int, array<string, mixed>> $reasons */
    private function reasonCodes(array $reasons): array
    {
        return array_map(static fn (array $reason): string => $reason['code'], $reasons);
    }

    private function face(int $x = 220, int $y = 160, int $width = 200, int $height = 240, array $embedding = [1.0, 0.0, 0.0]): array
    {
        return [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
            'confidence' => 1.0,
            'embedding' => $embedding,
        ];
    }
}
