<?php

namespace App\Services;

/**
 * ProgressiveOverloadService — HU05 (HU13)
 * Calcula la sobrecarga progresiva basada en el historial reciente del usuario.
 * Aplica un máximo de 10% de incremento y bloquea si hay fatiga/sesión incompleta.
 */
class ProgressiveOverloadService
{
    public function __construct(
        private WorkoutHistoryService $historyService
    ) {}

    /**
     * Calcula si se debe aplicar sobrecarga progresiva y en qué medida.
     *
     * @param int   $userId
     * @param array $perfilUsuario  Incluye nivel_impacto, objetivo, etc.
     * @return array
     */
    public function calculateOverload(int $userId, array $perfilUsuario = []): array
    {
        $lastSession = $this->historyService->getLastCompletedSession($userId);

        // Sin historial → empezar sin sobrecarga
        if (!$lastSession) {
            return [
                'aplicar_sobrecarga'  => false,
                'factor'              => 1.0,
                'porcentaje_cambio'   => 0,
                'mensaje'             => 'Sin historial previo. Se recomienda comenzar con una rutina estándar para establecer tu línea base.',
                'series_base'         => 3,
                'reps_base'           => 12,
                'series_ajustadas'    => 3,
                'reps_ajustadas'      => 12,
            ];
        }

        $dificultad     = $lastSession->difficulty_reported ?? 'adecuada';
        $fatiga         = $lastSession->fatigue_reported ?? false;
        $estadoSesion   = $lastSession->status ?? 'completada';

        // Bloquear sobrecarga si hubo fatiga o sesión incompleta/muy pesada
        if ($fatiga || $dificultad === 'muy_pesada' || $estadoSesion !== 'completada') {
            return [
                'aplicar_sobrecarga'  => false,
                'factor'              => 0.9,
                'porcentaje_cambio'   => -10,
                'mensaje'             => 'Se detectó fatiga o dificultad elevada en la sesión anterior. Se recomienda reducir la carga un 10% para esta sesión.',
                'series_base'         => 3,
                'reps_base'           => 12,
                'series_ajustadas'    => 3,
                'reps_ajustadas'      => 11,
                'ultima_sesion'       => [
                    'fecha'      => $lastSession->completed_at?->format('Y-m-d'),
                    'dificultad' => $dificultad,
                    'fatiga'     => $fatiga,
                    'estado'     => $estadoSesion,
                ],
            ];
        }

        // Verificar últimas 3 sesiones para progresión acumulada
        $ultimasSesiones  = $this->historyService->getLastNSessions($userId, 3);
        $todasMuyFaciles  = $ultimasSesiones->count() >= 2
            && $ultimasSesiones->every(fn($s) => $s->difficulty_reported === 'muy_facil');

        // Sesión adecuada → mantener carga
        if ($dificultad === 'adecuada') {
            return [
                'aplicar_sobrecarga'  => false,
                'factor'              => 1.0,
                'porcentaje_cambio'   => 0,
                'mensaje'             => 'Nivel adecuado en la sesión anterior. Se mantiene la carga actual para consolidar el progreso.',
                'series_base'         => 3,
                'reps_base'           => 12,
                'series_ajustadas'    => 3,
                'reps_ajustadas'      => 12,
            ];
        }

        // Sesión muy fácil (1 sesión) → subir 5%
        if ($dificultad === 'muy_facil' && !$todasMuyFaciles) {
            $factor = 1.05;
            return $this->buildOverloadResponse($factor, $userId, $lastSession, $perfilUsuario,
                'La sesión anterior fue muy fácil. Se incrementa la carga un 5%.');
        }

        // Varias sesiones muy fáciles → sobrecarga máxima del 10%
        if ($todasMuyFaciles) {
            $factor = 1.10;
            return $this->buildOverloadResponse($factor, $userId, $lastSession, $perfilUsuario,
                'Se detectaron múltiples sesiones de baja dificultad. Se aplica sobrecarga progresiva máxima del 10%.');
        }

        // Fallback: mantener
        return [
            'aplicar_sobrecarga'  => false,
            'factor'              => 1.0,
            'porcentaje_cambio'   => 0,
            'mensaje'             => 'Carga actual adecuada al progreso del usuario.',
            'series_base'         => 3,
            'reps_base'           => 12,
            'series_ajustadas'    => 3,
            'reps_ajustadas'      => 12,
        ];
    }

    /**
     * Construye la respuesta de sobrecarga aplicada.
     */
    private function buildOverloadResponse(
        float           $factor,
        int             $userId,
        $lastSession,
        array           $perfil,
        string          $mensaje
    ): array {
        $seriesBase = 3;
        $repsBase   = 12;

        // Respetar restricciones de bajo impacto: no exceder series/reps seguras
        $nivelImpacto = $perfil['nivel_impacto'] ?? 'medio';
        if ($nivelImpacto === 'bajo') {
            $seriesBase = 2;
            $repsBase   = 10;
        }

        $seriesAjustadas = (int) round($seriesBase * $factor);
        $repsAjustadas   = (int) round($repsBase * $factor);

        // Caps de seguridad absolutos
        $seriesAjustadas = min($seriesAjustadas, 5);
        $repsAjustadas   = min($repsAjustadas, 20);

        $porcentajeCambio = (int) round(($factor - 1.0) * 100);

        return [
            'aplicar_sobrecarga'  => true,
            'factor'              => $factor,
            'porcentaje_cambio'   => $porcentajeCambio,
            'mensaje'             => $mensaje,
            'series_base'         => $seriesBase,
            'reps_base'           => $repsBase,
            'series_ajustadas'    => $seriesAjustadas,
            'reps_ajustadas'      => $repsAjustadas,
            'ultima_sesion'       => [
                'fecha'      => $lastSession->completed_at?->format('Y-m-d'),
                'dificultad' => $lastSession->difficulty_reported,
                'fatiga'     => $lastSession->fatigue_reported,
                'estado'     => $lastSession->status,
            ],
        ];
    }
}
