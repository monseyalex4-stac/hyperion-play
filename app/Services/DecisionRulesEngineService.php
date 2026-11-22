<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class DecisionRulesEngineService
{
    protected array $rules;
    protected string $rulesPath;

    public function __construct()
    {
        $this->rulesPath = storage_path('app/DATASET/reglas.json');
        if (File::exists($this->rulesPath)) {
            $this->rules = json_decode(File::get($this->rulesPath), true) ?: [];
        } else {
            $this->rules = [
                'reglas_seguridad' => [
                    'edad_limite_impacto_bajo' => 45,
                    'grasa_limite_impacto_bajo' => 25.0,
                    'accion_edad_grasa' => 'restringir_a_bajo_impacto'
                ],
                'reglas_lesiones' => [
                    'articulaciones_monitoreadas' => ['rodilla', 'espalda_baja', 'hombro', 'muñeca'],
                    'accion_lesion' => 'exclusion_estricta'
                ],
                'reglas_entorno' => [
                    'compatibilidad' => [
                        'casa_sin_equipo' => ['casa_sin_equipo'],
                        'casa_basico' => ['casa_sin_equipo', 'casa_basico'],
                        'gimnasio' => ['casa_sin_equipo', 'casa_basico', 'gimnasio']
                    ]
                ],
                'reglas_tiempo' => [
                    'tiempo_default_seg' => 900,
                    'tolerancia_margen_seg' => 60,
                    'series_promedio' => 3
                ],
                'gamificacion_xp' => [
                    'base_completitud' => 50,
                    'bonus_por_serie' => 5
                ]
            ];
        }
    }

    /**
     * Evalúa si un ejercicio específico es seguro y compatible para el perfil dado.
     * Retorna ['es_seguro' => bool, 'motivo' => ?string]
     */
    public function evaluarSeguridadEjercicio(array $ejercicio, array $perfil): array
    {
        $edad = (int) ($perfil['edad'] ?? $perfil['Age'] ?? 25);
        $bodyFat = (float) ($perfil['body_fat'] ?? $perfil['BodyFat_pct'] ?? $perfil['BodyFat'] ?? 18.0);
        $lesion = strtolower(trim((string) ($perfil['lesion'] ?? 'ninguna')));
        $entorno = strtolower(trim((string) ($perfil['entorno'] ?? 'casa_sin_equipo')));

        // 1. Regla de Seguridad por Edad o Grasa Corporal (HU #4 & HU #9)
        $umbralEdad = $this->rules['reglas_seguridad']['edad_limite_impacto_bajo'] ?? 45;
        $umbralGrasa = $this->rules['reglas_seguridad']['grasa_limite_impacto_bajo'] ?? 25.0;

        if ($edad > $umbralEdad || $bodyFat > $umbralGrasa) {
            $impacto = strtolower($ejercicio['nivel_impacto'] ?? 'medio');
            if ($impacto !== 'bajo') {
                return [
                    'es_seguro' => false,
                    'motivo' => "Impacto '{$impacto}' no permitido para edad > {$umbralEdad} o grasa > {$umbralGrasa}%. Requiere estrictamente impacto bajo para proteger articulaciones."
                ];
            }
        }

        // 2. Regla de Exclusión Estricta por Lesión Articular (HU #4 & HU #11)
        if ($lesion !== '' && !in_array($lesion, ['ninguna', 'ninguno', 'sano', 'sin_lesion'])) {
            $zonasRiesgo = array_map('strtolower', $ejercicio['zonas_riesgo_evitar'] ?? []);
            $articulacionesMonitoreadas = $this->rules['reglas_lesiones']['articulaciones_monitoreadas'] ?? ['rodilla', 'espalda_baja', 'hombro', 'muñeca'];

            foreach ($articulacionesMonitoreadas as $art) {
                if (str_contains($lesion, $art) && in_array($art, $zonasRiesgo)) {
                    return [
                        'es_seguro' => false,
                        'motivo' => "Riesgo articular para '{$art}': ejercicio contraindicado por presencia en zonas_riesgo_evitar."
                    ];
                }
            }
        }

        // 3. Regla de Compatibilidad de Entorno / Equipamiento
        $compatibles = $this->rules['reglas_entorno']['compatibilidad'][$entorno] ?? ['casa_sin_equipo'];
        $entornoEjercicio = strtolower($ejercicio['entorno'] ?? 'casa_sin_equipo');

        if (!in_array($entornoEjercicio, $compatibles)) {
            return [
                'es_seguro' => false,
                'motivo' => "Entorno del ejercicio '{$entornoEjercicio}' incompatible con equipamiento disponible '{$entorno}'."
            ];
        }

        return ['es_seguro' => true, 'motivo' => null];
    }

    /**
     * Filtra una lista de ejercicios, retornando los seguros y los descartados con motivo.
     */
    public function filtrarCatalogo(array $catalogo, array $perfil): array
    {
        $seguros = [];
        $descartados = [];

        foreach ($catalogo as $ex) {
            $evaluacion = $this->evaluarSeguridadEjercicio($ex, $perfil);
            if ($evaluacion['es_seguro']) {
                $seguros[] = $ex;
            } else {
                $descartados[] = [
                    'id' => $ex['id'] ?? null,
                    'nombre' => $ex['nombre'] ?? null,
                    'motivo' => $evaluacion['motivo'],
                ];
            }
        }

        return [$seguros, $descartados];
    }

    /**
     * Ejecuta la validación automatizada de 20 perfiles (HU #5) y genera el reporte.
     */
    public function ejecutarValidacionMetricas(): array
    {
        $datasetPath = storage_path('app/DATASET/dataset_limpio.csv');
        $ragPath = storage_path('app/DATASET/ejercicios_rag_etiquetados.json');
        $reportPath = storage_path('app/DATASET/reporte_metricas.txt');

        if (!File::exists($datasetPath) || !File::exists($ragPath)) {
            return [
                'status' => 'error',
                'mensaje' => 'No se encontraron dataset_limpio.csv o ejercicios_rag_etiquetados.json'
            ];
        }

        // Cargar perfiles
        $lines = file($datasetPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $header = str_getcsv(array_shift($lines));
        $rows = array_map('str_getcsv', $lines);

        $perfiles = [];
        foreach ($rows as $r) {
            if (count($r) === count($header)) {
                $perfiles[] = array_combine($header, $r);
            }
        }

        $catalogo = json_decode(File::get($ragPath), true) ?: [];

        // Muestra de 20 perfiles con variabilidad
        $step = max(1, (int) floor(count($perfiles) / 20));
        $muestra20 = [];
        for ($i = 0; $i < count($perfiles) && count($muestra20) < 20; $i += $step) {
            $muestra20[] = $perfiles[$i];
        }

        $lesionesSimuladas = ['ninguna', 'rodilla', 'espalda_baja', 'hombro', 'muñeca'];
        $entornosSimulados = ['casa_sin_equipo', 'casa_basico', 'gimnasio'];

        $tablaResultados = [];
        $totalVp = 0;
        $totalFp = 0;
        $totalVn = 0;
        $totalFn = 0;

        foreach ($muestra20 as $idx => $row) {
            $usrId = sprintf('USR-%02d', $idx + 1);
            $edad = (int) ($row['Age'] ?? 30);
            $grasa = (float) ($row['BodyFat_pct'] ?? 15.0);
            $lesion = $lesionesSimuladas[$idx % count($lesionesSimuladas)];
            $entorno = $entornosSimulados[$idx % count($entornosSimulados)];

            $perfil = [
                'edad' => $edad,
                'body_fat' => $grasa,
                'lesion' => $lesion,
                'entorno' => $entorno,
            ];

            $vpUsr = 0;
            $fpUsr = 0;
            $vnUsr = 0;
            $fnUsr = 0;

            $debeRestringirImpacto = ($grasa > 25.0 || $edad > 45);

            foreach ($catalogo as $ex) {
                $impacto = $ex['nivel_impacto'] ?? 'medio';
                $riesgos = $ex['zonas_riesgo_evitar'] ?? [];

                // Ground Truth
                $esSeguro = true;
                if ($debeRestringirImpacto && $impacto === 'alto') {
                    $esSeguro = false;
                }
                if ($lesion !== 'ninguna' && in_array($lesion, $riesgos)) {
                    $esSeguro = false;
                }

                // Inferencia del motor de reglas
                $eval = $this->evaluarSeguridadEjercicio($ex, $perfil);
                $recomendado = $eval['es_seguro'];

                if ($recomendado && $esSeguro) {
                    $vpUsr++;
                } elseif ($recomendado && !$esSeguro) {
                    $fpUsr++;
                } elseif (!$recomendado && !$esSeguro) {
                    $vnUsr++;
                } elseif (!$recomendado && $esSeguro) {
                    $fnUsr++;
                }
            }

            $totalVp += $vpUsr;
            $totalFp += $fpUsr;
            $totalVn += $vnUsr;
            $totalFn += $fnUsr;

            $precisionUsr = ($vpUsr + $fpUsr) > 0 ? round(($vpUsr / ($vpUsr + $fpUsr)) * 100, 2) : 100.0;

            $tablaResultados[] = [
                'id' => $usrId,
                'edad' => $edad,
                'grasa' => $grasa,
                'lesion' => $lesion,
                'entorno' => $entorno,
                'seguros' => $vpUsr,
                'vp' => $vpUsr,
                'fp' => $fpUsr,
                'precision' => $precisionUsr
            ];
        }

        $precisionGlobal = ($totalVp + $totalFp) > 0 ? round(($totalVp / ($totalVp + $totalFp)) * 100, 2) : 100.0;
        $recallGlobal = ($totalVp + $totalFn) > 0 ? round(($totalVp / ($totalVp + $totalFn)) * 100, 2) : 100.0;
        $especificidad = ($totalVn + $totalFp) > 0 ? round(($totalVn / ($totalVn + $totalFp)) * 100, 2) : 100.0;
        $f1Score = ($precisionGlobal + $recallGlobal) > 0 ? round((2 * $precisionGlobal * $recallGlobal) / ($precisionGlobal + $recallGlobal), 2) : 100.0;

        return [
            'status' => 'success',
            'perfiles_evaluados' => count($muestra20),
            'total_ejercicios' => count($catalogo),
            'total_inferencias' => count($muestra20) * count($catalogo),
            'tabla' => $tablaResultados,
            'matriz' => [
                'VP' => $totalVp,
                'FP' => $totalFp,
                'VN' => $totalVn,
                'FN' => $totalFn,
                'Precision' => $precisionGlobal,
                'Recall' => $recallGlobal,
                'Especificidad' => $especificidad,
                'F1_Score' => $f1Score
            ],
            'reporte_path' => $reportPath
        ];
    }
}
