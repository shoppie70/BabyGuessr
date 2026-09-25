<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fortune_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('baby_profile_id')->constrained('baby_profiles')->cascadeOnDelete();
            $table->string('status', 20)->default('not_generated');
            $table->string('model', 80)->nullable();
            $table->longText('report_ciphertext')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique('baby_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_reports');
    }
};
