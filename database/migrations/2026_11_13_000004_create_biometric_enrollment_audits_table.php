<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_enrollment_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('murid_id')->nullable()->constrained('murid')->nullOnDelete();
            $table->foreignId('biometric_template_id')->nullable()->constrained('biometric_templates')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50)->index();
            $table->string('status_before', 20)->nullable();
            $table->string('status_after', 20)->nullable();
            $table->json('reasons')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['murid_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_enrollment_audits');
    }
};
