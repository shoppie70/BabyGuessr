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
        Schema::create('baby_profiles', function (Blueprint $table) {
            $table->id();
            $table->text('family_name_encrypted');
            $table->text('given_name_encrypted');
            $table->text('family_name_kana_encrypted');
            $table->text('given_name_kana_encrypted');
            $table->string('given_name_hmac', 64)->index();
            $table->string('given_name_kana_hmac', 64)->index();
            $table->date('birth_date');
            $table->time('birth_time')->nullable();
            $table->string('sex', 10);
            $table->text('birth_place_encrypted')->nullable();
            $table->unsignedInteger('birth_weight')->nullable();
            $table->string('status', 20)->default('open');
            $table->string('game_token', 64)->unique();
            $table->string('manage_token', 64)->unique();
            $table->string('diagnostics_token', 64)->unique();
            $table->timestamps();
        });

        Schema::create('guesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('baby_profile_id')->constrained('baby_profiles')->cascadeOnDelete();
            $table->text('challenger_name_encrypted')->nullable();
            $table->text('guess_encrypted');
            $table->string('guess_hmac', 64)->nullable()->index();
            $table->string('result', 20); // 'wrong', 'reading_match', 'correct'
            $table->string('session_identifier', 64)->index();
            $table->unsignedInteger('attempt_no')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guesses');
        Schema::dropIfExists('baby_profiles');
    }
};
