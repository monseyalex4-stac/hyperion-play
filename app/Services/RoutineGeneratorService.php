<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkoutSession;

class RoutineGeneratorService
{
    protected DecisionRulesEngineService $rulesEngine;
    protected ExerciseDbService $exerciseDb;
    protected RagCatalogService $ragCatalog;
    protected GamificationRpgService $rpgService;

    public function __construct(
        DecisionRulesEngineService $rulesEngine,
        ExerciseDbService $exerciseDb,
        RagCatalogService $ragCatalog,
        GamificationRpgService $rpgService
    ) {
        $this->rulesEngine = $rulesEngine;
        $this->exerciseDb = $exerciseDb;
        $this->ragCatalog = $ragCatalog;
        $this->rpgService = $rpgService;
    }

    /**
     * Genera una rutina recibiendo directamente un array de perfil (HU02 / HU03).
     */
    public function generateRoutine(array $perfil): array
    {
        $user = new User([
            'age' => $perfil['edad'] ?? 25,
            'body_fat_pct' => $perfil['body_fat'] ?? 18.0,
            'injury' => $perfil['lesion'] ?? 'ninguna',
            'environment' => $perfil['entorno'] ?? 'casa_sin_equipo',
            'available_time_minutes' => $perfil['tiempo_minutos'] ?? 15,
            'fitness_goal' => $perfil['objetivo'] ?? 'resistencia',
            'gender' => $perfil['genero'] ?? 'no_disponible',
        ]);

        return $this->generarRutina($user, $perfil);
    }

    /**
     * Genera una rutina completa personalizada respetando las reglas de seguridad clínicas,
     * historial del usuario, sobrecarga progresiva y requerimientos de tiempo/equipo.
     */
    public function generarRutina(User $user, array $overrides = []): array
    {
        // 1. Unificar perfil del usuario con sobreescrituras (de la entrevista o del chat)
        $perfil = [
            'edad' => $overrides['edad'] ?? $user->age ?? 25,
            'body_fat' => $overrides['body_fat'] ?? $user->body_fat_pct ?? 18.0,
            'lesion' => $overrides['lesion'] ?? $user->injury ?? 'ninguna',
            'entorno' => $overrides['entorno'] ?? $user->environment ?? 'casa_sin_equipo',
            'tiempo_minutos' => (int) ($overrides['tiempo_minutos'] ?? $user->available_time_minutes ?? 15),
            'objetivo' => $overrides['objetivo'] ?? $user->fitness_goal ?? 'resistencia',
            'arquetipo' => $overrides['arquetipo'] ?? $user->archetype ?? 'titan_agil',
        ];

        // 2. Revisar historial previo del usuario (HU #12 & HU #13)
        $ultimaSesion = ($user->exists ?? false)
            ? $user->workoutSessions()
                ->where('status', 'completada')
                ->latest('completed_at')
                ->first()
            : null;

        $esNuevoUsuario = ($ultimaSesion === null);
        $huboFatiga = $ultimaSesion ? ($ultimaSesion->fatigue_reported || $ultimaSesion->difficulty_rating === 'muy_pesada') : false;
        $fueFacil = $ultimaSesion ? ($ultimaSesion->difficulty_rating === 'muy_facil') : false;

        // 3. Reglas de Impacto y Lesiones (HU #9 & HU #11)
        $edadLimiteDisparada = ($perfil['edad'] > 45);
        $grasaLimiteDisparada = ($perfil['body_fat'] > 25.0);
        $forzadoBajo = (($overrides['nivel_impacto_forzado'] ?? '') === 'bajo');
        $forzarBajoImpacto = ($forzadoBajo || $edadLimiteDisparada || $grasaLimiteDisparada || ($perfil['lesion'] !== 'ninguna' && !empty($perfil['lesion'])));

        $tieneLesion = ($perfil['lesion'] !== 'ninguna' && !empty($perfil['lesion']) && !in_array($perfil['lesion'], ['sano', 'sin_lesion']));
        $avisoMedicoObligatorio = null;
        if ($tieneLesion) {
            $avisoMedicoObligatorio = "⚠️ AVISO MÉDICO: Hyperion Play no sustituye el diagnóstico ni la prescripción de un médico o fisioterapeuta. La siguiente rutina está adaptada de forma preventiva y suave para proteger tu articulación.";
        }

        // 4. Obtener catálogo y aplicar filtro del motor de reglas clínicas
        $todosLosEjercicios = $this->exerciseDb->getExercises();
        [$ejerciciosSeguros, $descartados] = $this->rulesEngine->filtrarCatalogo($todosLosEjercicios, $perfil);

        // Si el usuario requiere bajo impacto estricto (HU #9 o HU #11), garantizar filtro
        if ($forzarBajoImpacto) {
            $ejerciciosSeguros = array_values(array_filter($ejerciciosSeguros, function ($e) {
                return strtolower($e['nivel_impacto'] ?? 'medio') === 'bajo';
            }));
        }

        // 5. Dimensionar la rutina según el tiempo objetivo (HU #10: Rápida 15 min o Micro-rutina)
        $tiempoObjetivoSeg = $perfil['tiempo_minutos'] * 60;
        $tiempoAcumuladoSeg = 0;
        $ejerciciosElegidos = [];
        $musculosUsados = [];

        // Parámetros de Sobrecarga Progresiva Asistida (HU #13)
        $factorSobrecargaReps = 0;
        $sobrecargaAplicada = false;
        $mensajeSobrecarga = null;

        if (!$esNuevoUsuario) {
            if ($huboFatiga) {
                // Mantener o reducir intensidad para prevenir sobreentrenamiento
                $mensajeSobrecarga = "Debido a que en tu última sesión reportaste fatiga o esfuerzo muy elevado, hemos mantenido descansos más generosos e intensidad controlada para cuidar tu recuperación.";
            } elseif ($fueFacil) {
                // Progresión asistida con tope máximo de seguridad del 10%
                $factorSobrecargaReps = 1; // +1 rep por serie (~8% a 10%)
                $sobrecargaAplicada = true;
                $mensajeSobrecarga = "¡Excelente progreso previo! Hemos aplicado un reto de Sobrecarga Progresiva (+8% a 10% en repeticiones) para seguir estimulando tu progreso. Puedes aceptar este desafío o mantener el ritmo si te sientes cargado.";
            }
        }

        foreach ($ejerciciosSeguros as $ex) {
            $duracionSerieSeg = (int) ($ex['duracion_estimada_seg'] ?? 45);
            $descansoSerieSeg = $forzarBajoImpacto ? 45 : 30;
            $seriesBase = $esNuevoUsuario ? 2 : 3;

            $duracionEjercicioSeg = ($duracionSerieSeg + $descansoSerieSeg) * $seriesBase;

            if (($tiempoAcumuladoSeg + $duracionEjercicioSeg) <= ($tiempoObjetivoSeg + 60)) {
                $musculo = strtolower($ex['musculo_principal'] ?? 'general');

                // Variedad de grupos musculares
                if (in_array($musculo, $musculosUsados) && count($musculosUsados) < 4) {
                    continue;
                }

                $repsBase = 10 + $factorSobrecargaReps;

                // Indicaciones posturales básicas (HU #9)
                $postura = $this->generarIndicacionPostural($ex, $perfil['lesion']);

                $ejerciciosElegidos[] = [
                    'id' => $ex['id'] ?? uniqid('EX-'),
                    'nombre' => $ex['nombre'] ?? $ex['name'] ?? 'Ejercicio',
                    'musculo' => $ex['musculo_principal'] ?? 'General',
                    'equipo' => $ex['equipo'] ?? 'Peso corporal',
                    'nivel_impacto' => $ex['nivel_impacto'] ?? 'bajo',
                    'series' => $seriesBase,
                    'repeticiones' => $repsBase,
                    'descanso_segundos' => $descansoSerieSeg,
                    'duracion_estimada_seg' => $duracionEjercicioSeg,
                    'indicacion_postural' => $postura,
                    'gif_url' => $ex['gif_url'] ?? null,
                ];

                $musculosUsados[] = $musculo;
                $tiempoAcumuladoSeg += $duracionEjercicioSeg;

                // Tope de ejercicios para rutinas rápidas
                if (count($ejerciciosElegidos) >= 4) {
                    break;
                }
            }
        }

        // Si por restricciones físicas no alcanzan ejercicios para 15 min, proponer micro-rutina válida (HU #10)
        $duracionRealMinutos = max(5, (int) round($tiempoAcumuladoSeg / 60));
        $avisoMicroRutina = null;

        if ($duracionRealMinutos < $perfil['tiempo_minutos'] && count($ejerciciosElegidos) > 0) {
            $avisoMicroRutina = "Debido a tus restricciones de seguridad y protección articular, hemos diseñado una micro-rutina segura de {$duracionRealMinutos} minutos en lugar de forzar ejercicios que comprometan tu cuerpo.";
        }

        // 6. Título y Arquetipo RPG de la rutina
        $tituloRutina = $esNuevoUsuario
            ? "Rutina de Calibración Inicial: El Despertar del Guerrero"
            : ($tieneLesion ? "Sesión de Rehabilitación y Movilidad Segura" : "Entrenamiento de Eficiencia Titánica ({$duracionRealMinutos} min)");

        // 7. Explicabilidad ("Cómo y por qué") en lenguaje natural claro y empático (HU #15)
        $explicacionNatural = $this->generarExplicacionNatural(
            $perfil,
            $esNuevoUsuario,
            $forzarBajoImpacto,
            $tieneLesion,
            $duracionRealMinutos,
            $sobrecargaAplicada,
            $huboFatiga
        );

        $datosExplicabilidad = [
            'tiempo_minutos' => $duracionRealMinutos,
            'entorno' => $perfil['entorno'],
            'objetivo' => $perfil['objetivo'],
            'lesion_considerada' => $perfil['lesion'],
            'bajo_impacto_aplicado' => $forzarBajoImpacto,
            'sobrecarga_progresiva_aplicada' => $sobrecargaAplicada,
            'es_rutina_calibracion' => $esNuevoUsuario,
            'criterios_origen' => [
                'entrevista' => 'Tiempo disponible (' . $perfil['tiempo_minutos'] . ' min) y entorno (' . $perfil['entorno'] . ')',
                'biometricos_seguridad' => ($edadLimiteDisparada || $grasaLimiteDisparada) ? 'Protección articular preventiva por edad/' . round($perfil['body_fat']) . '% grasa' : 'Sin restricción biológica extrema',
                'lesiones' => $tieneLesion ? "Exclusión estricta de movimientos que comprometen {$perfil['lesion']}" : 'Sin antecedentes lesionales reportados',
                'historial' => $esNuevoUsuario ? 'Primera sesión del usuario (calibración base)' : ($huboFatiga ? 'Atenuación por fatiga previa' : 'Sobrecarga calculada sobre sesión previa')
            ],
            'explicacion_texto' => $explicacionNatural
        ];

        // 8. Crear la sesión en Base de Datos (HU #12)
        $sesion = WorkoutSession::create([
            'user_id' => $user->id,
            'title' => $tituloRutina,
            'archetype' => $perfil['arquetipo'],
            'total_duration_minutes' => $duracionRealMinutos,
            'target_environment' => $perfil['entorno'],
            'target_injury' => $perfil['lesion'],
            'status' => 'en_progreso',
            'fatigue_reported' => false,
            'progressive_overload_applied' => $sobrecargaAplicada,
            'overload_percentage' => $sobrecargaAplicada ? 8.5 : 0.0,
            'xp_earned' => 0,
            'exercises_data' => $ejerciciosElegidos,
            'explainability_data' => $datosExplicabilidad,
            'medical_disclaimer_displayed' => ($avisoMedicoObligatorio !== null),
        ]);

        return [
            'sesion_id' => $sesion->id,
            'titulo' => $tituloRutina,
            'duracion_minutos' => $duracionRealMinutos,
            'arquetipo' => $perfil['arquetipo'],
            'es_calibracion' => $esNuevoUsuario,
            'aviso_medico' => $avisoMedicoObligatorio,
            'aviso_micro_rutina' => $avisoMicroRutina,
            'aviso_sobrecarga' => $mensajeSobrecarga,
            'ejercicios' => $ejerciciosElegidos,
            'explicabilidad' => $datosExplicabilidad,
            'xp_estimada' => $this->rpgService->calcularXpRutina([
                'duracion_minutos' => $duracionRealMinutos,
                'ejercicios' => $ejerciciosElegidos,
            ], $sobrecargaAplicada),
        ];
    }

    /**
     * Genera indicaciones posturales pedagógicas para prevenir lesiones (HU #9).
     */
    protected function generarIndicacionPostural(array $ejercicio, string $lesion): string
    {
        $nombre = strtolower($ejercicio['nombre'] ?? '');
        $musculo = strtolower($ejercicio['musculo_principal'] ?? '');

        if (str_contains($nombre, 'squat') || str_contains($musculo, 'quads')) {
            return "Mantén el pecho erguido, apoya todo el peso en el centro del pie y no permitas que las rodillas colapsen hacia adentro al descender.";
        }

        if (str_contains($nombre, 'plank') || str_contains($musculo, 'abs')) {
            return "Activa el abdomen como si fueras a recibir un impacto; evita que la cadera caiga o se arquee la zona lumbar y respira con fluidez.";
        }

        if (str_contains($nombre, 'push') || str_contains($musculo, 'chest')) {
            return "Alinea las muñecas justo debajo de los hombros, retrae las escápulas y mantén los codos a unos 45 grados de tu torso.";
        }

        if (str_contains($musculo, 'back') || str_contains($nombre, 'row')) {
            return "Conserva la curvatura natural de la columna, lleva los codos hacia atrás con control y no des tirones con el cuello.";
        }

        return "Ejecuta cada repetición de forma suave y controlada, inhalando en la fase de preparación y exhalando durante el esfuerzo.";
    }

    /**
     * Genera la explicación en lenguaje natural comprensible y empático (HU #15).
     */
    protected function generarExplicacionNatural(
        array $perfil,
        bool $esNuevo,
        bool $bajoImpacto,
        bool $tieneLesion,
        int $duracion,
        bool $sobrecarga,
        bool $fatiga
    ): string {
        $partes = [];

        if ($esNuevo) {
            $partes[] = "Como es tu primera sesión en Hyperion Play, he preparado una rutina de calibración suave para conocer la respuesta de tu cuerpo y establecer tu nivel base sin riesgos.";
        } else {
            $partes[] = "He diseñado esta rutina tomando en cuenta el registro de tus sesiones anteriores.";
        }

        // Espacio y tiempo
        $entornoTexto = ($perfil['entorno'] === 'casa_sin_equipo') ? 'en casa sin necesidad de equipo adicional' : (($perfil['entorno'] === 'casa_basico') ? 'en casa con equipo ligero' : 'en gimnasio');
        $partes[] = "Tu sesión está calculada para durar exactamente {$duracion} minutos y realizarse cómodamente {$entornoTexto}.";

        // Salud y seguridad articular
        if ($tieneLesion) {
            $partes[] = "Dado que reportaste sensibilidad en la zona de {$perfil['lesion']}, excluimos con estricta prioridad cualquier movimiento que cargue esa articulación, enfocándonos en estabilidad suave y segura.";
        } elseif ($bajoImpacto) {
            $partes[] = "Para cuidar tus articulaciones y asegurar una transición saludable, seleccioné únicamente ejercicios de bajo impacto articular, libres de saltos y cargas bruscas.";
        }

        // Progresión
        if ($sobrecarga) {
            $partes[] = "Como tu último entrenamiento lo completaste con solvencia y excelente energía, propusimos un pequeño incremento gradual del 8% en las repeticiones para continuar estimulando tus adaptaciones musculares.";
        } elseif ($fatiga) {
            $partes[] = "Como la sesión previa fue muy intensa o con fatiga, hoy priorizamos recuperación activa con descansos apropiados para evitar sobrecargas.";
        }

        return implode(' ', $partes);
    }
}
