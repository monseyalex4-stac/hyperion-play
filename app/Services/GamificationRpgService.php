<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserQuest;
use App\Models\WorkoutSession;
use Illuminate\Support\Facades\File;

class GamificationRpgService
{
    protected array $gameMechanics;
    protected string $mechanicsPath;

    public function __construct()
    {
        $this->mechanicsPath = storage_path('app/DATASET/open_game_mechanics.json');
        if (File::exists($this->mechanicsPath)) {
            $this->gameMechanics = json_decode(File::get($this->mechanicsPath), true) ?: [];
        } else {
            $this->gameMechanics = [
                'sistema_niveles' => [
                    ['nivel' => 1, 'titulo' => 'Recluta de Hierro', 'xp_min' => 0, 'xp_max' => 99],
                    ['nivel' => 2, 'titulo' => 'Guerrero de Cobre', 'xp_min' => 100, 'xp_max' => 249],
                    ['nivel' => 3, 'titulo' => 'Caballero de Bronce', 'xp_min' => 250, 'xp_max' => 499],
                    ['nivel' => 4, 'titulo' => 'Campeón de Plata', 'xp_min' => 500, 'xp_max' => 999],
                    ['nivel' => 5, 'titulo' => 'Titán de Hyperion', 'xp_min' => 1000, 'xp_max' => 999999],
                ],
                'recompensas' => [
                    'xp_base_rutina' => 50,
                    'xp_por_serie' => 5,
                    'xp_ejercicio_principiante' => 10,
                    'xp_ejercicio_intermedio' => 15,
                    'xp_ejercicio_avanzado' => 20,
                    'xp_mision_cumplida' => 30,
                ],
            ];
        }
    }

    /**
     * Calcula la experiencia ganada para una rutina completada.
     */
    public function calcularXpRutina(array $rutinaData, bool $sobrecargaAceptada = false): int
    {
        $recompensas = $this->gameMechanics['recompensas'] ?? [];
        $xpBase = (int) ($recompensas['xp_base_rutina'] ?? 50);
        $xpPorSerie = (int) ($recompensas['xp_por_serie'] ?? 5);

        $duracionMinutos = (int) ($rutinaData['duracion_minutos'] ?? 15);
        $ejercicios = $rutinaData['ejercicios'] ?? [];

        // Si es micro-rutina por restricciones (HU #10), otorgar XP proporcional al tiempo
        if ($duracionMinutos < 15) {
            $xpBase = (int) max(25, round(($xpBase * $duracionMinutos) / 15));
        }

        $totalSeries = 0;
        $bonusDificultad = 0;

        foreach ($ejercicios as $ej) {
            $series = (int) ($ej['series'] ?? 3);
            $totalSeries += $series;

            $nivel = strtolower($ej['nivel'] ?? $ej['nivel_experiencia'] ?? 'principiante');
            if ($nivel === 'intermedio') {
                $bonusDificultad += 5;
            } elseif ($nivel === 'avanzado') {
                $bonusDificultad += 10;
            }
        }

        $xpTotal = $xpBase + ($totalSeries * $xpPorSerie) + $bonusDificultad;

        // Bonus por aceptar reto de sobrecarga progresiva (HU #13)
        if ($sobrecargaAceptada) {
            $xpTotal += 15;
        }

        return $xpTotal;
    }

    /**
     * Aplica la XP ganada al usuario, actualiza su nivel, títulos y misiones, y retorna el resumen RPG.
     */
    public function procesarFinRutina(User $user, WorkoutSession $session, int $xpGanada, ?string $dificultad = null, bool $fatiga = false): array
    {
        $nivelAnterior = $user->current_level;
        $tituloAnterior = $user->rpg_title;
        $xpAnterior = $user->current_xp;

        $nuevaXp = $xpAnterior + $xpGanada;

        // Determinar nuevo nivel y título
        $nivelInfo = $this->obtenerInfoNivelPorXp($nuevaXp);
        $nuevoNivel = $nivelInfo['nivel'];
        $nuevoTitulo = $nivelInfo['titulo'];

        $subioNivel = $nuevoNivel > $nivelAnterior;

        // Actualizar datos del usuario
        $user->update([
            'current_xp' => $nuevaXp,
            'current_level' => $nuevoNivel,
            'rpg_title' => $nuevoTitulo,
        ]);

        // Actualizar sesión
        $session->update([
            'status' => 'completada',
            'xp_earned' => $xpGanada,
            'difficulty_rating' => $dificultad,
            'fatigue_reported' => $fatiga,
            'completed_at' => now(),
        ]);

        // Procesar misiones activas (HU #14)
        $misionesCompletadas = $this->actualizarMisionesUsuario($user, $session);

        // Calcular progreso hacia el siguiente nivel
        $xpFaltante = max(0, $nivelInfo['xp_siguiente'] - $nuevaXp);
        $rangoNivel = max(1, $nivelInfo['xp_siguiente'] - $nivelInfo['xp_actual_min']);
        $progresoNivel = min(100, round((($nuevaXp - $nivelInfo['xp_actual_min']) / $rangoNivel) * 100));

        // Construir mensaje narrativo RPG
        $mensajeNarrativo = $this->generarMensajeNarrativo(
            $user->name,
            $nuevoTitulo,
            $nuevoNivel,
            $nuevaXp,
            $xpGanada,
            $xpFaltante,
            $subioNivel,
            $misionesCompletadas
        );

        return [
            'xp_ganada' => $xpGanada,
            'xp_total' => $nuevaXp,
            'nivel' => $nuevoNivel,
            'titulo' => $nuevoTitulo,
            'subio_nivel' => $subioNivel,
            'xp_faltante_siguiente_nivel' => $xpFaltante,
            'porcentaje_progreso_nivel' => $progresoNivel,
            'misiones_actualizadas' => $misionesCompletadas,
            'mensaje_narrativo' => $mensajeNarrativo,
        ];
    }

    /**
     * Obtiene el rango de nivel según la XP acumulada.
     */
    public function obtenerInfoNivelPorXp(int $xp): array
    {
        $niveles = $this->gameMechanics['sistema_niveles'] ?? [
            ['nivel' => 1, 'titulo' => 'Recluta de Hierro', 'xp_min' => 0, 'xp_max' => 99],
            ['nivel' => 2, 'titulo' => 'Guerrero de Cobre', 'xp_min' => 100, 'xp_max' => 249],
            ['nivel' => 3, 'titulo' => 'Caballero de Bronce', 'xp_min' => 250, 'xp_max' => 499],
            ['nivel' => 4, 'titulo' => 'Campeón de Plata', 'xp_min' => 500, 'xp_max' => 999],
            ['nivel' => 5, 'titulo' => 'Titán de Hyperion', 'xp_min' => 1000, 'xp_max' => 999999],
        ];

        $nivelActual = $niveles[0];
        $siguienteXp = 100;

        foreach ($niveles as $idx => $n) {
            if ($xp >= $n['xp_min']) {
                $nivelActual = $n;
                $siguienteXp = isset($niveles[$idx + 1]) ? $niveles[$idx + 1]['xp_min'] : $n['xp_max'];
            }
        }

        return [
            'nivel' => $nivelActual['nivel'],
            'titulo' => $nivelActual['titulo'],
            'xp_actual_min' => $nivelActual['xp_min'],
            'xp_siguiente' => $siguienteXp,
        ];
    }

    /**
     * Actualiza el progreso de misiones semanales y otorga recompensas extras.
     */
    protected function actualizarMisionesUsuario(User $user, WorkoutSession $session): array
    {
        $completadas = [];
        $quests = $user->quests()->where('completed', false)->get();

        foreach ($quests as $quest) {
            $updated = false;

            if ($quest->quest_key === 'mision_calibracion' || str_starts_with($quest->quest_key, 'mision_calibracion')) {
                $quest->current_count += 1;
                $updated = true;
            } elseif ($quest->quest_key === 'mision_3_rutinas' || str_starts_with($quest->quest_key, 'mision_3_rutinas')) {
                $quest->current_count += 1;
                $updated = true;
            } elseif (($quest->quest_key === 'mision_progresion' || str_starts_with($quest->quest_key, 'mision_progresion')) && $session->progressive_overload_applied) {
                $quest->current_count += 1;
                $updated = true;
            }

            if ($updated) {
                if ($quest->current_count >= $quest->target_count) {
                    $quest->completed = true;
                    $quest->completed_at = now();
                    $user->increment('current_xp', $quest->reward_xp);
                    $completadas[] = [
                        'titulo' => $quest->title,
                        'xp_extra' => $quest->reward_xp,
                    ];
                }
                $quest->save();
            }
        }

        return $completadas;
    }

    /**
     * Genera la narrativa inmersiva estilo RPG de Hyperion.
     */
    protected function generarMensajeNarrativo(
        string $nombre,
        string $titulo,
        int $nivel,
        int $xpTotal,
        int $xpGanada,
        int $xpFaltante,
        bool $subioNivel,
        array $misionesCompletadas
    ): string {
        $lore = "⚔️ ¡BATALLA COMPLETADA CON ÉXITO, " . strtoupper($nombre) . "! ⚔️\n\n";

        if ($subioNivel) {
            $lore .= "🔥 ¡HAZ ASCENDIDO DE RANGO! 🔥\n";
            $lore .= "Los dioses de Hyperion reconocen tu tenacidad. Has alcanzado el Nivel {$nivel}: {$titulo}.\n\n";
        } else {
            $lore .= "Tu disciplina como {$titulo} (Nivel {$nivel}) forja un espíritu inquebrantable.\n\n";
        }

        $lore .= "✨ Recompensa de batalla: +{$xpGanada} XP acumulados.\n";
        $lore .= "💎 Experiencia Total: {$xpTotal} XP\n";

        if ($xpFaltante > 0) {
            $lore .= "🎯 Siguiente Ascenso: Te faltan solo {$xpFaltante} XP para alcanzar tu próxima categoría.\n";
        } else {
            $lore .= "👑 Rango Máximo: Eres el epítome de un Titán de Hyperion.\n";
        }

        if (!empty($misionesCompletadas)) {
            $lore .= "\n📜 ¡MISIÓN CUMPLIDA!\n";
            foreach ($misionesCompletadas as $m) {
                $lore .= "- {$m['titulo']} (+{$m['xp_extra']} XP extra bonificados)\n";
            }
        }

        $lore .= "\nDescansa, hidrátate y prepárate. Mañana la forja continuará.";

        return $lore;
    }
}
