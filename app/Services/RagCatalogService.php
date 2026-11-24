<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class RagCatalogService
{
    protected ?array $baseCatalog = null;
    protected string $catalogPath;
    protected ChatbotTrainingService $trainingService;

    public function __construct(?ChatbotTrainingService $trainingService = null)
    {
        $this->catalogPath = storage_path('app/DATASET/ejercicios_rag_etiquetados.json');
        $this->trainingService = $trainingService ?? new ChatbotTrainingService();
    }

    /**
     * Limpia la caché en memoria del catálogo base.
     */
    public function clearCache(): void
    {
        $this->baseCatalog = null;
    }

    /**
     * Obtiene el catálogo completo de ejercicios RAG, priorizando los
     * ejercicios personalizados entrenados manualmente.
     */
    public function getCatalog(): array
    {
        if ($this->baseCatalog === null) {
            if (File::exists($this->catalogPath)) {
                $json = File::get($this->catalogPath);
                $this->baseCatalog = json_decode($json, true) ?: [];
            } else {
                $this->baseCatalog = [];
            }
        }

        $customExercises = $this->trainingService->getCustomExercises();
        if (!empty($customExercises)) {
            return array_merge($customExercises, $this->baseCatalog);
        }

        return $this->baseCatalog;
    }

    /**
     * Busca un ejercicio por su ID.
     */
    public function findById(string $id): ?array
    {
        foreach ($this->getCatalog() as $exercise) {
            if (($exercise['id'] ?? '') === $id) {
                return $exercise;
            }
        }
        return null;
    }

    /**
     * Filtra ejercicios según criterios de búsqueda.
     */
    public function filter(array $criteria): array
    {
        $exercises = $this->getCatalog();

        return array_values(array_filter($exercises, function ($ex) use ($criteria) {
            if (isset($criteria['musculo_principal']) && $criteria['musculo_principal'] !== '') {
                if (strtolower($ex['musculo_principal'] ?? '') !== strtolower($criteria['musculo_principal'])) {
                    return false;
                }
            }

            if (isset($criteria['nivel_impacto']) && $criteria['nivel_impacto'] !== '') {
                if (strtolower($ex['nivel_impacto'] ?? '') !== strtolower($criteria['nivel_impacto'])) {
                    return false;
                }
            }

            if (isset($criteria['entorno']) && $criteria['entorno'] !== '') {
                if (($ex['entorno'] ?? '') !== $criteria['entorno']) {
                    return false;
                }
            }

            if (isset($criteria['equipo']) && $criteria['equipo'] !== '') {
                if (stripos($ex['equipo'] ?? '', $criteria['equipo']) === false) {
                    return false;
                }
            }

            return true;
        }));
    }
}
