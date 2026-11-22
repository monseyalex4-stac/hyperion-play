<?php

namespace App\Http\Controllers;

use App\Services\RoutineGeneratorService;
use App\Services\WorkoutHistoryService;
use App\Services\ProgressiveOverloadService;
use App\Services\DecisionRulesEngineService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * RoutineApiController
 * Endpoints dedicados para generación de rutinas, historial y sobrecarga progresiva.
 * HU02 (HU08), HU03 (HU09), HU04 (HU12), HU05 (HU13)
 */
class RoutineApiController extends Controller
{
    public function __construct(
        private RoutineGeneratorService    $routineGenerator,
        private WorkoutHistoryService      $historyService,
        private ProgressiveOverloadService $overloadService,
        private DecisionRulesEngineService $rulesEngine
    ) {}

    // ─────────────────────────────────────────────────────────────
    // HU02 — POST /api/routine/generate
    // Genera una rutina personalizada cruzando perfil + catálogo RAG
    // ─────────────────────────────────────────────────────────────
    public function generateRoutine(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'         => 'nullable|integer|min:1',
            'tiempo_minutos'  => 'required|integer|min:5|max:120',
            'entorno'         => 'required|string|in:casa_sin_equipo,casa_basico,gimnasio',
            'lesion'          => 'nullable|string',
            'objetivo'        => 'required|string|in:perdida_grasa,ganancia_muscular,resistencia,flexibilidad,mantenimiento',
            'edad'            => 'nullable|integer|min:10|max:100',
            'body_fat'        => 'nullable|numeric|min:1|max:70',
            'gender'          => 'nullable|string',
        ]);

        // Si viene user_id, cargar perfil del usuario
        $perfilBase = [];
        if (!empty($validated['user_id'])) {
            $user = User::find($validated['user_id']);
            if ($user) {
                $perfilBase = [
                    'edad'            => $user->age ?? $validated['edad'] ?? 30,
                    'body_fat'        => $user->body_fat_pct ?? $validated['body_fat'] ?? 20,
                    'nivel'           => $user->fitness_level ?? 'principiante',
                    'lesion'          => $user->injury_zone ?? $validated['lesion'] ?? 'ninguna',
                    'objetivo'        => $user->fitness_goal ?? $validated['objetivo'],
                    'entorno'         => $user->available_equipment ?? $validated['entorno'],
                    'tiempo_minutos'  => $user->available_time_minutes ?? $validated['tiempo_minutos'],
                    'genero'          => $user->gender ?? $validated['gender'] ?? 'no_disponible',
                ];
            }
        }

        // Mezclar con parámetros del request (request tiene prioridad)
        $perfil = array_merge($perfilBase, [
            'edad'           => $validated['edad'] ?? ($perfilBase['edad'] ?? 30),
            'body_fat'       => $validated['body_fat'] ?? ($perfilBase['body_fat'] ?? 20),
            'entorno'        => $validated['entorno'],
            'lesion'         => $validated['lesion'] ?? ($perfilBase['lesion'] ?? 'ninguna'),
            'objetivo'       => $validated['objetivo'],
            'tiempo_minutos' => $validated['tiempo_minutos'],
            'nivel'          => $perfilBase['nivel'] ?? 'principiante',
            'genero'         => $validated['gender'] ?? ($perfilBase['genero'] ?? 'no_disponible'),
        ]);

        // Evaluar reglas de seguridad
        $restricciones = $this->rulesEngine->evaluateProfile($perfil);
        $perfil['restricciones'] = $restricciones;

        // Generar rutina
        $rutina = $this->routineGenerator->generateRoutine($perfil);

        return response()->json([
            'success'      => true,
            'hu'           => 'HU02',
            'perfil_input' => [
                'edad'           => $perfil['edad'],
                'body_fat'       => $perfil['body_fat'],
                'entorno'        => $perfil['entorno'],
                'objetivo'       => $perfil['objetivo'],
                'tiempo_minutos' => $perfil['tiempo_minutos'],
                'lesion'         => $perfil['lesion'],
                'genero'         => $perfil['genero'],
            ],
            'restricciones_aplicadas' => $restricciones,
            'rutina'       => $rutina,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // HU03 — POST /api/routine/low-impact
    // Rutina de bajo impacto garantizada (100% nivel_impacto: bajo)
    // ─────────────────────────────────────────────────────────────
    public function generateLowImpactRoutine(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'        => 'nullable|integer|min:1',
            'tiempo_minutos' => 'required|integer|min:5|max:120',
            'entorno'        => 'required|string|in:casa_sin_equipo,casa_basico,gimnasio',
            'lesion'         => 'nullable|string',
            'objetivo'       => 'nullable|string',
        ]);

        $perfil = [
            'edad'                  => 50,       // forzar perfil conservador
            'body_fat'              => 26,       // activa restricción de bajo impacto
            'entorno'               => $validated['entorno'],
            'lesion'                => $validated['lesion'] ?? 'ninguna',
            'objetivo'              => $validated['objetivo'] ?? 'mantenimiento',
            'tiempo_minutos'        => $validated['tiempo_minutos'],
            'nivel'                 => 'principiante',
            'nivel_impacto_forzado' => 'bajo',   // instrucción explícita al generador
            'genero'                => 'no_disponible',
        ];

        // Las reglas de negocio aplicarán automáticamente bajo impacto
        $restricciones = $this->rulesEngine->evaluateProfile($perfil);
        $perfil['restricciones'] = $restricciones;

        $rutina = $this->routineGenerator->generateRoutine($perfil);

        // Verificar garantía: todos los ejercicios deben ser bajo impacto
        $ejercicios  = $rutina['ejercicios'] ?? [];
        $totalEj     = count($ejercicios);
        $bajosEj     = collect($ejercicios)->filter(fn($e) => ($e['nivel_impacto'] ?? '') === 'bajo')->count();
        $garantia100 = ($totalEj === 0) ? false : ($bajosEj === $totalEj);

        return response()->json([
            'success'              => true,
            'hu'                   => 'HU03',
            'garantia_bajo_impacto'=> $garantia100,
            'ejercicios_total'     => $totalEj,
            'ejercicios_bajo_impacto' => $bajosEj,
            'restricciones_aplicadas' => $restricciones,
            'rutina'               => $rutina,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // HU04 — GET /api/workout/history
    // Consulta historial de sesiones del usuario
    // ─────────────────────────────────────────────────────────────
    public function getHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|min:1',
            'date'    => 'nullable|date_format:Y-m-d',
        ]);

        $history = $this->historyService->getUserHistory(
            $validated['user_id'],
            $validated['date'] ?? null
        );

        return response()->json([
            'success' => true,
            'hu'      => 'HU04',
            'data'    => $history,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // HU04 — POST /api/workout/history
    // Guarda una sesión de entrenamiento completada
    // ─────────────────────────────────────────────────────────────
    public function saveHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'              => 'required|integer|min:1',
            'ejercicios'           => 'nullable|array',
            'duracion_minutos'     => 'nullable|integer|min:1',
            'xp_total'             => 'nullable|integer|min:0',
            'estado'               => 'nullable|string|in:completada,incompleta,cancelada',
            'fatiga_reportada'     => 'nullable|boolean',
            'dificultad'           => 'nullable|string|in:muy_facil,adecuada,muy_pesada',
            'sobrecarga_progresiva'=> 'nullable|boolean',
            'porcentaje_sobrecarga'=> 'nullable|numeric',
            'disclaimer_mostrado'  => 'nullable|boolean',
        ]);

        $session = $this->historyService->saveSession($validated['user_id'], [
            'exercises'              => $validated['ejercicios'] ?? [],
            'duracion_minutos'       => $validated['duracion_minutos'] ?? 0,
            'xp_total'               => $validated['xp_total'] ?? 0,
            'estado'                 => $validated['estado'] ?? 'completada',
            'fatiga_reportada'       => $validated['fatiga_reportada'] ?? false,
            'dificultad'             => $validated['dificultad'] ?? 'adecuada',
            'sobrecarga_progresiva'  => $validated['sobrecarga_progresiva'] ?? false,
            'porcentaje_sobrecarga'  => $validated['porcentaje_sobrecarga'] ?? 0.0,
            'disclaimer_mostrado'    => $validated['disclaimer_mostrado'] ?? false,
            'explicabilidad'         => [],
        ]);

        return response()->json([
            'success'    => true,
            'hu'         => 'HU04',
            'mensaje'    => 'Sesión de entrenamiento guardada correctamente.',
            'sesion_id'  => $session->id,
            'created_at' => $session->created_at?->format('Y-m-d H:i:s'),
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────
    // HU05 — GET /api/routine/progressive-overload
    // Calcula la sobrecarga progresiva basada en historial reciente
    // ─────────────────────────────────────────────────────────────
    public function getProgressiveOverload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'      => 'required|integer|min:1',
            'nivel_impacto'=> 'nullable|string|in:bajo,medio,alto',
            'objetivo'     => 'nullable|string',
        ]);

        $perfil = [
            'nivel_impacto' => $validated['nivel_impacto'] ?? 'medio',
            'objetivo'      => $validated['objetivo'] ?? 'mantenimiento',
        ];

        $resultado = $this->overloadService->calculateOverload($validated['user_id'], $perfil);

        return response()->json([
            'success'  => true,
            'hu'       => 'HU05',
            'user_id'  => $validated['user_id'],
            'resultado'=> $resultado,
        ]);
    }
}
