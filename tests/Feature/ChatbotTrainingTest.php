<?php

namespace Tests\Feature;

use App\Models\ChatbotTrainingEntry;
use App\Models\User;
use App\Models\WorkoutSession;
use App\Services\DecisionRulesEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotTrainingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Aventurero Trainer',
            'email' => 'trainer@hyperionplay.com',
            'password' => bcrypt('password'),
            'age' => 26,
            'weight_kg' => 75.0,
            'height_cm' => 176.0,
            'body_fat_pct' => 16.0,
            'environment' => 'casa_basico',
            'available_time_minutes' => 15,
            'injury' => 'ninguna',
            'fitness_goal' => 'fuerza',
            'archetype' => 'titan_agil',
            'current_level' => 1,
            'current_xp' => 50,
            'rpg_title' => 'Recluta de Hierro',
        ]);
    }

    public function test_entrenar_respuesta_qa_y_responder_en_chat(): void
    {
        $create = $this->postJson('/api/training/entries', [
            'type' => 'qa',
            'trigger_text' => 'que comer antes de entrenar, pre entreno',
            'response_text' => 'Come avena con plátano 60 minutos antes de tu sesión.',
            'metadata' => ['match_mode' => 'contains'],
        ]);

        $create->assertStatus(201);
        $entryId = $create->json('entry.id');

        // Probar con acentos y signos de interrogación
        $chat = $this->postJson('/api/chat/message', [
            'user_id' => $this->user->id,
            'message' => '¿Qué comer antes de entrenar?',
        ]);

        $chat->assertStatus(200);
        $chat->assertJsonPath('respuesta.tipo', 'respuesta_entrenada');
        $chat->assertJsonPath('respuesta.entrenamiento_aplicado.tipo', 'qa');
        $this->assertStringContainsString('avena con plátano', $chat->json('respuesta.texto'));

        // Pausar entrada -> ya no debe disparar la respuesta entrenada
        $toggle = $this->patchJson("/api/training/entries/{$entryId}/toggle");
        $toggle->assertStatus(200);
        $toggle->assertJsonPath('entry.is_active', false);

        $chatAfterPause = $this->postJson('/api/chat/message', [
            'user_id' => $this->user->id,
            'message' => '¿Qué comer antes de entrenar?',
        ]);
        $this->assertNotEquals('respuesta_entrenada', $chatAfterPause->json('respuesta.tipo'));

        // Eliminar entrada
        $del = $this->deleteJson("/api/training/entries/{$entryId}");
        $del->assertStatus(200);
        $this->assertDatabaseMissing('chatbot_training_entries', ['id' => $entryId]);
    }

    public function test_entrenar_modismo_musculo_nlp_y_generar_rutina(): void
    {
        $this->postJson('/api/training/entries', [
            'type' => 'synonym',
            'trigger_text' => 'conejo',
            'metadata' => [
                'category' => 'musculo',
                'target' => 'biceps',
                'label' => 'Bíceps',
            ],
        ])->assertStatus(201);

        $chat = $this->postJson('/api/chat/message', [
            'user_id' => $this->user->id,
            'message' => 'Quiero trabajar mis conejos 25 min',
        ]);

        $chat->assertStatus(200);
        $chat->assertJsonPath('respuesta.tipo', 'rutina_generada');
        $this->assertEquals(25, $chat->json('respuesta.rutina.duracion_minutos'));
        $this->assertStringContainsString('Bíceps', $chat->json('respuesta.rutina.titulo'));

        $musculos = array_map(fn($e) => strtolower($e['musculo']), $chat->json('respuesta.rutina.ejercicios'));
        $this->assertContains('biceps', $musculos);
    }

    public function test_entrenar_sinonimo_lesion_y_alerta_emergencia_medica(): void
    {
        // Entrenar sinónimo de lesión
        $this->postJson('/api/training/entries', [
            'type' => 'synonym',
            'trigger_text' => 'menisco',
            'metadata' => [
                'category' => 'lesion',
                'target' => 'rodilla',
                'label' => 'Rodilla',
            ],
        ])->assertStatus(201);

        // Entrenar alerta médica personalizada
        $this->postJson('/api/training/entries', [
            'type' => 'synonym',
            'trigger_text' => 'vision borrosa',
            'response_text' => 'ALERTA PERSONALIZADA: Detén el entrenamiento por visión borrosa y consulta a un médico.',
            'metadata' => [
                'category' => 'alarma',
                'target' => 'emergencia',
                'label' => 'Alerta Médica',
            ],
        ])->assertStatus(201);

        // Verificar detección de lesión por sinónimo
        $respLesion = $this->postJson('/api/chat/message', [
            'user_id' => $this->user->id,
            'message' => 'Me duele el menisco, dame una rutina segura',
        ]);
        $respLesion->assertStatus(200);
        $this->assertEquals('rodilla', $this->user->fresh()->injury);
        $this->assertNotEmpty($respLesion->json('respuesta.rutina.aviso_medico'));

        // Verificar alerta de emergencia médica personalizada (con tilde)
        $respAlarma = $this->postJson('/api/chat/message', [
            'user_id' => $this->user->id,
            'message' => 'Siento visión borrosa mientras entreno',
        ]);
        $respAlarma->assertStatus(200);
        $respAlarma->assertJsonPath('respuesta.tipo', 'alerta_emergencia');
        $this->assertStringContainsString('ALERTA PERSONALIZADA', $respAlarma->json('respuesta.texto'));
    }

    public function test_entrenar_ejercicio_rag_personalizado_y_exclusiones_clinicas(): void
    {
        $this->postJson('/api/training/entries', [
            'type' => 'exercise',
            'trigger_text' => 'Press Militar Espartano Personalizado',
            'response_text' => 'Codos ligeramente adelantados y abdomen firme sin arquear la espalda.',
            'metadata' => [
                'musculo_principal' => 'delts',
                'entorno' => 'casa_basico',
                'nivel_impacto' => 'bajo',
                'zonas_riesgo_evitar' => ['hombro'],
                'duracion_estimada_seg' => 50,
                'xp_base' => 25,
            ],
        ])->assertStatus(201);

        // Usuario sano en casa_basico pide rutina de hombros -> debe aparecer su ejercicio entrenado con técnica propia
        $respSano = $this->postJson('/api/chat/message', [
            'user_id' => $this->user->id,
            'message' => 'Dame una rutina de hombros de 20 minutos',
        ]);
        $respSano->assertStatus(200);

        $ejerciciosSano = $respSano->json('respuesta.rutina.ejercicios');
        $encontrado = collect($ejerciciosSano)->firstWhere('nombre', 'Press Militar Espartano Personalizado');
        $this->assertNotNull($encontrado, 'El ejercicio RAG entrenado manualmente debe aparecer en la rutina.');
        $this->assertStringContainsString('Codos ligeramente adelantados', $encontrado['indicacion_postural']);

        // Usuario con lesión de hombro -> el motor clínico debe excluir el ejercicio entrenado
        $this->user->update(['injury' => 'hombro']);
        $respLesionHombro = $this->postJson('/api/chat/message', [
            'user_id' => $this->user->id,
            'message' => 'Dame una rutina de hombros de 20 minutos',
        ]);
        $ejerciciosLesion = $respLesionHombro->json('respuesta.rutina.ejercicios');
        $encontradoLesion = collect($ejerciciosLesion)->firstWhere('nombre', 'Press Militar Espartano Personalizado');
        $this->assertNull($encontradoLesion, 'El motor clínico debe excluir el ejercicio entrenado cuando hay lesión en hombro.');
    }

    public function test_reglas_clinicas_simulador_y_exportar_importar_json(): void
    {
        // 1. Actualizar reglas clínicas
        $rulesResp = $this->postJson('/api/training/rules', [
            'edad_limite_impacto_bajo' => 40,
            'grasa_limite_impacto_bajo' => 22.0,
            'articulaciones_monitoreadas' => 'rodilla, espalda_baja, hombro, muñeca, tobillo',
        ]);
        $rulesResp->assertStatus(200);

        $rulesEngine = app(DecisionRulesEngineService::class);
        $evalEdad = $rulesEngine->evaluarSeguridadEjercicio(
            ['nivel_impacto' => 'medio', 'entorno' => 'casa_sin_equipo', 'zonas_riesgo_evitar' => []],
            ['edad' => 42, 'body_fat' => 18.0, 'lesion' => 'ninguna', 'entorno' => 'casa_sin_equipo']
        );
        $this->assertFalse($evalEdad['es_seguro'], 'Debe restringir impacto medio al bajar umbral de edad a 40.');

        $evalTobillo = $rulesEngine->evaluarSeguridadEjercicio(
            ['nivel_impacto' => 'bajo', 'entorno' => 'casa_sin_equipo', 'zonas_riesgo_evitar' => ['tobillo']],
            ['edad' => 25, 'body_fat' => 15.0, 'lesion' => 'tobillo', 'entorno' => 'casa_sin_equipo']
        );
        $this->assertFalse($evalTobillo['es_seguro'], 'Debe excluir ejercicios con riesgo en tobillo tras añadir la articulación.');

        // 2. Probar simulador en vivo sin crear sesiones
        $sessionsBefore = WorkoutSession::count();
        $simResp = $this->postJson('/api/training/simulate', [
            'user_id' => $this->user->id,
            'message' => 'Quiero entrenar pecho 30 min',
        ]);
        $simResp->assertStatus(200);
        $simResp->assertJsonPath('diagnostico.intencion_detectada', 'generar_rutina_rag');
        $simResp->assertJsonPath('diagnostico.entidades_nlp.tiempo', 30);
        $this->assertEquals($sessionsBefore, WorkoutSession::count(), 'El simulador no debe crear sesiones reales.');

        // 3. Exportar e importar JSON
        $this->postJson('/api/training/entries', [
            'type' => 'qa',
            'trigger_text' => 'hidratacion',
            'response_text' => 'Bebe al menos 2 litros de agua al día.',
        ])->assertStatus(201);

        $export = $this->getJson('/api/training/export');
        $export->assertStatus(200);
        $entries = $export->json('entries');
        $this->assertGreaterThanOrEqual(2, count($entries));

        ChatbotTrainingEntry::query()->delete();
        $this->assertEquals(0, ChatbotTrainingEntry::count());

        $import = $this->postJson('/api/training/import', [
            'entries' => $entries,
        ]);
        $import->assertStatus(200);
        $this->assertGreaterThanOrEqual(2, ChatbotTrainingEntry::count());

        // 4. Confirmar que el panel aparece en la vista principal
        $view = $this->get('/hyperion');
        $view->assertStatus(200);
        $view->assertSee('Entrenar Chatbot');
        $view->assertSee('Laboratorio de Entrenamiento Manual');
    }
}
