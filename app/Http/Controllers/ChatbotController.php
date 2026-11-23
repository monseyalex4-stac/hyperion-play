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
            'user_id' => 'nullable|integer',
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
     * Crear un nuevo usuario/aventurero para iniciar una sesión limpia.
     */
    public function createUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:100',
            'age'                    => 'nullable|integer|min:12|max:100',
            'gender'                 => 'nullable|string',
            'weight_kg'              => 'nullable|numeric|min:30|max:300',
            'height_cm'              => 'nullable|numeric|min:100|max:250',
            'body_fat_pct'           => 'nullable|numeric|min:3|max:60',
            'environment'            => 'nullable|string|in:casa_sin_equipo,casa_basico,gimnasio',
            'available_time_minutes' => 'nullable|integer|min:5|max:120',
            'injury'                 => 'nullable|string',
            'fitness_goal'           => 'nullable|string',
        ]);

        $email = 'aventurero_' . time() . '_' . rand(100, 999) . '@hyperionplay.com';

        $weight = isset($validated['weight_kg']) ? (float)$validated['weight_kg'] : 70.0;
        $height = isset($validated['height_cm']) ? (float)$validated['height_cm'] : 170.0;
        $age = isset($validated['age']) ? (int)$validated['age'] : 25;
        $gender = $validated['gender'] ?? 'no_disponible';

        $bodyFat = isset($validated['body_fat_pct']) ? (float)$validated['body_fat_pct'] : null;
        if ($bodyFat === null && $height > 0) {
            $heightM = $height / 100.0;
            $bmi = $weight / ($heightM * $heightM);
            $sexFactor = ($gender === 'masculino') ? 1.0 : (($gender === 'femenino') ? 0.0 : 0.5);
            $bodyFat = round(max(5.0, min(50.0, (1.20 * $bmi) + (0.23 * $age) - (10.8 * $sexFactor) - 5.4)), 1);
        }

        $newUser = User::create([
            'name'                   => $validated['name'],
            'email'                  => $email,
            'password'               => bcrypt('secret123'),
            'age'                    => $age,
            'gender'                 => $gender,
            'weight_kg'              => $weight,
            'height_cm'              => $height,
            'body_fat_pct'           => $bodyFat ?? 18.0,
            'environment'            => $validated['environment'] ?? 'casa_sin_equipo',
            'available_time_minutes' => $validated['available_time_minutes'] ?? 15,
            'injury'                 => $validated['injury'] ?? 'ninguna',
            'fitness_goal'           => $validated['fitness_goal'] ?? 'resistencia',
            'archetype'              => 'titan_agil',
            'current_level'          => 1,
            'current_xp'             => 0,
            'rpg_title'              => 'Aspirante',
            'interview_completed'    => false,
        ]);

        // Misión inicial
        \App\Models\UserQuest::create([
            'user_id'       => $newUser->id,
            'quest_key'     => 'mision_calibracion_' . $newUser->id,
            'title'         => 'El Despertar del Guerrero',
            'description'   => 'Completa tu primera sesión guiada por Hyperion',
            'target_count'  => 1,
            'current_count' => 0,
            'reward_xp'     => 30,
            'completed'     => false,
        ]);

        return response()->json([
            'success'  => true,
            'user'     => $newUser,
            'redirect' => route('hyperion.index', ['user_id' => $newUser->id]),
        ]);
    }

    /**
     * Actualiza los datos del usuario activo (ej: cambiar nombre).
     */
    public function updateUser(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);

        $validated = $request->validate([
            'name'        => 'nullable|string|max:100',
            'age'         => 'nullable|integer|min:12|max:100',
            'environment' => 'nullable|string|in:casa_sin_equipo,casa_basico,gimnasio',
            'injury'      => 'nullable|string',
        ]);

        if (!empty($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (!empty($validated['age'])) {
            $user->age = $validated['age'];
        }
        if (!empty($validated['environment'])) {
            $user->environment = $validated['environment'];
        }
        if (isset($validated['injury'])) {
            $user->injury = $validated['injury'];
        }

        $user->save();

        session(['active_user_id' => $user->id]);
        cookie()->queue('hyperion_user_id', (string) $user->id, 60 * 24 * 30);

        return response()->json([
            'status'  => 'success',
            'mensaje' => 'Perfil actualizado correctamente.',
            'user'    => $user,
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
        $sessionId = $request->input('session_id');

        // 1. Buscar primero por ID de sesión específica si se proveyó
        $sesionPendiente = null;
        if ($sessionId) {
            $sesionPendiente = WorkoutSession::where('user_id', $user->id)
                ->where('id', $sessionId)
                ->first();
        }

        // 2. Si no, buscar la sesión en progreso más reciente
        if (!$sesionPendiente) {
            $sesionPendiente = $user->workoutSessions()
                ->where('status', 'en_progreso')
                ->latest()
                ->first();
        }

        // 3. Si no hay en progreso, verificar si la última ya fue completada
        if (!$sesionPendiente) {
            $ultima = $user->workoutSessions()->latest()->first();
            if ($ultima && $ultima->status === 'completada') {
                return response()->json([
                    'status' => 'info',
                    'mensaje' => 'Esta rutina ya fue completada anteriormente y su XP ya fue otorgada.',
                    'user' => $user,
                ]);
            }

            return response()->json([
                'status' => 'error',
                'mensaje' => 'No hay ninguna rutina activa para completar. Pídele primero una rutina a Hyperion en el chat.',
            ], 404);
        }

        // Si ya estaba completada
        if ($sesionPendiente->status === 'completada') {
            return response()->json([
                'status' => 'info',
                'mensaje' => 'Esta rutina ya fue completada anteriormente y su XP ya fue otorgada.',
                'user' => $user,
            ]);
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
