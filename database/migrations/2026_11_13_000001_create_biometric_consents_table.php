<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('murid_id')->unique()->constrained('murid')->cascadeOnUpdate();
            $table->string('status', 20)->default('pending')->index();
            $table->string('consent_version', 50);
            $table->string('source', 100)->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_consents');
    }
};
