<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('murid_id')->constrained('murid')->cascadeOnUpdate();
            $table->string('status', 20)->default('pending')->index();
            $table->string('source_image_path')->nullable();
            $table->string('source_image_hash', 64)->nullable();
            $table->unsignedTinyInteger('face_count')->default(0);
            $table->decimal('quality_score', 5, 2)->nullable();
            $table->json('reasons')->nullable();
            $table->json('metrics')->nullable();
            $table->json('faces')->nullable();
            $table->string('processor', 100)->nullable();
            $table->string('processor_version', 100)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();

            $table->index(['murid_id', 'created_at']);
            $table->index(['status', 'evaluated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_evaluations');
    }
};
