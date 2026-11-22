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
        Schema::create('workout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('archetype')->default('titan_agil');
            $table->integer('total_duration_minutes')->default(15);
            $table->string('target_environment')->default('casa_sin_equipo');
            $table->string('target_injury')->default('ninguna');
            $table->enum('status', ['en_progreso', 'completada', 'abandonada'])->default('en_progreso');
            $table->enum('difficulty_rating', ['muy_facil', 'adecuada', 'muy_pesada'])->nullable();
            $table->boolean('fatigue_reported')->default(false);
            $table->boolean('progressive_overload_applied')->default(false);
            $table->float('overload_percentage')->default(0.0);
            $table->integer('xp_earned')->default(0);
            $table->json('exercises_data');
            $table->json('explainability_data')->nullable();
            $table->boolean('medical_disclaimer_displayed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_quests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('quest_key');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('target_count')->default(1);
            $table->integer('current_count')->default(0);
            $table->integer('reward_xp')->default(30);
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_quests');
        Schema::dropIfExists('workout_sessions');
    }
};
