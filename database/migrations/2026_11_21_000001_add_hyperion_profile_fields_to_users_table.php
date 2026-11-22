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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('age')->default(25)->after('email');
            $table->float('weight_kg')->default(72.0)->after('age');
            $table->float('height_cm')->default(172.0)->after('weight_kg');
            $table->float('body_fat_pct')->default(18.0)->after('height_cm');
            $table->string('environment')->default('casa_sin_equipo')->after('body_fat_pct');
            $table->integer('available_time_minutes')->default(15)->after('environment');
            $table->string('injury')->default('ninguna')->after('available_time_minutes');
            $table->string('fitness_goal')->default('resistencia')->after('injury');
            $table->string('archetype')->default('titan_agil')->after('fitness_goal');
            $table->integer('current_level')->default(1)->after('archetype');
            $table->integer('current_xp')->default(0)->after('current_level');
            $table->string('rpg_title')->default('Recluta de Hierro')->after('current_xp');
            $table->boolean('interview_completed')->default(false)->after('rpg_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'age',
                'weight_kg',
                'height_cm',
                'body_fat_pct',
                'environment',
                'available_time_minutes',
                'injury',
                'fitness_goal',
                'archetype',
                'current_level',
                'current_xp',
                'rpg_title',
                'interview_completed',
            ]);
        });
    }
};
