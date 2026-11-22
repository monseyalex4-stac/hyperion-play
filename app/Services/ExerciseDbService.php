<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExerciseDbService
{
    protected RagCatalogService $ragCatalog;
    protected string $apiKey;
    protected string $apiHost;
    protected string $baseUrl;

    public function __construct(RagCatalogService $ragCatalog)
    {
        $this->ragCatalog = $ragCatalog;
        $this->apiKey = config('services.exercisedb.key', env('EXERCISE_DB_KEY', ''));
        $this->apiHost = config('services.exercisedb.host', env('EXERCISE_DB_HOST', 'exercisedb.p.rapidapi.com'));
        $this->baseUrl = 'https://' . $this->apiHost;
    }

    /**
     * Consulta ejercicios filtrados por grupo muscular y equipo, cruzándolos con el esquema RAG.
     * Con fallback automático al catálogo RAG local si la API falla o no tiene credenciales.
     */
    public function getExercises(?string $bodyPart = null, ?string $equipment = null): array
    {
        // Si no hay API key configurada, ir directamente a fallback con aviso en log
        if (empty($this->apiKey)) {
            Log::info('[ExerciseDB Service] No se configuró API Key de ExerciseDB. Utilizando catálogo RAG local indexado.');
            return $this->getFallbackExercises($bodyPart, $equipment);
        }

        try {
            $url = $this->baseUrl . '/exercises';
            if ($bodyPart) {
                $url .= '/bodyPart/' . urlencode(strtolower($bodyPart));
            } elseif ($equipment) {
                $url .= '/equipment/' . urlencode(strtolower($equipment));
            }

            $response = Http::timeout(4)
                ->retry(2, 400)
                ->withHeaders([
                    'X-RapidAPI-Key' => $this->apiKey,
                    'X-RapidAPI-Host' => $this->apiHost,
                ])
                ->get($url);

            if ($response->successful()) {
                $rawData = $response->json();
                if (is_array($rawData) && count($rawData) > 0) {
                    return $this->normalizeAndIndexExercises($rawData);
                }
            }

            Log::warning("[ExerciseDB Service] Respuesta no exitosa de la API (Status: {$response->status()}). Activando fallback RAG local.");
            return $this->getFallbackExercises($bodyPart, $equipment);

        } catch (\Throwable $e) {
            // Manejo de errores de red, timeouts o rate limits
            Log::warning('[ExerciseDB API Fallback] Error conectando a ExerciseDB: ' . $e->getMessage() . '. Respaldo con ejercicios RAG locales.');
            return $this->getFallbackExercises($bodyPart, $equipment);
        }
    }

    /**
     * Respaldo (Fallback) utilizando el catálogo RAG local pre-etiquetado.
     */
    protected function getFallbackExercises(?string $bodyPart = null, ?string $equipment = null): array
    {
        $criteria = [];
        if ($bodyPart) {
            $criteria['musculo_principal'] = $bodyPart;
        }
        if ($equipment) {
            $criteria['equipo'] = $equipment;
        }

        $filtered = $this->ragCatalog->filter($criteria);
        if (empty($filtered)) {
            // Si el filtro fue demasiado estricto, retornar ejercicios generales del catálogo
            return array_slice($this->ragCatalog->getCatalog(), 0, 30);
        }

        return $filtered;
    }

    /**
     * Normaliza los ejercicios provenientes de ExerciseDB al esquema JSON local RAG.
     */
    public function normalizeAndIndexExercises(array $apiExercises): array
    {
        $normalized = [];

        foreach ($apiExercises as $item) {
            $target = strtolower($item['target'] ?? $item['bodyPart'] ?? 'general');
            $equip = strtolower($item['equipment'] ?? 'body weight');

            // Determinar entorno compatible
            $entorno = 'gimnasio';
            if ($equip === 'body weight' || $equip === 'none') {
                $entorno = 'casa_sin_equipo';
            } elseif (in_array($equip, ['dumbbell', 'band', 'resistance band', 'kettlebell'])) {
                $entorno = 'casa_basico';
            }

            // Estimación de zonas de riesgo según músculo objetivo
            $zonasRiesgo = [];
            if (in_array($target, ['quads', 'calves', 'hamstrings', 'glutes'])) {
                $zonasRiesgo[] = 'rodilla';
            }
            if (in_array($target, ['lower back', 'spine', 'waist'])) {
                $zonasRiesgo[] = 'espalda_baja';
            }
            if (in_array($target, ['delts', 'shoulders', 'chest', 'lats'])) {
                $zonasRiesgo[] = 'hombro';
            }
            if (in_array($target, ['forearms', 'biceps', 'triceps'])) {
                $zonasRiesgo[] = 'muñeca';
            }

            // Estimación de nivel de impacto
            $nivelImpacto = 'bajo';
            $nameLower = strtolower($item['name'] ?? '');
            if (str_contains($nameLower, 'jump') || str_contains($nameLower, 'burpee') || str_contains($nameLower, 'hop') || str_contains($nameLower, 'plyo')) {
                $nivelImpacto = 'alto';
            } elseif (str_contains($nameLower, 'clean') || str_contains($nameLower, 'snatch') || str_contains($nameLower, 'swing')) {
                $nivelImpacto = 'medio';
            }

            $normalized[] = [
                'id' => 'EXDB-' . ($item['id'] ?? uniqid()),
                'nombre' => ucwords($item['name'] ?? 'Ejercicio'),
                'descripcion' => 'Ejercicio obtenido vía ExerciseDB enfocado en ' . $target . '.',
                'musculo_principal' => $target,
                'musculos_secundarios' => $item['secondaryMuscles'] ?? [],
                'equipo' => $equip,
                'nivel' => 'Principiante',
                'tipo' => $item['bodyPart'] ?? 'Fuerza',
                'instrucciones' => $item['instructions'] ?? ['Realiza el movimiento con técnica controlada.'],
                'fuente' => 'ExerciseDB_API',
                'gif_url' => $item['gifUrl'] ?? '',
                'media_id' => $item['id'] ?? '',
                'name' => $item['name'] ?? '',
                'nivel_impacto' => $nivelImpacto,
                'nivel_experiencia' => 'principiante',
                'entorno' => $entorno,
                'zonas_riesgo_evitar' => $zonasRiesgo,
                'tipo_objetivo' => 'resistencia',
                'duracion_estimada_seg' => 45,
                'xp_base' => 12,
            ];
        }

        return $normalized;
    }
}
