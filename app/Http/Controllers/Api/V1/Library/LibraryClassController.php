<?php

namespace App\Http\Controllers\Api\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Library\ClassResource;
use App\Services\Api\Library\LibraryClassService;
use App\Support\Api\LibraryApiResponse;

class LibraryClassController extends Controller
{
    public function __construct(
        private readonly LibraryClassService $service,
    ) {
    }

    public function index()
    {
        $classes = $this->service->index();

        return LibraryApiResponse::success(
            ClassResource::collection($classes)->resolve(),
            'Class data retrieved successfully',
        );
    }
}
