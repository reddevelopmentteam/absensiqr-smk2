<?php

namespace App\Http\Controllers\Api\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Library\StudentResource;
use App\Services\Api\Library\LibraryStudentService;
use App\Support\Api\LibraryApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LibraryStudentController extends Controller
{
    public function __construct(
        private readonly LibraryStudentService $service,
    ) {
    }

    public function index(Request $request)
    {
        $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.LibraryStudentService::MAX_PER_PAGE],
            'search' => ['nullable', 'string', 'max:191'],
            'status' => ['nullable', 'string', 'max:50'],
            'class_id' => ['nullable', 'integer', 'min:1', 'exists:rombel,id'],
        ]);

        $students = $this->service->index($request);

        return LibraryApiResponse::paginator(
            $students,
            StudentResource::collection($students),
            'Student data retrieved successfully',
        );
    }

    public function show(string $student)
    {
        $studentModel = $this->service->findByUuid($student);

        if (! $studentModel) {
            return LibraryApiResponse::error('Student not found', 404);
        }

        return LibraryApiResponse::success(
            (new StudentResource($studentModel))->resolve(request()),
            'Student data retrieved successfully',
        );
    }

    public function byNis(Request $request, string $nis)
    {
        $validation = Validator::make(['nis' => $nis], [
            'nis' => ['required', 'string', 'max:191'],
        ]);

        if ($validation->fails()) {
            return LibraryApiResponse::error('Invalid NIS', 422, $validation->errors()->toArray());
        }

        $student = $this->service->findByNis($nis);

        if (! $student) {
            return LibraryApiResponse::error('Student not found', 404);
        }

        return LibraryApiResponse::success(
            (new StudentResource($student))->resolve($request),
            'Student data retrieved successfully',
        );
    }

    public function byQr(Request $request, string $token)
    {
        $validation = Validator::make(['token' => $token], [
            'token' => ['required', 'string', 'max:191'],
        ]);

        if ($validation->fails()) {
            return LibraryApiResponse::error('Invalid QR token', 422, $validation->errors()->toArray());
        }

        $uuid = $this->service->extractUuidFromQrToken($token);

        if ($uuid === null) {
            return LibraryApiResponse::error('Invalid QR token', 422);
        }

        $student = $this->service->findByUuid($uuid);

        if (! $student) {
            return LibraryApiResponse::error('Student not found', 404);
        }

        return LibraryApiResponse::success(
            (new StudentResource($student))->resolve($request),
            'Student identified from QR code',
        );
    }

    public function changes(Request $request)
    {
        $request->validate([
            'updated_since' => ['required', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.LibraryStudentService::MAX_PER_PAGE],
        ]);

        $changes = $this->service->changes($request);

        return LibraryApiResponse::paginator(
            $changes,
            StudentResource::collection($changes),
            'Student changes retrieved successfully',
        );
    }
}
