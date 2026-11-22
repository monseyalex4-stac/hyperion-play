<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserQuest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'aventurero@hyperionplay.com'],
            [
                'name' => 'Santiago (Aventurero)',
                'password' => Hash::make('password123'),
                'age' => 24,
                'weight_kg' => 74.0,
                'height_cm' => 175.0,
                'body_fat_pct' => 16.5,
                'environment' => 'casa_sin_equipo',
                'available_time_minutes' => 15,
                'injury' => 'ninguna',
                'fitness_goal' => 'resistencia',
                'archetype' => 'titan_agil',
                'current_level' => 1,
                'current_xp' => 45,
                'rpg_title' => 'Recluta de Hierro',
                'interview_completed' => false,
            ]
        );

        // Misiones iniciales
        UserQuest::firstOrCreate(
            ['user_id' => $user->id, 'quest_key' => 'mision_calibracion'],
            [
                'title' => 'El Despertar del Guerrero',
                'description' => 'Completa tu rutina de calibración inicial guiada por Hyperion',
                'target_count' => 1,
                'current_count' => 0,
                'reward_xp' => 30,
                'completed' => false,
            ]
        );

        UserQuest::firstOrCreate(
            ['user_id' => $user->id, 'quest_key' => 'mision_3_rutinas'],
            [
                'title' => 'Constancia de Titán',
                'description' => 'Completa 3 rutinas en la semana',
                'target_count' => 3,
                'current_count' => 0,
                'reward_xp' => 50,
                'completed' => false,
            ]
        );

        UserQuest::firstOrCreate(
            ['user_id' => $user->id, 'quest_key' => 'mision_progresion'],
            [
                'title' => 'Superando los Límites',
                'description' => 'Acepta y completa un reto de sobrecarga progresiva (+10%)',
                'target_count' => 1,
                'current_count' => 0,
                'reward_xp' => 40,
                'completed' => false,
            ]
        );
    }
}
