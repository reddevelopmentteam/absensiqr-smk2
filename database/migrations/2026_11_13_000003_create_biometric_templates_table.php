<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('murid_id')->unique()->constrained('murid')->cascadeOnUpdate();
            $table->foreignId('evaluation_id')->nullable()->constrained('biometric_evaluations')->nullOnDelete();
            $table->string('status', 20)->default('suspended')->index();
            $table->string('algorithm', 100);
            $table->string('model_version', 100);
            $table->longText('template_payload');
            $table->string('source_image_path')->nullable();
            $table->string('source_image_hash', 64)->nullable();
            $table->decimal('quality_score', 5, 2)->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason', 100)->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'expires_at']);
            $table->index(['murid_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_templates');
    }
};
