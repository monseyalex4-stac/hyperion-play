<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkoutSession;

class ConversationalChatbotService
{
    protected RoutineGeneratorService $routineGenerator;
    protected GamificationRpgService $rpgService;
    protected ChatbotTrainingService $trainingService;

    public function __construct(
        RoutineGeneratorService $routineGenerator,
        GamificationRpgService $rpgService,
        ?ChatbotTrainingService $trainingService = null
    ) {
        $this->routineGenerator = $routineGenerator;
        $this->rpgService = $rpgService;
        $this->trainingService = $trainingService ?? new ChatbotTrainingService();
    }

    /**
     * Procesa un mensaje del usuario y orquesta la respuesta conversacional de Hyperion.
     */
    public function procesarMensaje(User $user, string $mensaje): array
    {
        $mensajeLimpio = mb_strtolower(trim($mensaje));

        // 1. Guardrail de Emergencia Médica Inmediata (REG-SEG-05 + alarmas entrenadas)
        $emergencia = $this->detectarEmergenciaMedicaDetalle($mensajeLimpio);
        if ($emergencia['detected']) {
            $textoAlerta = !empty($emergencia['custom_response'])
                ? $emergencia['custom_response']
                : "🚨 ¡ALTO, AVENTURERO! Tu seguridad es primordial.\n\nHe detectado que refieres dolor agudo, mareos o molestia torácica. Suspende de inmediato cualquier actividad física, siéntate en un lugar fresco y seguro, y acude o llama a un profesional de la salud o servicio de urgencias médicas. Hyperion no prescribirá ejercicios bajo estas circunstancias.";

            return $this->formatearRespuesta([
                'emisor' => 'hyperion',
                'tipo' => 'alerta_emergencia',
                'texto' => $textoAlerta,
                'rutina' => null,
                'perfil_actualizado' => false,
                'entrenamiento_aplicado' => $emergencia['source'] === 'manual' ? [
                    'tipo' => 'alarma',
                    'trigger' => $emergencia['keyword'],
                ] : null,
            ]);
        }

        // 1.5 Respuestas Personalizadas Entrenadas Manualmente (Q&A)
        $qaMatch = $this->trainingService->findQaMatch($mensaje);
        if ($qaMatch !== null) {
            return $this->formatearRespuesta([
                'emisor' => 'hyperion',
                'tipo' => 'respuesta_entrenada',
                'texto' => $qaMatch['response_text'],
                'rutina' => null,
                'perfil_actualizado' => false,
                'entrenamiento_aplicado' => [
                    'tipo' => 'qa',
                    'id' => $qaMatch['id'],
                    'trigger' => $qaMatch['trigger_matched'],
                ],
            ]);
        }

        // 2. Consulta de Historial de Entrenamientos (HU #12)
        if ($this->esConsultaHistorial($mensajeLimpio)) {
            return $this->formatearRespuesta($this->generarRespuestaHistorial($user));
        }

        // 3. Consulta de Explicabilidad ("Cómo y por qué") (HU #15)
        if ($this->esConsultaExplicabilidad($mensajeLimpio)) {
            return $this->formatearRespuesta($this->generarRespuestaExplicabilidad($user));
        }

        // 4. Confirmación de Rutina Completada / Feedback (HU #12, #13, #14)
        if ($this->esConfirmacionFinalizacion($mensajeLimpio)) {
            return $this->formatearRespuesta($this->generarRespuestaFinalizacion($user, $mensajeLimpio));
        }

        // 5. Extracción de Entidades y Parámetros en Lenguaje Natural (HU #7 + Vocabulario NLP entrenado)
        $entidades = $this->extraerEntidadesNLP($mensajeLimpio);
        $huboCambios = false;

        if ($entidades['tiempo'] !== null) {
            $user->available_time_minutes = $entidades['tiempo'];
            $huboCambios = true;
        }

        if ($entidades['entorno'] !== null) {
            $user->environment = $entidades['entorno'];
            $huboCambios = true;
        }

        if ($entidades['lesion'] !== null) {
            $user->injury = $entidades['lesion'];
            $huboCambios = true;
        }

        if ($entidades['objetivo'] !== null) {
            $user->fitness_goal = $entidades['objetivo'];
            $huboCambios = true;
        }

        if ($huboCambios) {
            $user->save();
        }

        // 6. Si el usuario pide generar una rutina o ya respondió la entrevista
        if ($this->esSolicitudRutina($mensajeLimpio, $entidades) || $this->tieneParametrosSuficientes($user)) {
            // Asignar valores por defecto conservadores si algún dato fue omitido (HU #7)
            $this->aplicarValoresPorDefectoConservadores($user);

            $overrides = [];
            if ($entidades['tiempo'] !== null) {
                $overrides['tiempo_minutos'] = $entidades['tiempo'];
            }
            if (!empty($entidades['musculos'])) {
                $overrides['musculos_objetivo'] = $entidades['musculos'];
                $overrides['nombres_musculos'] = $entidades['nombres_musculos'];
            }

            $rutinaGenerada = $this->routineGenerator->generarRutina($user, $overrides);

            $textoRespuesta = "⚡ ¡RUTINA PREPARADA POR HYPERION! ⚡\n\n";

            // Si hay aviso médico obligatorio (HU #11), mostrarlo de forma destacada
            if (!empty($rutinaGenerada['aviso_medico'])) {
                $textoRespuesta .= "{$rutinaGenerada['aviso_medico']}\n\n";
            }

            if (!empty($rutinaGenerada['aviso_micro_rutina'])) {
                $textoRespuesta .= "ℹ️ {$rutinaGenerada['aviso_micro_rutina']}\n\n";
            }

            if (!empty($rutinaGenerada['aviso_sobrecarga'])) {
                $textoRespuesta .= "📈 {$rutinaGenerada['aviso_sobrecarga']}\n\n";
            }

            $textoRespuesta .= "Título: {$rutinaGenerada['titulo']}\n";
            $textoRespuesta .= "⏱️ Duración: {$rutinaGenerada['duracion_minutos']} minutos\n";
            $textoRespuesta .= "🛡️ Arquetipo: " . ucfirst(str_replace('_', ' ', $rutinaGenerada['arquetipo'])) . "\n";
            $textoRespuesta .= "💎 Recompensa estimada: +{$rutinaGenerada['xp_estimada']} XP\n\n";

            $textoRespuesta .= "📋 Plan de Ejercicios:\n";
            foreach ($rutinaGenerada['ejercicios'] as $idx => $ej) {
                $num = $idx + 1;
                $textoRespuesta .= "{$num}. {$ej['nombre']} ({$ej['musculo']})\n";
                $textoRespuesta .= "   - {$ej['series']} series de {$ej['repeticiones']} repeticiones (Descanso: {$ej['descanso_segundos']}s)\n";
                $textoRespuesta .= "   - 💡 Técnica: {$ej['indicacion_postural']}\n";
            }

            $textoRespuesta .= "\n¿Deseas saber cómo y por qué se construyó esta rutina? Escribe '¿Por qué esta rutina?'. Cuando termines de entrenar, avísame con 'Terminé la rutina' para sumar tus puntos de experiencia.";

            return $this->formatearRespuesta([
                'emisor' => 'hyperion',
                'tipo' => 'rutina_generada',
                'texto' => $textoRespuesta,
                'rutina' => $rutinaGenerada,
                'perfil_actualizado' => true,
            ]);
        }

        // 7. Flujo de Entrevista Conversacional Adaptativa (HU #7)
        return $this->formatearRespuesta($this->continuarEntrevistaConversacional($user, $mensajeLimpio));
    }

    /**
     * Limpia y formatea la respuesta final para asegurar texto plano sin Markdown.
     */
    protected function formatearRespuesta(array $respuesta): array
    {
        if (isset($respuesta['texto'])) {
            $respuesta['texto'] = $this->limpiarTextoMarkdown($respuesta['texto']);
        }
        return $respuesta;
    }

    /**
     * Limpia completamente caracteres y formato Markdown mediante expresiones regulares (Regex).
     */
    public function limpiarTextoMarkdown(?string $texto): string
    {
        if (empty($texto)) {
            return '';
        }

        // Eliminar bloques de código ```...```
        $texto = preg_replace('/```[\s\S]*?```/', '', $texto);
        // Eliminar código en línea `...`
        $texto = preg_replace('/`([^`]+)`/', '$1', $texto);
        // Eliminar encabezados Markdown (#, ##, ###, etc.)
        $texto = preg_replace('/^\s*#{1,6}\s+/m', '', $texto);
        // Eliminar negritas y cursivas (***, **, *, ___, __, _)
        $texto = preg_replace('/(\*{1,3}|_{1,3})([\s\S]*?)\1/', '$2', $texto);
        // Eliminar asteriscos y guiones bajos residuales sueltos
        $texto = str_replace(['**', '*', '__', '_'], '', $texto);
        // Eliminar tachado ~~texto~~
        $texto = preg_replace('/~~(.*?)~~/', '$1', $texto);
        // Eliminar enlaces [texto](url)
        $texto = preg_replace('/\[(.*?)\]\([^)]+\)/', '$1', $texto);
        // Eliminar citas Markdown (> texto)
        $texto = preg_replace('/^\s*>\s+/m', '', $texto);

        return trim($texto);
    }

    /**
     * Entrevista adaptativa guiada paso a paso si faltan datos.
     */
    protected function continuarEntrevistaConversacional(User $user, string $mensaje): array
    {
        $saludo = "¡Saludos, aventurero {$user->name}! Soy HYPERION, tu Titán mentor de acondicionamiento físico.\n\n";

        // Paso 1: Tiempo disponible
        if (empty($user->available_time_minutes) || $user->available_time_minutes === 0) {
            return [
                'emisor' => 'hyperion',
                'tipo' => 'pregunta_entrevista',
                'texto' => $saludo . "Para forjar el plan ideal hoy, ¿de cuántos minutos dispones para tu sesión? (Por ejemplo: 15 min, 30 min o poco tiempo).",
                'rutina' => null,
                'perfil_actualizado' => false,
            ];
        }

        // Paso 2: Entorno y equipamiento
        if (empty($user->environment)) {
            return [
                'emisor' => 'hyperion',
                'tipo' => 'pregunta_entrevista',
                'texto' => "Excelente. ¿Dónde entrenarás hoy y qué equipamiento tienes a mano? (Por ejemplo: en mi cuarto sin equipo, casa con mancuernas o en el gimnasio).",
                'rutina' => null,
                'perfil_actualizado' => false,
            ];
        }

        // Paso 3: Lesiones o limitaciones físicas
        if (empty($user->injury)) {
            return [
                'emisor' => 'hyperion',
                'tipo' => 'pregunta_entrevista',
                'texto' => "¿Presentas alguna molestia o lesión articular actualmente? (Por ejemplo: dolor de rodilla, espalda baja, hombro o ninguna, estoy al 100%).",
                'rutina' => null,
                'perfil_actualizado' => false,
            ];
        }

        // Si ya tiene todo pero mandó un saludo o consulta general
        return [
            'emisor' => 'hyperion',
            'tipo' => 'conversacion_general',
            'texto' => $saludo . "Tengo registrados tus parámetros actuales: {$user->available_time_minutes} min, entorno " . str_replace('_', ' ', $user->environment) . " y condición física " . ($user->injury === 'ninguna' ? 'sin lesiones' : 'cuidando ' . $user->injury) . ".\n\n¿Deseas que preparemos tu rutina ahora? Escribe 'Generar rutina' o indícame si deseas ajustar algún dato.",
            'rutina' => null,
            'perfil_actualizado' => false,
        ];
    }

    /**
     * Aplica valores conservadores por defecto si el usuario omitió datos (HU #7).
     */
    protected function aplicarValoresPorDefectoConservadores(User $user): void
    {
        if (empty($user->available_time_minutes)) {
            $user->available_time_minutes = 15;
        }
        if (empty($user->environment)) {
            $user->environment = 'casa_sin_equipo';
        }
        if (empty($user->injury)) {
            $user->injury = 'ninguna';
        }
        if (empty($user->fitness_goal)) {
            $user->fitness_goal = 'resistencia';
        }
        $user->interview_completed = true;
        $user->save();
    }

    /**
     * Extrae intenciones y entidades a partir de texto libre usando reglas NLP (REG-NLP).
     */
    protected function extraerEntidadesNLP(string $text): array
    {
        $resultado = [
            'tiempo' => null,
            'entorno' => null,
            'lesion' => null,
            'objetivo' => null,
            'musculos' => [],
            'nombres_musculos' => [],
        ];

        // 1. Detección Inteligente de Tiempo (5 a 120 minutos)
        if (preg_match('/(\d+)\s*(?:minutos?|mins?|m\b)/i', $text, $matches)) {
            $resultado['tiempo'] = max(5, min(120, (int) $matches[1]));
        } elseif (preg_match('/(?:tiempo|para|de|durante)\s*(\d+)\s*(?:minutos?|mins?|m)?\b/i', $text, $matches)) {
            $val = (int) $matches[1];
            if ($val >= 5 && $val <= 120) {
                $resultado['tiempo'] = $val;
            }
        } elseif (str_contains($text, 'media hora') || str_contains($text, '30 min')) {
            $resultado['tiempo'] = 30;
        } elseif (str_contains($text, 'hora y media') || str_contains($text, '90 min')) {
            $resultado['tiempo'] = 90;
        } elseif (str_contains($text, 'una hora') || str_contains($text, '1 hora') || str_contains($text, '60 min')) {
            $resultado['tiempo'] = 60;
        } elseif (str_contains($text, 'poco tiempo') || str_contains($text, 'rapida') || str_contains($text, 'rápida') || str_contains($text, '15 min')) {
            $resultado['tiempo'] = 15;
        } elseif (str_contains($text, 'micro') || str_contains($text, '10 min')) {
            $resultado['tiempo'] = 10;
        }

        // 2. Detección de Grupos Musculares Específicos Solicitados
        $mapaMusculos = [
            'pecho'        => ['db' => ['pectorals', 'chest'], 'label' => 'Pecho'],
            'pectoral'     => ['db' => ['pectorals', 'chest'], 'label' => 'Pecho'],
            'pectorales'   => ['db' => ['pectorals', 'chest'], 'label' => 'Pecho'],
            'bicep'        => ['db' => ['biceps'], 'label' => 'Bíceps'],
            'biceps'       => ['db' => ['biceps'], 'label' => 'Bíceps'],
            'bícep'        => ['db' => ['biceps'], 'label' => 'Bíceps'],
            'bíceps'       => ['db' => ['biceps'], 'label' => 'Bíceps'],
            'tricep'       => ['db' => ['triceps'], 'label' => 'Tríceps'],
            'triceps'      => ['db' => ['triceps'], 'label' => 'Tríceps'],
            'trícep'       => ['db' => ['triceps'], 'label' => 'Tríceps'],
            'tríceps'      => ['db' => ['triceps'], 'label' => 'Tríceps'],
            'brazo'        => ['db' => ['biceps', 'triceps', 'forearms'], 'label' => 'Brazos'],
            'brazos'       => ['db' => ['biceps', 'triceps', 'forearms'], 'label' => 'Brazos'],
            'antebrazo'    => ['db' => ['forearms'], 'label' => 'Antebrazos'],
            'antebrazos'   => ['db' => ['forearms'], 'label' => 'Antebrazos'],
            'espalda'      => ['db' => ['lats', 'upper back', 'middle back', 'lower back', 'traps', 'spine'], 'label' => 'Espalda'],
            'dorsal'       => ['db' => ['lats'], 'label' => 'Espalda'],
            'dorsales'     => ['db' => ['lats'], 'label' => 'Espalda'],
            'trapecio'     => ['db' => ['traps'], 'label' => 'Trapecios'],
            'trapecios'    => ['db' => ['traps'], 'label' => 'Trapecios'],
            'hombro'       => ['db' => ['delts', 'shoulders'], 'label' => 'Hombros'],
            'hombros'      => ['db' => ['delts', 'shoulders'], 'label' => 'Hombros'],
            'deltoide'     => ['db' => ['delts', 'shoulders'], 'label' => 'Hombros'],
            'deltoides'    => ['db' => ['delts', 'shoulders'], 'label' => 'Hombros'],
            'pierna'       => ['db' => ['quads', 'quadriceps', 'hamstrings', 'calves', 'adductors', 'abductors'], 'label' => 'Pierna'],
            'piernas'      => ['db' => ['quads', 'quadriceps', 'hamstrings', 'calves', 'adductors', 'abductors'], 'label' => 'Pierna'],
            'cuadricep'    => ['db' => ['quads', 'quadriceps'], 'label' => 'Cuádriceps'],
            'cuadriceps'   => ['db' => ['quads', 'quadriceps'], 'label' => 'Cuádriceps'],
            'cuádricep'    => ['db' => ['quads', 'quadriceps'], 'label' => 'Cuádriceps'],
            'cuádriceps'   => ['db' => ['quads', 'quadriceps'], 'label' => 'Cuádriceps'],
            'femoral'      => ['db' => ['hamstrings'], 'label' => 'Femorales'],
            'femorales'    => ['db' => ['hamstrings'], 'label' => 'Femorales'],
            'pantorrilla'  => ['db' => ['calves'], 'label' => 'Pantorrillas'],
            'pantorrillas' => ['db' => ['calves'], 'label' => 'Pantorrillas'],
            'gemelo'       => ['db' => ['calves'], 'label' => 'Pantorrillas'],
            'gemelos'      => ['db' => ['calves'], 'label' => 'Pantorrillas'],
            'gluteo'       => ['db' => ['glutes'], 'label' => 'Glúteo'],
            'gluteos'      => ['db' => ['glutes'], 'label' => 'Glúteo'],
            'glúteo'       => ['db' => ['glutes'], 'label' => 'Glúteo'],
            'glúteos'      => ['db' => ['glutes'], 'label' => 'Glúteo'],
            'pompa'        => ['db' => ['glutes'], 'label' => 'Glúteo'],
            'pompas'       => ['db' => ['glutes'], 'label' => 'Glúteo'],
            'abdomen'      => ['db' => ['abs', 'abdominals'], 'label' => 'Abdomen'],
            'abdominales'  => ['db' => ['abs', 'abdominals'], 'label' => 'Abdomen'],
            'core'         => ['db' => ['abs', 'abdominals'], 'label' => 'Core'],
            'abs'          => ['db' => ['abs', 'abdominals'], 'label' => 'Abdomen'],
            'cardio'       => ['db' => ['cardiovascular system'], 'label' => 'Cardio'],
        ];

        $dinamicos = $this->trainingService->getDynamicSynonyms();
        $normText = $this->trainingService->normalizeText($text);

        foreach ($mapaMusculos as $keyword => $info) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/u', $text)) {
                $resultado['musculos'] = array_merge($resultado['musculos'], $info['db']);
                if (!in_array($info['label'], $resultado['nombres_musculos'])) {
                    $resultado['nombres_musculos'][] = $info['label'];
                }
            }
        }

        // Vocabulario NLP entrenado manualmente para músculos / modismos
        foreach ($dinamicos['musculo'] as $normKw => $info) {
            if (preg_match('/\b' . preg_quote($normKw, '/') . 's?\b/u', $normText) || str_contains($normText, $normKw)) {
                $resultado['musculos'] = array_merge($resultado['musculos'], $info['db']);
                if (!in_array($info['label'], $resultado['nombres_musculos'])) {
                    $resultado['nombres_musculos'][] = $info['label'];
                }
            }
        }
        $resultado['musculos'] = array_values(array_unique($resultado['musculos']));

        // 3. Entorno (reglas base + vocabulario entrenado)
        if (str_contains($text, 'gimnasio') || str_contains($text, 'gym') || str_contains($text, 'maquinas') || str_contains($text, 'máquinas')) {
            $resultado['entorno'] = 'gimnasio';
        } elseif (str_contains($text, 'mancuerna') || str_contains($text, 'pesas') || str_contains($text, 'bandas') || str_contains($text, 'ligas')) {
            $resultado['entorno'] = 'casa_basico';
        } elseif (str_contains($text, 'sin equipo') || str_contains($text, 'nada') || str_contains($text, 'mi casa') || str_contains($text, 'cuarto') || str_contains($text, 'piso')) {
            $resultado['entorno'] = 'casa_sin_equipo';
        }

        foreach ($dinamicos['entorno'] as $normKw => $targetEntorno) {
            if (str_contains($normText, $normKw)) {
                $resultado['entorno'] = $targetEntorno;
                break;
            }
        }

        // 4. Lesiones (reglas base + vocabulario entrenado)
        // Evitar falsos positivos cuando el usuario pide entrenar un músculo (ej: "rutina de hombros" o "entrenar espalda")
        $tieneContextoLesion = (bool) preg_match('/(dolor|duele|lesi[oó]n|molestia|lastim|problemas?|sensib|da[ñn]|herid|operad|cirug|ci[aá]tica|manguito|lumbar|esguince|tendinitis|menisco|fractura|incomodidad|evitar|cuidar)/iu', $text);
        $esPeticionEntrenoZona = function (string $zonaPattern) use ($text): bool {
            return (bool) preg_match('/(rutina|entrenar|ejercicios?|trabajar|hacer|marcar|fortalecer|crecer|enfocad[ao]|para)\s+(?:de\s+|para\s+|mis?\s+|los?\s+|las?\s+|el\s+|la\s+)?(?:\w+\s+y\s+)?' . $zonaPattern . '/iu', $text);
        };

        if (str_contains($text, 'rodilla')) {
            $resultado['lesion'] = 'rodilla';
        } elseif (str_contains($text, 'lumbar') || str_contains($text, 'ciática') || str_contains($text, 'ciatica') || str_contains($text, 'espalda baja')) {
            $resultado['lesion'] = 'espalda_baja';
        } elseif (str_contains($text, 'espalda') && ($tieneContextoLesion || !$esPeticionEntrenoZona('espalda'))) {
            $resultado['lesion'] = 'espalda_baja';
        } elseif (str_contains($text, 'manguito')) {
            $resultado['lesion'] = 'hombro';
        } elseif (str_contains($text, 'hombro') && ($tieneContextoLesion || !$esPeticionEntrenoZona('hombros?'))) {
            $resultado['lesion'] = 'hombro';
        } elseif (str_contains($text, 'muñeca') || str_contains($text, 'muneca') || (str_contains($text, 'mano') && $tieneContextoLesion)) {
            $resultado['lesion'] = 'muñeca';
        } elseif (str_contains($text, 'ninguna') || str_contains($text, 'sin lesion') || str_contains($text, 'sin lesión') || str_contains($text, 'al 100') || str_contains($text, 'todo bien') || str_contains($text, 'sano')) {
            $resultado['lesion'] = 'ninguna';
        }

        foreach ($dinamicos['lesion'] as $normKw => $targetLesion) {
            if (str_contains($normText, $normKw)) {
                $resultado['lesion'] = $targetLesion;
                break;
            }
        }

        // 5. Objetivos
        if (str_contains($text, 'fuerza') || str_contains($text, 'musculo') || str_contains($text, 'músculo')) {
            $resultado['objetivo'] = 'fuerza';
        } elseif (str_contains($text, 'peso') || str_contains($text, 'grasa') || str_contains($text, 'adelgazar')) {
            $resultado['objetivo'] = 'perdida_grasa';
        } elseif (str_contains($text, 'resistencia') || str_contains($text, 'condicion') || str_contains($text, 'condición') || str_contains($text, 'cardio')) {
            $resultado['objetivo'] = 'resistencia';
        }

        return $resultado;
    }

    /**
     * Detecta emergencias clínicas agudas (REG-SEG-05 + alarmas entrenadas manualmente).
     */
    protected function detectarEmergenciaMedicaDetalle(string $text): array
    {
        $palabrasAlarma = [
            'dolor en el pecho', 'dolor de pecho', 'presión en el pecho',
            'me mareo', 'mareo fuerte', 'desmayo', 'falta de aire',
            'no puedo respirar', 'dolor agudo', 'taquicardia', 'dolor punzante'
        ];

        $normText = $this->trainingService->normalizeText($text);

        // Revisar primero alarmas entrenadas manualmente (pueden traer respuesta personalizada)
        $dinamicos = $this->trainingService->getDynamicSynonyms();
        foreach ($dinamicos['alarma'] as $alarmaItem) {
            if ($alarmaItem['keyword'] !== '' && str_contains($normText, $alarmaItem['keyword'])) {
                return [
                    'detected' => true,
                    'keyword' => $alarmaItem['raw'],
                    'custom_response' => $alarmaItem['response_text'] ?: null,
                    'source' => 'manual',
                ];
            }
        }

        foreach ($palabrasAlarma as $alarma) {
            if (str_contains($text, $alarma) || str_contains($normText, $this->trainingService->normalizeText($alarma))) {
                return [
                    'detected' => true,
                    'keyword' => $alarma,
                    'custom_response' => null,
                    'source' => 'base',
                ];
            }
        }

        return ['detected' => false, 'keyword' => null, 'custom_response' => null, 'source' => null];
    }

    protected function detectarEmergenciaMedica(string $text): bool
    {
        return $this->detectarEmergenciaMedicaDetalle($text)['detected'];
    }

    protected function esConsultaHistorial(string $text): bool
    {
        return str_contains($text, 'historial') || str_contains($text, 'mis entrenamientos') || str_contains($text, 'sesiones previas');
    }

    protected function esConsultaExplicabilidad(string $text): bool
    {
        return str_contains($text, 'por que') || str_contains($text, 'por qué') || str_contains($text, 'explicame') || str_contains($text, 'explícame') || str_contains($text, 'como se calculo') || str_contains($text, 'cómo se calculó');
    }

    protected function esConfirmacionFinalizacion(string $text): bool
    {
        return str_contains($text, 'termine') || str_contains($text, 'terminé') || str_contains($text, 'complete') || str_contains($text, 'completé') || str_contains($text, 'ya acabe') || str_contains($text, 'ya acabé') || str_contains($text, 'finalice') || str_contains($text, 'finalicé');
    }

    protected function esSolicitudRutina(string $text, array $entidades = []): bool
    {
        if (!empty($entidades['musculos'])) {
            return true;
        }

        return str_contains($text, 'rutina') 
            || str_contains($text, 'entrenar') 
            || str_contains($text, 'ejercicios') 
            || str_contains($text, 'empezar') 
            || str_contains($text, 'dame una rutina')
            || str_contains($text, 'mision')
            || str_contains($text, 'misión')
            || str_contains($text, 'emprender')
            || str_contains($text, 'pecho')
            || str_contains($text, 'bicep')
            || str_contains($text, 'bícep')
            || str_contains($text, 'tricep')
            || str_contains($text, 'trícep')
            || str_contains($text, 'brazo')
            || str_contains($text, 'espalda')
            || str_contains($text, 'pierna')
            || str_contains($text, 'gluteo')
            || str_contains($text, 'glúteo')
            || str_contains($text, 'abdomen')
            || str_contains($text, 'core');
    }

    /**
     * Simula en vivo cómo el chatbot interpreta un mensaje (NLP, Q&A, Guardrails y RAG)
     * sin alterar el perfil del usuario ni crear sesiones en la base de datos.
     */
    public function inspeccionarMensaje(User $user, string $mensaje): array
    {
        $mensajeLimpio = mb_strtolower(trim($mensaje));
        $entidades = $this->extraerEntidadesNLP($mensajeLimpio);
        $emergencia = $this->detectarEmergenciaMedicaDetalle($mensajeLimpio);
        $qaMatch = $this->trainingService->findQaMatch($mensaje);

        $intencion = 'entrevista_conversacional';
        $vistaPrevia = '';
        $fuente = 'motor_base';

        if ($emergencia['detected']) {
            $intencion = 'alerta_emergencia';
            $fuente = $emergencia['source'] === 'manual' ? 'alarma_entrenada' : 'guardrail_base';
            $vistaPrevia = $emergencia['custom_response']
                ?: "🚨 ¡ALTO, AVENTURERO! Alerta médica disparada por '{$emergencia['keyword']}'. Se bloquea la prescripción de ejercicios.";
        } elseif ($qaMatch !== null) {
            $intencion = 'respuesta_entrenada';
            $fuente = 'qa_manual';
            $vistaPrevia = $this->limpiarTextoMarkdown($qaMatch['response_text']);
        } elseif ($this->esConsultaHistorial($mensajeLimpio)) {
            $intencion = 'consulta_historial';
            $vistaPrevia = 'Consultará y mostrará la crónica de entrenamientos previos del usuario.';
        } elseif ($this->esConsultaExplicabilidad($mensajeLimpio)) {
            $intencion = 'consulta_explicabilidad';
            $vistaPrevia = 'Explicará paso a paso cómo y por qué se construyó la última rutina.';
        } elseif ($this->esConfirmacionFinalizacion($mensajeLimpio)) {
            $intencion = 'confirmacion_finalizacion';
            $vistaPrevia = 'Cerrará la rutina en progreso y otorgará XP y avance de misiones RPG.';
        } elseif ($this->esSolicitudRutina($mensajeLimpio, $entidades) || $this->tieneParametrosSuficientes($user)) {
            $intencion = 'generar_rutina_rag';
            $tiempo = $entidades['tiempo'] ?? $user->available_time_minutes ?? 15;
            $entorno = $entidades['entorno'] ?? $user->environment ?? 'casa_sin_equipo';
            $lesion = $entidades['lesion'] ?? $user->injury ?? 'ninguna';
            $enfoque = !empty($entidades['nombres_musculos']) ? implode(' y ', $entidades['nombres_musculos']) : 'Cuerpo completo / Balanceado';
            $vistaPrevia = "Generará una rutina RAG de {$tiempo} min enfocada en {$enfoque} (Entorno: {$entorno}, Protección articular: {$lesion}).";
        } else {
            $vistaPrevia = 'Continuará la entrevista conversacional adaptativa solicitando los parámetros faltantes.';
        }

        // Revisar si existen ejercicios personalizados RAG que coincidan con el músculo detectado
        $ejerciciosCustomMatch = [];
        $customExercises = $this->trainingService->getCustomExercises();
        if (!empty($entidades['musculos'])) {
            foreach ($customExercises as $ex) {
                if (in_array(strtolower($ex['musculo_principal']), $entidades['musculos'], true)) {
                    $ejerciciosCustomMatch[] = $ex['nombre'];
                }
            }
        }

        return [
            'mensaje' => $mensaje,
            'intencion_detectada' => $intencion,
            'fuente' => $fuente,
            'entidades_nlp' => $entidades,
            'qa_coincidencia' => $qaMatch,
            'emergencia' => $emergencia,
            'ejercicios_rag_personalizados_aplicables' => $ejerciciosCustomMatch,
            'vista_previa_respuesta' => $vistaPrevia,
        ];
    }

    protected function tieneParametrosSuficientes(User $user): bool
    {
        return !empty($user->available_time_minutes) && !empty($user->environment) && !empty($user->injury);
    }

    /**
     * Genera la vista conversacional del historial cronológico (HU #12).
     */
    protected function generarRespuestaHistorial(User $user): array
    {
        $sesiones = $user->workoutSessions()->latest()->take(5)->get();

        if ($sesiones->isEmpty()) {
            return [
                'emisor' => 'hyperion',
                'tipo' => 'historial',
                'texto' => "📜 REGISTRO DE BATALLAS DE HYPERION\n\nAún no has registrado ninguna sesión de entrenamiento previa. ¡Es el momento perfecto para iniciar tu Rutina de Calibración Inicial! Escribe 'Quiero mi rutina' para comenzar.",
                'rutina' => null,
                'perfil_actualizado' => false,
            ];
        }

        $texto = "📜 CRÓNICA DE TUS ENTRENAMIENTOS (Últimas sesiones):\n\n";
        foreach ($sesiones as $s) {
            $fecha = $s->created_at->format('d/m/Y H:i');
            $estadoIcon = ($s->status === 'completada') ? '✅' : '⏳';
            $texto .= "{$estadoIcon} {$s->title}\n";
            $texto .= "   - Fecha: {$fecha}\n";
            $texto .= "   - Duración: {$s->total_duration_minutes} min | Estado: " . ucfirst($s->status) . "\n";
            $texto .= "   - Dificultad reportada: " . ($s->difficulty_rating ? ucfirst(str_replace('_', ' ', $s->difficulty_rating)) : 'Sin calificar') . "\n";
            if ($s->xp_earned > 0) {
                $texto .= "   - Recompensa ganada: +{$s->xp_earned} XP\n";
            }
            $texto .= "\n";
        }

        $texto .= "Tu historial servirá como base para calcular tu próxima sobrecarga progresiva asistida.";

        return [
            'emisor' => 'hyperion',
            'tipo' => 'historial',
            'texto' => $texto,
            'rutina' => null,
            'perfil_actualizado' => false,
        ];
    }

    /**
     * Despliega la explicabilidad en lenguaje natural claro y empático (HU #15).
     */
    protected function generarRespuestaExplicabilidad(User $user): array
    {
        $ultimaSesion = $user->workoutSessions()->latest()->first();

        if (!$ultimaSesion || empty($ultimaSesion->explainability_data)) {
            return [
                'emisor' => 'hyperion',
                'tipo' => 'explicabilidad',
                'texto' => "Aún no has generado una rutina activa. Cuando solicites tu rutina, podrás consultar aquí la justificación detallada de cada criterio de selección.",
                'rutina' => null,
                'perfil_actualizado' => false,
            ];
        }

        $data = $ultimaSesion->explainability_data;
        $criterios = $data['criterios_origen'] ?? [];

        $texto = "🔍 CÓMO Y POR QUÉ SE DISEÑÓ TU RUTINA (EXPLICABILIDAD DE HYPERION)\n\n";
        $texto .= "{$data['explicacion_texto']}\n\n";

        $texto .= "🧩 Origen de las decisiones tomadas:\n";
        $texto .= "1. Tus respuestas en la entrevista: {$criterios['entrevista']}\n";
        $texto .= "2. Reglas de seguridad biomecánica: {$criterios['biometricos_seguridad']}\n";
        $texto .= "3. Protección contra lesiones: {$criterios['lesiones']}\n";
        $texto .= "4. Memoria de progreso: {$criterios['historial']}\n\n";
        $texto .= "Cada ejercicio fue filtrado por el motor clínico para asegurar máxima efectividad sin poner en riesgo tu salud.";

        return [
            'emisor' => 'hyperion',
            'tipo' => 'explicabilidad',
            'texto' => $texto,
            'rutina' => null,
            'perfil_actualizado' => false,
        ];
    }

    /**
     * Procesa la finalización de la rutina y otorga XP / Rangos RPG (HU #14).
     */
    protected function generarRespuestaFinalizacion(User $user, string $mensaje): array
    {
        $sesionPendiente = $user->workoutSessions()
            ->where('status', 'en_progreso')
            ->latest()
            ->first();

        if (!$sesionPendiente) {
            return [
                'emisor' => 'hyperion',
                'tipo' => 'info',
                'texto' => "No tienes ninguna rutina activa en progreso en este momento. Escribe 'Quiero entrenar' para iniciar una nueva misión.",
                'rutina' => null,
                'perfil_actualizado' => false,
            ];
        }

        // Evaluar esfuerzo percibido a partir del mensaje si lo incluyó
        $dificultad = 'adecuada';
        $fatiga = false;
        if (str_contains($mensaje, 'facil') || str_contains($mensaje, 'fácil')) {
            $dificultad = 'muy_facil';
        } elseif (str_contains($mensaje, 'pesada') || str_contains($mensaje, 'cansado') || str_contains($mensaje, 'agotado') || str_contains($mensaje, 'dificil') || str_contains($mensaje, 'difícil')) {
            $dificultad = 'muy_pesada';
            $fatiga = true;
        }

        $xpGanada = $this->rpgService->calcularXpRutina([
            'duracion_minutos' => $sesionPendiente->total_duration_minutes,
            'ejercicios' => $sesionPendiente->exercises_data ?? [],
        ], $sesionPendiente->progressive_overload_applied);

        $resultadoRpg = $this->rpgService->procesarFinRutina(
            $user,
            $sesionPendiente,
            $xpGanada,
            $dificultad,
            $fatiga
        );

        return [
            'emisor' => 'hyperion',
            'tipo' => 'finalizacion_rpg',
            'texto' => $resultadoRpg['mensaje_narrativo'],
            'rpg_data' => $resultadoRpg,
            'rutina' => null,
            'perfil_actualizado' => true,
        ];
    }
}
