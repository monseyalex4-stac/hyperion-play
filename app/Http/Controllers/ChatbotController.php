<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkoutSession;
use App\Services\ConversationalChatbotService;
use App\Services\DecisionRulesEngineService;
use App\Services\ExerciseDbService;
use App\Services\GamificationRpgService;
use App\Services\RoutineGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    protected ConversationalChatbotService $chatbot;
    protected RoutineGeneratorService $routineGenerator;
    protected GamificationRpgService $rpgService;
    protected DecisionRulesEngineService $rulesEngine;
    protected ExerciseDbService $exerciseDb;

    public function __construct(
        ConversationalChatbotService $chatbot,
        RoutineGeneratorService $routineGenerator,
        GamificationRpgService $rpgService,
        DecisionRulesEngineService $rulesEngine,
        ExerciseDbService $exerciseDb
    ) {
        $this->chatbot = $chatbot;
        $this->routineGenerator = $routineGenerator;
        $this->rpgService = $rpgService;
        $this->rulesEngine = $rulesEngine;
        $this->exerciseDb = $exerciseDb;
    }

    /**
     * Obtiene el usuario activo o el usuario por defecto para la sesión interactiva.
     */
    protected function resolveUser(Request $request): User
    {
        $userId = $request->input('user_id');
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                return $user;
            }
        }

        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Santiago (Aventurero)',
                'email' => 'aventurero@hyperionplay.com',
                'password' => bcrypt('password123'),
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
            ]);
        }

        return $user;
    }

    /**
     * Enviar mensaje en el chat conversacional con Hyperion (HU #7 a #15).
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $user = $this->resolveUser($request);
        $respuesta = $this->chatbot->procesarMensaje($user, $request->input('message'));

        $user->refresh();
        $user->load('quests');

        return response()->json([
            'status' => 'success',
            'respuesta' => $respuesta,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'age' => $user->age,
                'body_fat_pct' => $user->body_fat_pct,
                'environment' => $user->environment,
                'available_time_minutes' => $user->available_time_minutes,
                'injury' => $user->injury,
                'fitness_goal' => $user->fitness_goal,
                'archetype' => $user->archetype,
                'current_level' => $user->current_level,
                'current_xp' => $user->current_xp,
                'rpg_title' => $user->rpg_title,
                'quests' => $user->quests,
            ],
        ]);
    }

    /**
     * Obtiene el perfil actual del usuario y su estado RPG.
     */
    public function getProfile(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        $user->load('quests');

        $nivelInfo = $this->rpgService->obtenerInfoNivelPorXp($user->current_xp);
        $ultimaSesion = $user->workoutSessions()->latest()->first();

        return response()->json([
            'status' => 'success',
            'user' => $user,
            'nivel_info' => $nivelInfo,
            'ultima_sesion' => $ultimaSesion,
        ]);
    }

    /**
     * Finaliza la rutina activa y registra esfuerzo percibido (HU #12, #13, #14).
     */
    public function completeWorkout(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        $dificultad = $request->input('difficulty_rating', 'adecuada'); // muy_facil, adecuada, muy_pesada
        $fatiga = (bool) $request->input('fatigue_reported', false);
        $sobrecargaAceptada = (bool) $request->input('progressive_overload_accepted', false);

        $sesionPendiente = $user->workoutSessions()
            ->where('status', 'en_progreso')
            ->latest()
            ->first();

        if (!$sesionPendiente) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'No hay ninguna rutina activa para completar.',
            ], 404);
        }

        $xpGanada = $this->rpgService->calcularXpRutina([
            'duracion_minutos' => $sesionPendiente->total_duration_minutes,
            'ejercicios' => $sesionPendiente->exercises_data ?? [],
        ], $sobrecargaAceptada);

        $resultadoRpg = $this->rpgService->procesarFinRutina(
            $user,
            $sesionPendiente,
            $xpGanada,
            $dificultad,
            $fatiga
        );

        $user->refresh();

        return response()->json([
            'status' => 'success',
            'mensaje' => '¡Rutina completada y registrada en el historial!',
            'rpg' => $resultadoRpg,
            'user' => $user,
        ]);
    }

    /**
     * Consulta el historial cronológico de entrenamientos (HU #12).
     */
    public function getHistory(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        $historial = $user->workoutSessions()
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'historial' => $historial,
        ]);
    }

    /**
     * Ejecuta y consulta la validación automatizada de 20 perfiles (HU #5).
     */
    public function getMetricsReport(): JsonResponse
    {
        $resultado = $this->rulesEngine->ejecutarValidacionMetricas();

        return response()->json($resultado);
    }

    /**
     * Prueba técnica de la integración de ExerciseDB con fallback (HU #6).
     */
    public function testExerciseDb(Request $request): JsonResponse
    {
        $bodyPart = $request->input('body_part');
        $equipment = $request->input('equipment');

        $ejercicios = $this->exerciseDb->getExercises($bodyPart, $equipment);

        return response()->json([
            'status' => 'success',
            'total' => count($ejercicios),
            'origen' => !empty(env('EXERCISE_DB_KEY')) ? 'API_ExerciseDB' : 'Fallback_RAG_Local',
            'muestra' => array_slice($ejercicios, 0, 5),
        ]);
    }
}
