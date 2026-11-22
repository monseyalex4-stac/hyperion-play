<?php

namespace App\Services;

use App\Models\WorkoutSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * WorkoutHistoryService — HU04 (HU12)
 * Persiste y consulta el historial de sesiones de entrenamiento por usuario.
 */
class WorkoutHistoryService
{
    /**
     * Guarda una sesión de entrenamiento completada.
     *
     * @param int   $userId      ID del usuario
     * @param array $sessionData Datos de la sesión (exercises, duration, status, etc.)
     * @return WorkoutSession
     */
    public function saveSession(int $userId, array $sessionData): WorkoutSession
    {
        return WorkoutSession::create([
            'user_id'                       => $userId,
            'exercises_data'                => $sessionData['exercises'] ?? [],
            'total_duration_minutes'        => $sessionData['duracion_minutos'] ?? 0,
            'total_xp_earned'               => $sessionData['xp_total'] ?? 0,
            'status'                        => $sessionData['estado'] ?? 'completada',
            'fatigue_reported'              => $sessionData['fatiga_reportada'] ?? false,
            'difficulty_reported'           => $sessionData['dificultad'] ?? 'adecuada',
            'progressive_overload_applied'  => $sessionData['sobrecarga_progresiva'] ?? false,
            'overload_percentage'           => $sessionData['porcentaje_sobrecarga'] ?? 0.0,
            'medical_disclaimer_displayed'  => $sessionData['disclaimer_mostrado'] ?? false,
            'explainability_data'           => $sessionData['explicabilidad'] ?? [],
            'completed_at'                  => now(),
        ]);
    }

    /**
     * Recupera el historial de sesiones de un usuario.
     *
     * @param int         $userId ID del usuario
     * @param string|null $date   Fecha opcional (YYYY-MM-DD) para filtrar
     * @return array
     */
    public function getUserHistory(int $userId, ?string $date = null): array
    {
        $query = WorkoutSession::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        $sessions = $query->get();

        if ($sessions->isEmpty()) {
            return [
                'user_id'   => $userId,
                'total'     => 0,
                'sesiones'  => [],
                'mensaje'   => 'No se encontraron sesiones de entrenamiento' . ($date ? " para la fecha $date" : ''),
            ];
        }

        $formatted = $sessions->map(function (WorkoutSession $session) {
            return [
                'id'                       => $session->id,
                'fecha'                    => $session->created_at?->format('Y-m-d'),
                'completada_a'             => $session->completed_at?->format('Y-m-d H:i:s'),
                'duracion_minutos'         => $session->total_duration_minutes,
                'xp_ganado'                => $session->total_xp_earned,
                'estado'                   => $session->status,
                'dificultad'               => $session->difficulty_reported,
                'fatiga_reportada'         => $session->fatigue_reported,
                'sobrecarga_progresiva'    => $session->progressive_overload_applied,
                'porcentaje_sobrecarga'    => $session->overload_percentage,
                'ejercicios'               => $session->exercises_data ?? [],
                'disclaimer_mostrado'      => $session->medical_disclaimer_displayed,
            ];
        })->toArray();

        return [
            'user_id'  => $userId,
            'total'    => count($formatted),
            'sesiones' => $formatted,
        ];
    }

    /**
     * Obtiene la última sesión completada de un usuario.
     *
     * @param int $userId
     * @return WorkoutSession|null
     */
    public function getLastCompletedSession(int $userId): ?WorkoutSession
    {
        return WorkoutSession::where('user_id', $userId)
            ->where('status', 'completada')
            ->latest('completed_at')
            ->first();
    }

    /**
     * Obtiene las últimas N sesiones completadas.
     *
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getLastNSessions(int $userId, int $limit = 3): Collection
    {
        return WorkoutSession::where('user_id', $userId)
            ->where('status', 'completada')
            ->latest('completed_at')
            ->limit($limit)
            ->get();
    }
}
