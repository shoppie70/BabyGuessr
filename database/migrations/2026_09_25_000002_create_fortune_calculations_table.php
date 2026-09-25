<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fortune_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('baby_profile_id')->constrained('baby_profiles')->cascadeOnDelete();
            $table->string('calculator_key', 50);
            $table->string('calculator_version', 20);
            $table->string('input_hash', 64)->index();
            $table->string('status', 20)->default('completed'); // 'completed', 'partial', 'unavailable', 'failed'
            $table->longText('result_ciphertext');
            $table->string('error_code', 50)->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['baby_profile_id', 'calculator_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_calculations');
    }
};
