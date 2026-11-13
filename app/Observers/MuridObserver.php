<?php

namespace App\Observers;

use App\Models\Murid\Murid;
use App\Services\Biometric\FaceEnrollmentService;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MuridObserver
{
    public function updated(Murid $murid): void
    {
        if (! Schema::hasTable('biometric_templates')) {
            return;
        }

        try {
            if ($murid->wasChanged('image_path')) {
                app(FaceEnrollmentService::class)->handleReferencePhotoChanged($murid);
            }

            if ($murid->wasChanged('status') && $murid->status !== 'aktif') {
                app(FaceEnrollmentService::class)->handleStudentUnavailable($murid, 'student_inactive');
            }
        } catch (Throwable $error) {
            report($error);
        }
    }

    public function deleted(Murid $murid): void
    {
        if (! Schema::hasTable('biometric_templates')) {
            return;
        }

        try {
            app(FaceEnrollmentService::class)->handleStudentUnavailable($murid, 'student_deleted');
        } catch (Throwable $error) {
            report($error);
        }
    }
}
