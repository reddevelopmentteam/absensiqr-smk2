<?php

namespace Tests\Feature;

use App\Models\Murid\Murid;
use App\Models\Murid\Rombel\Indeks;
use App\Models\Murid\Rombel\Jurusan;
use App\Models\Murid\Rombel\Rombel;
use App\Models\Murid\Rombel\Tingkat;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryApiTest extends TestCase
{
    use RefreshDatabase;

    private static int $userCounter = 0;

    private function libraryUser(): User
    {
        self::$userCounter++;
        $role = Role::create([
            'name' => 'SKENDA Library '.self::$userCounter,
            'permissions' => [],
        ]);

        return User::create([
            'name' => 'SKENDA Library',
            'email' => 'library+'.self::$userCounter.'@example.test',
            'password' => 'password',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    private function token(User $user, array $abilities = ['library-read']): string
    {
        // Guard Sanctum di-cache per request; reset agar token terbaru dipakai
        // saat beberapa request dijalankan dalam satu test.
        $this->app['auth']->forgetGuards();

        return $user->createToken('skenda-library', $abilities)->plainTextToken;
    }

    private function class(string $indeksNama = 'A'): Rombel
    {
        $tingkat = Tingkat::firstOrCreate(['nama' => 'XI']);
        $jurusan = Jurusan::firstOrCreate(['nama' => 'PPLG']);
        $indeks = Indeks::firstOrCreate(['nama' => $indeksNama]);

        return Rombel::firstOrCreate([
            'tingkat_id' => $tingkat->id,
            'jurusan_id' => $jurusan->id,
            'indeks_id' => $indeks->id,
        ]);
    }

    private function student(Rombel $rombel, string $name = 'Siswa Perpustakaan', string $nipd = '123456', string $nisn = '0012345678'): Murid
    {
        return Murid::create([
            'nama' => $name,
            'nipd' => $nipd,
            'nisn' => $nisn,
            'jk' => 'L',
            'tempat_lahir' => 'Banjarmasin',
            'tanggal_lahir' => '2010-01-01',
            'rombel_id' => $rombel->id,
            'status' => 'aktif',
        ]);
    }

    public function test_guest_is_unauthenticated_and_token_requires_library_read_ability(): void
    {
        $this->getJson('/api/v1/library/students')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
            ]);

        $user = $this->libraryUser();

        $this->getJson('/api/v1/library/students', [
            'Authorization' => 'Bearer '.$this->token($user, ['other-ability']),
        ])->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Token does not have the required library-read ability.',
                'data' => null,
            ]);

        $authorizedUser = $this->libraryUser();

        $this->getJson('/api/v1/library/students', [
            'Authorization' => 'Bearer '.$this->token($authorizedUser),
        ])->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Student data retrieved successfully',
            ]);
    }

    public function test_pagination_search_status_and_class_filters_work(): void
    {
        $rombel = $this->class();
        $other = $this->class('B');
        $student = $this->student($rombel, 'Ahmad Perpustakaan', '111111');
        $this->student($other, 'Budi Kelas Lain', '222222', '0022334455');

        $response = $this->getJson('/api/v1/library/students?per_page=1', [
            'Authorization' => 'Bearer '.$this->token($this->libraryUser()),
        ])->assertStatus(200);

        $response->assertJsonPath('data.total', 2);
        $response->assertJsonPath('data.per_page', 1);
        $response->assertJsonPath('data.current_page', 1);
        $response->assertJsonPath('data.last_page', 2);
        $this->assertCount(1, $response->json('data.data'));

        $this->getJson('/api/v1/library/students?search=Ahmad', [
            'Authorization' => 'Bearer '.$this->token($this->libraryUser()),
        ])->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.name', 'Ahmad Perpustakaan');

        $this->getJson('/api/v1/library/students?search=222222', [
            'Authorization' => 'Bearer '.$this->token($this->libraryUser()),
        ])->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.nis', '222222');

        $this->getJson('/api/v1/library/students?class_id='.$rombel->id, [
            'Authorization' => 'Bearer '.$this->token($this->libraryUser()),
        ])->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.class.id', $rombel->id)
            ->assertJsonPath('data.data.0.class.name', 'XI PPLG A');

        $student->update(['status' => 'lulus']);

        $this->getJson('/api/v1/library/students?status=active', [
            'Authorization' => 'Bearer '.$this->token($this->libraryUser()),
        ])->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.name', 'Budi Kelas Lain');

        $this->getJson('/api/v1/library/students?status=inactive', [
            'Authorization' => 'Bearer '.$this->token($this->libraryUser()),
        ])->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.name', 'Ahmad Perpustakaan')
            ->assertJsonPath('data.data.0.is_active', false);

        $this->getJson('/api/v1/library/students?class_id=999999', [
            'Authorization' => 'Bearer '.$this->token($this->libraryUser()),
        ])->assertStatus(422);

        $this->getJson('/api/v1/library/students?per_page=101', [
            'Authorization' => 'Bearer '.$this->token($this->libraryUser()),
        ])->assertStatus(422);
    }

    public function test_student_photo_url_is_absolute_and_null_when_missing(): void
    {
        $rombel = $this->class();
        $token = $this->token($this->libraryUser());

        $withoutPhoto = $this->student($rombel, 'Tanpa Foto', '111222', '0001112223');
        $this->getJson('/api/v1/library/students/'.$withoutPhoto->uuid, [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(200)
            ->assertJsonPath('data.photo_url', null);

        $absolute = $this->student($rombel, 'Foto Absolut', '111333', '0001113334');
        $absolute->update(['image_path' => 'https://cdn.sekolah.sch.id/murid/a.png']);
        $this->getJson('/api/v1/library/students/'.$absolute->uuid, [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(200)
            ->assertJsonPath('data.photo_url', 'https://cdn.sekolah.sch.id/murid/a.png');

        $relative = $this->student($rombel, 'Foto Relatif', '111444', '0001114445');
        $relative->update(['image_path' => 'murid/xi-pplg-a/b.png']);
        $this->getJson('/api/v1/library/students/'.$relative->uuid, [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(200)
            ->assertJsonPath('data.photo_url', Storage::disk('public')->url('murid/xi-pplg-a/b.png'));
    }

    public function test_student_can_be_found_by_uuid_and_nis(): void
    {
        $rombel = $this->class();
        $student = $this->student($rombel, 'Siswa UUID', '998877');
        $token = $this->token($this->libraryUser());

        $this->getJson('/api/v1/library/students/'.$student->uuid, [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(200)
            ->assertJsonPath('data.uuid', $student->uuid)
            ->assertJsonPath('data.nis', '998877')
            ->assertJsonPath('data.nipd', '998877')
            ->assertJsonPath('data.nisn', '0012345678')
            ->assertJsonPath('data.name', 'Siswa UUID')
            ->assertJsonPath('data.birth_date', '2010-01-01')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.class.name', 'XI PPLG A');

        $this->getJson('/api/v1/library/students/by-nis/998877', [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(200)
            ->assertJsonPath('data.uuid', $student->uuid)
            ->assertJsonPath('data.nis', '998877');

        $this->getJson('/api/v1/library/students/'.$student->uuid.'-tidak-ada', [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Student not found',
                'data' => null,
            ]);

        $this->getJson('/api/v1/library/students/by-nis/000000', [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Student not found');
    }

    public function test_qr_endpoint_uses_actual_qr_payload_uuid(): void
    {
        $rombel = $this->class();
        $student = $this->student($rombel, 'Siswa QR', '556677');
        $izinUuid = (string) \Illuminate\Support\Str::uuid();
        $token = $this->token($this->libraryUser());

        $this->getJson('/api/v1/library/students/by-qr/'.rawurlencode($student->uuid), [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(200)
            ->assertJsonPath('data.uuid', $student->uuid)
            ->assertJsonPath('data.name', 'Siswa QR');

        // Format kartu izin: {murid_uuid}>{izin_uuid}.
        $this->getJson('/api/v1/library/students/by-qr/'.rawurlencode($student->uuid.'>'.$izinUuid), [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(200)
            ->assertJsonPath('data.uuid', $student->uuid);

        $this->getJson('/api/v1/library/students/by-qr/'.rawurlencode('bukan-uuid'), [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid QR token');

        $this->getJson('/api/v1/library/students/by-qr/'.rawurlencode((string) \Illuminate\Support\Str::uuid()), [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(404)
            ->assertJsonPath('message', 'Student not found');
    }

    public function test_changes_endpoint_reports_updates_status_changes_and_soft_deletes(): void
    {
        $rombel = $this->class();
        $student = $this->student($rombel, 'Siswa Sinkron', '445566');
        $token = $this->token($this->libraryUser());
        $since = Carbon::now()->subDay()->toIso8601String();

        $this->getJson('/api/v1/library/students/changes?updated_since='.rawurlencode($since), [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.uuid', $student->uuid)
            ->assertJsonPath('data.data.0.deleted_at', null);

        $student->update(['status' => 'nonaktif']);

        $this->getJson('/api/v1/library/students/changes?updated_since='.rawurlencode($since), [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(200)
            ->assertJsonPath('data.data.0.status', 'inactive')
            ->assertJsonPath('data.data.0.is_active', false);

        $student->delete();

        $changes = $this->getJson('/api/v1/library/students/changes?updated_since='.rawurlencode($since), [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(200);

        $changes->assertJsonPath('data.data.0.uuid', $student->uuid);
        $this->assertNotNull($changes->json('data.data.0.deleted_at'));

        $afterDelete = Carbon::now()->addSecond()->toIso8601String();

        $this->getJson('/api/v1/library/students/changes?updated_since='.rawurlencode($afterDelete), [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(200)
            ->assertJsonPath('data.total', 0);

        $this->getJson('/api/v1/library/students/changes', [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(422);
    }

    public function test_classes_endpoint_returns_class_id_and_name(): void
    {
        $rombel = $this->class();

        $this->getJson('/api/v1/library/classes', [
            'Authorization' => 'Bearer '.$this->token($this->libraryUser()),
        ])->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $rombel->id,
                'name' => 'XI PPLG A',
                'tingkat' => 'XI',
                'jurusan' => 'PPLG',
                'indeks' => 'A',
            ]);
    }

    public function test_response_does_not_expose_sensitive_student_columns(): void
    {
        $rombel = $this->class();
        $student = $this->student($rombel, 'Siswa Privasi', '334455');

        $response = $this->getJson('/api/v1/library/students/'.$student->uuid, [
            'Authorization' => 'Bearer '.$this->token($this->libraryUser()),
        ])->assertStatus(200);

        $keys = array_keys($response->json('data'));

        $this->assertSame(
            ['uuid', 'nis', 'nipd', 'nisn', 'name', 'photo_url', 'birth_date', 'status', 'is_active', 'class', 'updated_at', 'deleted_at'],
            $keys,
        );

        $response->assertJsonMissingPath('data.password');
        $response->assertJsonMissingPath('data.email');
        $response->assertJsonMissingPath('data.alamat');
        $response->assertJsonMissingPath('data.hp');
        $response->assertJsonMissingPath('data.nama_ayah');
        $response->assertJsonMissingPath('data.nama_ibu');
        $response->assertJsonMissingPath('data.nama_wali');
    }

    public function test_api_is_read_only(): void
    {
        $token = $this->token($this->libraryUser());
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->postJson('/api/v1/library/students', [], $headers)->assertStatus(405);
        $this->putJson('/api/v1/library/students/'.(string) \Illuminate\Support\Str::uuid(), [], $headers)->assertStatus(405);
        $this->patchJson('/api/v1/library/students/'.(string) \Illuminate\Support\Str::uuid(), [], $headers)->assertStatus(405);
        $this->deleteJson('/api/v1/library/students/'.(string) \Illuminate\Support\Str::uuid(), [], $headers)->assertStatus(405);
    }
}
