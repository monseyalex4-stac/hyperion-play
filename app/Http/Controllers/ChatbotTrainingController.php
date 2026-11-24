<?php

namespace App\Http\Controllers;

use App\Models\ChatbotTrainingEntry;
use App\Models\User;
use App\Services\ChatbotTrainingService;
use App\Services\ConversationalChatbotService;
use App\Services\RagCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatbotTrainingController extends Controller
{
    protected ChatbotTrainingService $trainingService;
    protected ConversationalChatbotService $chatbot;
    protected RagCatalogService $ragCatalog;

    public function __construct(
        ChatbotTrainingService $trainingService,
        ConversationalChatbotService $chatbot,
        RagCatalogService $ragCatalog
    ) {
        $this->trainingService = $trainingService;
        $this->chatbot = $chatbot;
        $this->ragCatalog = $ragCatalog;
    }

    /**
     * Lista todo el conocimiento entrenado manualmente, reglas clínicas activas y estadísticas.
     */
    public function index(): JsonResponse
    {
        $qa = $this->trainingService->getEntries('qa');
        $synonyms = $this->trainingService->getEntries('synonym');
        $exercises = $this->trainingService->getEntries('exercise');
        $rules = $this->trainingService->getClinicalRules();

        return response()->json([
            'status' => 'success',
            'entries' => [
                'qa' => $qa->values(),
                'synonym' => $synonyms->values(),
                'exercise' => $exercises->values(),
            ],
            'clinical_rules' => [
                'edad_limite_impacto_bajo' => $rules['reglas_seguridad']['edad_limite_impacto_bajo'] ?? 45,
                'grasa_limite_impacto_bajo' => $rules['reglas_seguridad']['grasa_limite_impacto_bajo'] ?? 25.0,
                'articulaciones_monitoreadas' => $rules['reglas_lesiones']['articulaciones_monitoreadas'] ?? ['rodilla', 'espalda_baja', 'hombro', 'muñeca'],
            ],
            'stats' => [
                'total_qa' => $qa->where('is_active', true)->count(),
                'total_synonyms' => $synonyms->where('is_active', true)->count(),
                'total_custom_exercises' => $exercises->where('is_active', true)->count(),
                'total_catalog_exercises' => count($this->ragCatalog->getCatalog()),
            ],
        ]);
    }

    /**
     * Registra una nueva entrada de entrenamiento manual (Q&A, Vocabulario NLP o Ejercicio RAG).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:qa,synonym,exercise',
            'trigger_text' => 'required|string|min:1|max:255',
            'response_text' => 'nullable|string|max:3000',
            'metadata' => 'nullable|array',
        ]);

        $type = $validated['type'];
        $metadata = $validated['metadata'] ?? [];

        if ($type === 'qa') {
            $request->validate([
                'response_text' => 'required|string|min:2|max:3000',
            ]);
            $mode = $metadata['match_mode'] ?? 'contains';
            $metadata['match_mode'] = in_array($mode, ['contains', 'exact'], true) ? $mode : 'contains';
        } elseif ($type === 'synonym') {
            $category = $metadata['category'] ?? 'musculo';
            if (!in_array($category, ['musculo', 'lesion', 'entorno', 'alarma'], true)) {
                return response()->json([
                    'status' => 'error',
                    'mensaje' => 'Categoría de vocabulario NLP no válida.',
                ], 422);
            }
            if ($category !== 'alarma' && empty(trim((string) ($metadata['target'] ?? '')))) {
                return response()->json([
                    'status' => 'error',
                    'mensaje' => 'Debes seleccionar el destino o categoría a la que apunta este sinónimo.',
                ], 422);
            }
            $metadata['category'] = $category;
            if ($category === 'musculo') {
                $metadata['db_targets'] = $this->trainingService->resolveMuscleDbTargets((string) $metadata['target']);
            }
        } elseif ($type === 'exercise') {
            $entorno = $metadata['entorno'] ?? 'casa_sin_equipo';
            $impacto = $metadata['nivel_impacto'] ?? 'bajo';
            if (!in_array($entorno, ['casa_sin_equipo', 'casa_basico', 'gimnasio'], true)) {
                $entorno = 'casa_sin_equipo';
            }
            if (!in_array($impacto, ['bajo', 'medio', 'alto'], true)) {
                $impacto = 'bajo';
            }
            $metadata = [
                'musculo_principal' => strtolower(trim((string) ($metadata['musculo_principal'] ?? 'pectorals'))),
                'equipo' => trim((string) ($metadata['equipo'] ?? ($entorno === 'casa_sin_equipo' ? 'body weight' : ($entorno === 'casa_basico' ? 'dumbbell' : 'barbell')))),
                'entorno' => $entorno,
                'nivel_impacto' => $impacto,
                'nivel_experiencia' => strtolower(trim((string) ($metadata['nivel_experiencia'] ?? 'principiante'))),
                'zonas_riesgo_evitar' => array_values(array_unique(array_filter(array_map('strtolower', (array) ($metadata['zonas_riesgo_evitar'] ?? []))))),
                'tipo_objetivo' => strtolower(trim((string) ($metadata['tipo_objetivo'] ?? 'fuerza'))),
                'duracion_estimada_seg' => max(15, min(300, (int) ($metadata['duracion_estimada_seg'] ?? 45))),
                'xp_base' => max(5, min(100, (int) ($metadata['xp_base'] ?? 15))),
            ];
        }

        $entry = ChatbotTrainingEntry::create([
            'type' => $type,
            'trigger_text' => trim($validated['trigger_text']),
            'response_text' => isset($validated['response_text']) ? trim((string) $validated['response_text']) : null,
            'metadata' => $metadata,
            'is_active' => true,
            'created_by' => Auth::id() ?? session('active_user_id'),
        ]);

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Entrenamiento guardado y activado en tiempo real.',
            'entry' => $entry,
        ], 201);
    }

    /**
     * Activa o desactiva una entrada de entrenamiento sin eliminarla.
     */
    public function toggle(int $id): JsonResponse
    {
        $entry = ChatbotTrainingEntry::findOrFail($id);
        $entry->is_active = !$entry->is_active;
        $entry->save();

        return response()->json([
            'status' => 'success',
            'mensaje' => $entry->is_active ? 'Entrenamiento activado.' : 'Entrenamiento pausado.',
            'entry' => $entry,
        ]);
    }

    /**
     * Elimina una entrada de entrenamiento manual.
     */
    public function destroy(int $id): JsonResponse
    {
        $entry = ChatbotTrainingEntry::findOrFail($id);
        $entry->delete();

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Entrada de entrenamiento eliminada.',
        ]);
    }

    /**
     * Guarda reglas clínicas de seguridad personalizadas (umbrales y articulaciones).
     */
    public function saveRules(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'edad_limite_impacto_bajo' => 'required|integer|min:18|max:90',
            'grasa_limite_impacto_bajo' => 'required|numeric|min:10|max:55',
            'articulaciones_monitoreadas' => 'required',
        ]);

        $arts = $validated['articulaciones_monitoreadas'];
        if (is_string($arts)) {
            $arts = array_filter(array_map('trim', explode(',', $arts)));
        }

        $entry = $this->trainingService->saveClinicalRules([
            'edad_limite_impacto_bajo' => $validated['edad_limite_impacto_bajo'],
            'grasa_limite_impacto_bajo' => $validated['grasa_limite_impacto_bajo'],
            'articulaciones_monitoreadas' => (array) $arts,
        ], Auth::id() ?? session('active_user_id'));

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Reglas clínicas actualizadas y aplicadas en vivo.',
            'clinical_rules' => $entry->metadata,
        ]);
    }

    /**
     * Simula en vivo cómo el chatbot entiende una frase de prueba (sin modificar la sesión real).
     */
    public function simulate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'user_id' => 'nullable|integer',
        ]);

        $userId = $validated['user_id'] ?? session('active_user_id') ?? Auth::id();
        $user = $userId ? User::find($userId) : User::first();

        if (!$user) {
            $user = new User([
                'name' => 'Aventurero Simulado',
                'age' => 25,
                'body_fat_pct' => 18.0,
                'environment' => 'casa_sin_equipo',
                'available_time_minutes' => 15,
                'injury' => 'ninguna',
                'fitness_goal' => 'resistencia',
            ]);
        }

        $diagnostico = $this->chatbot->inspeccionarMensaje($user, $validated['message']);

        return response()->json([
            'status' => 'success',
            'diagnostico' => $diagnostico,
        ]);
    }

    /**
     * Exporta todo el conocimiento manual en JSON.
     */
    public function export(): JsonResponse
    {
        return response()->json($this->trainingService->exportKnowledge());
    }

    /**
     * Importa conocimiento manual desde JSON.
     */
    public function import(Request $request): JsonResponse
    {
        $entries = $request->input('entries');

        if (!is_array($entries) && $request->filled('payload')) {
            $decoded = json_decode((string) $request->input('payload'), true);
            $entries = $decoded['entries'] ?? (is_array($decoded) ? $decoded : null);
        }

        if (!is_array($entries) || empty($entries)) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'El JSON no contiene un arreglo válido de entradas ("entries").',
            ], 422);
        }

        $count = $this->trainingService->importKnowledge($entries, Auth::id() ?? session('active_user_id'));

        return response()->json([
            'status' => 'success',
            'mensaje' => "Se importaron {$count} registros de entrenamiento correctamente.",
            'imported_count' => $count,
        ]);
    }
}
