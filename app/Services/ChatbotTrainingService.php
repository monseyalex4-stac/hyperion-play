<?php

namespace App\Services;

use App\Models\ChatbotTrainingEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ChatbotTrainingService
{
    /**
     * Verifica de forma segura si la tabla de entrenamiento existe.
     */
    protected function hasTable(): bool
    {
        try {
            return Schema::hasTable('chatbot_training_entries');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Normaliza texto en español (minúsculas, sin tildes ni signos de puntuación)
     * para matching robusto de frases y palabras clave.
     */
    public function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $accents = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        ];
        $text = strtr($text, $accents);
        $text = preg_replace('/[¿?¡!.,;:()"\']+/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Obtiene todas las entradas (opcionalmente filtradas por tipo y estado).
     */
    public function getEntries(?string $type = null, bool $onlyActive = false): Collection
    {
        if (!$this->hasTable()) {
            return collect();
        }

        $query = ChatbotTrainingEntry::query()->latest('id');
        if ($type !== null) {
            $query->where('type', $type);
        }
        if ($onlyActive) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Busca si el mensaje del usuario coincide con una respuesta Q&A entrenada manualmente.
     */
    public function findQaMatch(string $rawMessage): ?array
    {
        $entries = $this->getEntries('qa', true);
        if ($entries->isEmpty()) {
            return null;
        }

        $normalizedMessage = $this->normalizeText($rawMessage);
        if ($normalizedMessage === '') {
            return null;
        }

        $bestMatch = null;
        $bestScore = 0;

        foreach ($entries as $entry) {
            $meta = $entry->metadata ?? [];
            $matchMode = $meta['match_mode'] ?? 'contains';
            $triggers = array_filter(array_map('trim', preg_split('/[,|\n]+/', (string) $entry->trigger_text)));

            foreach ($triggers as $trigger) {
                $normTrigger = $this->normalizeText($trigger);
                if ($normTrigger === '') {
                    continue;
                }

                $score = 0;
                if ($normalizedMessage === $normTrigger) {
                    $score = 100 + mb_strlen($normTrigger);
                } elseif ($matchMode !== 'exact' && str_contains($normalizedMessage, $normTrigger)) {
                    $score = 50 + mb_strlen($normTrigger);
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = [
                        'id' => $entry->id,
                        'trigger_matched' => $trigger,
                        'response_text' => (string) $entry->response_text,
                        'score' => $score,
                    ];
                }
            }
        }

        return $bestMatch;
    }

    /**
     * Devuelve sinónimos NLP entrenados manualmente agrupados por categoría:
     * musculo, lesion, entorno, alarma.
     */
    public function getDynamicSynonyms(): array
    {
        $result = [
            'musculo' => [],
            'lesion'  => [],
            'entorno' => [],
            'alarma'  => [],
        ];

        $entries = $this->getEntries('synonym', true);
        foreach ($entries as $entry) {
            $meta = $entry->metadata ?? [];
            $category = $meta['category'] ?? 'musculo';
            $target = trim((string) ($meta['target'] ?? ''));
            $label = trim((string) ($meta['label'] ?? ucfirst($target)));

            $keywords = array_filter(array_map('trim', preg_split('/[,|\n]+/', (string) $entry->trigger_text)));

            foreach ($keywords as $kw) {
                $normKw = $this->normalizeText($kw);
                if ($normKw === '') {
                    continue;
                }

                if ($category === 'musculo' && $target !== '') {
                    $dbTargets = is_array($meta['db_targets'] ?? null)
                        ? $meta['db_targets']
                        : $this->resolveMuscleDbTargets($target);
                    $result['musculo'][$normKw] = [
                        'db' => $dbTargets,
                        'label' => $label ?: ucfirst($target),
                        'raw' => $kw,
                    ];
                } elseif ($category === 'lesion' && $target !== '') {
                    $result['lesion'][$normKw] = $target;
                } elseif ($category === 'entorno' && $target !== '') {
                    $result['entorno'][$normKw] = $target;
                } elseif ($category === 'alarma') {
                    $result['alarma'][] = [
                        'keyword' => $normKw,
                        'raw' => $kw,
                        'response_text' => trim((string) ($entry->response_text ?? '')),
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Resuelve las claves de músculo en la base RAG para una selección de músculo.
     */
    public function resolveMuscleDbTargets(string $target): array
    {
        $map = [
            'pecho'       => ['pectorals', 'chest'],
            'pectorals'   => ['pectorals', 'chest'],
            'chest'       => ['pectorals', 'chest'],
            'biceps'      => ['biceps'],
            'triceps'     => ['triceps'],
            'forearms'    => ['forearms'],
            'brazos'      => ['biceps', 'triceps', 'forearms'],
            'espalda'     => ['lats', 'upper back', 'middle back', 'lower back', 'traps', 'spine'],
            'lats'        => ['lats', 'upper back', 'middle back'],
            'hombros'     => ['delts', 'shoulders'],
            'delts'       => ['delts', 'shoulders'],
            'shoulders'   => ['delts', 'shoulders'],
            'piernas'     => ['quads', 'quadriceps', 'hamstrings', 'calves', 'adductors', 'abductors'],
            'quads'       => ['quads', 'quadriceps'],
            'hamstrings'  => ['hamstrings'],
            'calves'      => ['calves'],
            'glutes'      => ['glutes'],
            'gluteos'     => ['glutes'],
            'abs'         => ['abs', 'abdominals'],
            'abdomen'     => ['abs', 'abdominals'],
            'cardio'      => ['cardiovascular system'],
        ];

        $key = strtolower(trim($target));
        return $map[$key] ?? [$key];
    }

    /**
     * Obtiene los ejercicios personalizados entrenados manualmente en formato esquema RAG.
     */
    public function getCustomExercises(): array
    {
        $entries = $this->getEntries('exercise', true);
        $exercises = [];

        foreach ($entries as $entry) {
            $meta = $entry->metadata ?? [];
            $musculoPrincipal = strtolower((string) ($meta['musculo_principal'] ?? 'abs'));
            $entorno = (string) ($meta['entorno'] ?? 'casa_sin_equipo');
            $equipo = (string) ($meta['equipo'] ?? ($entorno === 'casa_sin_equipo' ? 'body weight' : ($entorno === 'casa_basico' ? 'dumbbell' : 'barbell')));
            $zonasRiesgo = array_values(array_filter(array_map('strtolower', (array) ($meta['zonas_riesgo_evitar'] ?? []))));
            $postura = trim((string) ($entry->response_text ?? ''));

            $exercises[] = [
                'id' => 'CUSTOM-' . $entry->id,
                'training_entry_id' => $entry->id,
                'nombre' => $entry->trigger_text,
                'name' => $entry->trigger_text,
                'descripcion' => $postura ?: "Ejercicio personalizado entrenado manualmente para {$musculoPrincipal}.",
                'musculo_principal' => $musculoPrincipal,
                'musculos_secundarios' => (array) ($meta['musculos_secundarios'] ?? []),
                'equipo' => $equipo,
                'nivel' => ucfirst((string) ($meta['nivel_experiencia'] ?? 'principiante')),
                'tipo' => (string) ($meta['tipo_objetivo'] ?? 'fuerza'),
                'instrucciones' => $postura ? [$postura] : ['Ejecuta el movimiento con técnica controlada y respiración constante.'],
                'indicacion_postural' => $postura ?: null,
                'fuente' => 'Entrenamiento_Manual_RAG',
                'gif_url' => $meta['gif_url'] ?? '',
                'nivel_impacto' => strtolower((string) ($meta['nivel_impacto'] ?? 'bajo')),
                'nivel_experiencia' => strtolower((string) ($meta['nivel_experiencia'] ?? 'principiante')),
                'entorno' => $entorno,
                'zonas_riesgo_evitar' => $zonasRiesgo,
                'tipo_objetivo' => strtolower((string) ($meta['tipo_objetivo'] ?? 'fuerza')),
                'duracion_estimada_seg' => max(15, min(300, (int) ($meta['duracion_estimada_seg'] ?? 45))),
                'xp_base' => max(5, min(100, (int) ($meta['xp_base'] ?? 15))),
            ];
        }

        return $exercises;
    }

    /**
     * Obtiene las reglas clínicas efectivas (combinando reglas.json + overrides entrenados en BD).
     */
    public function getClinicalRules(): array
    {
        $rulesPath = storage_path('app/DATASET/reglas.json');
        $baseRules = File::exists($rulesPath)
            ? (json_decode(File::get($rulesPath), true) ?: [])
            : [];

        $defaults = [
            'reglas_seguridad' => [
                'edad_limite_impacto_bajo' => 45,
                'grasa_limite_impacto_bajo' => 25.0,
                'accion_edad_grasa' => 'restringir_a_bajo_impacto',
            ],
            'reglas_lesiones' => [
                'articulaciones_monitoreadas' => ['rodilla', 'espalda_baja', 'hombro', 'muñeca'],
                'accion_lesion' => 'exclusion_estricta',
            ],
            'reglas_entorno' => [
                'compatibilidad' => [
                    'casa_sin_equipo' => ['casa_sin_equipo'],
                    'casa_basico' => ['casa_sin_equipo', 'casa_basico'],
                    'gimnasio' => ['casa_sin_equipo', 'casa_basico', 'gimnasio'],
                ],
            ],
            'reglas_tiempo' => [
                'tiempo_default_seg' => 900,
                'tolerancia_margen_seg' => 60,
                'series_promedio' => 3,
            ],
            'gamificacion_xp' => [
                'base_completitud' => 50,
                'bonus_por_serie' => 5,
            ],
        ];

        $rules = array_replace_recursive($defaults, $baseRules);

        if ($this->hasTable()) {
            $override = ChatbotTrainingEntry::where('type', 'rule')
                ->where('trigger_text', 'clinical_rules_config')
                ->where('is_active', true)
                ->latest('id')
                ->first();

            if ($override && is_array($override->metadata)) {
                $meta = $override->metadata;
                if (isset($meta['edad_limite_impacto_bajo'])) {
                    $rules['reglas_seguridad']['edad_limite_impacto_bajo'] = (int) $meta['edad_limite_impacto_bajo'];
                }
                if (isset($meta['grasa_limite_impacto_bajo'])) {
                    $rules['reglas_seguridad']['grasa_limite_impacto_bajo'] = (float) $meta['grasa_limite_impacto_bajo'];
                }
                if (isset($meta['articulaciones_monitoreadas']) && is_array($meta['articulaciones_monitoreadas'])) {
                    $cleanArts = array_values(array_unique(array_filter(array_map(
                        fn($a) => strtolower(trim((string) $a)),
                        $meta['articulaciones_monitoreadas']
                    ))));
                    if (!empty($cleanArts)) {
                        $rules['reglas_lesiones']['articulaciones_monitoreadas'] = $cleanArts;
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Guarda la configuración manual de reglas clínicas en la tabla de entrenamiento.
     */
    public function saveClinicalRules(array $config, ?int $userId = null): ChatbotTrainingEntry
    {
        $articulaciones = array_values(array_unique(array_filter(array_map(
            fn($a) => strtolower(trim((string) $a)),
            (array) ($config['articulaciones_monitoreadas'] ?? ['rodilla', 'espalda_baja', 'hombro', 'muñeca'])
        ))));

        $payload = [
            'edad_limite_impacto_bajo' => max(18, min(90, (int) ($config['edad_limite_impacto_bajo'] ?? 45))),
            'grasa_limite_impacto_bajo' => max(10.0, min(55.0, (float) ($config['grasa_limite_impacto_bajo'] ?? 25.0))),
            'articulaciones_monitoreadas' => !empty($articulaciones) ? $articulaciones : ['rodilla', 'espalda_baja', 'hombro', 'muñeca'],
        ];

        $entry = ChatbotTrainingEntry::updateOrCreate(
            ['type' => 'rule', 'trigger_text' => 'clinical_rules_config'],
            [
                'response_text' => 'Configuración manual de umbrales clínicos de seguridad',
                'metadata' => $payload,
                'is_active' => true,
                'created_by' => $userId,
            ]
        );

        return $entry;
    }

    /**
     * Exporta todo el conocimiento entrenado manualmente a un arreglo serializable a JSON.
     */
    public function exportKnowledge(): array
    {
        $entries = $this->getEntries();

        return [
            'schema_version' => '1.0',
            'app' => 'Hyperion Play',
            'exported_at' => now()->toIso8601String(),
            'total_entries' => $entries->count(),
            'entries' => $entries->map(fn($e) => [
                'type' => $e->type,
                'trigger_text' => $e->trigger_text,
                'response_text' => $e->response_text,
                'metadata' => $e->metadata,
                'is_active' => $e->is_active,
            ])->values()->all(),
        ];
    }

    /**
     * Importa entradas de entrenamiento desde un payload JSON.
     */
    public function importKnowledge(array $entries, ?int $userId = null): int
    {
        $imported = 0;
        $validTypes = ['qa', 'synonym', 'exercise', 'rule'];

        foreach ($entries as $item) {
            if (!is_array($item)) {
                continue;
            }
            $type = $item['type'] ?? '';
            $trigger = trim((string) ($item['trigger_text'] ?? ''));

            if (!in_array($type, $validTypes, true) || $trigger === '') {
                continue;
            }

            if ($type === 'rule' && $trigger === 'clinical_rules_config') {
                $this->saveClinicalRules((array) ($item['metadata'] ?? []), $userId);
                $imported++;
                continue;
            }

            ChatbotTrainingEntry::updateOrCreate(
                ['type' => $type, 'trigger_text' => $trigger],
                [
                    'response_text' => $item['response_text'] ?? null,
                    'metadata' => is_array($item['metadata'] ?? null) ? $item['metadata'] : [],
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'created_by' => $userId,
                ]
            );
            $imported++;
        }

        return $imported;
    }
}
