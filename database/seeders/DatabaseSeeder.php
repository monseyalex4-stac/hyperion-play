<?php

namespace Database\Seeders;

use App\Models\ChatbotTrainingEntry;
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

        // Ejemplos iniciales de entrenamiento manual para el Laboratorio de Hyperion
        ChatbotTrainingEntry::firstOrCreate(
            ['type' => 'qa', 'trigger_text' => 'que comer antes de entrenar, pre entreno, comida antes de hacer ejercicio'],
            [
                'response_text' => "🍎 ENERGÍA PARA EL GUERRERO (PRE-ENTRENO)\n\nLo ideal 45 a 90 minutos antes de entrenar es combinar un carbohidrato de absorción media (plátano, avena o pan integral) con algo de proteína ligera (yogur griego o huevo) y buena hidratación (400-500 ml de agua). Evita comidas muy grasosas justo antes de la sesión.",
                'metadata' => ['match_mode' => 'contains'],
                'is_active' => true,
                'created_by' => $user->id,
            ]
        );

        ChatbotTrainingEntry::firstOrCreate(
            ['type' => 'synonym', 'trigger_text' => 'conejo, conejos'],
            [
                'response_text' => 'Modismo popular para bíceps',
                'metadata' => [
                    'category' => 'musculo',
                    'target' => 'biceps',
                    'label' => 'Bíceps',
                    'db_targets' => ['biceps'],
                ],
                'is_active' => true,
                'created_by' => $user->id,
            ]
        );

        ChatbotTrainingEntry::firstOrCreate(
            ['type' => 'synonym', 'trigger_text' => 'menisco, ligamento cruzado'],
            [
                'response_text' => 'Términos clínicos relacionados con lesión de rodilla',
                'metadata' => [
                    'category' => 'lesion',
                    'target' => 'rodilla',
                    'label' => 'Rodilla',
                ],
                'is_active' => true,
                'created_by' => $user->id,
            ]
        );

        ChatbotTrainingEntry::firstOrCreate(
            ['type' => 'exercise', 'trigger_text' => 'Press Militar con Mancuernas'],
            [
                'response_text' => 'Espalda recta y abdomen firme; empuja las mancuernas hacia arriba sin arquear la zona lumbar y controla el descenso.',
                'metadata' => [
                    'musculo_principal' => 'delts',
                    'equipo' => 'dumbbell',
                    'entorno' => 'casa_basico',
                    'nivel_impacto' => 'bajo',
                    'nivel_experiencia' => 'intermedio',
                    'zonas_riesgo_evitar' => ['hombro'],
                    'tipo_objetivo' => 'fuerza',
                    'duracion_estimada_seg' => 60,
                    'xp_base' => 20,
                ],
                'is_active' => true,
                'created_by' => $user->id,
            ]
        );
    }
}
