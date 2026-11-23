<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkoutSession;
use App\Services\ConversationalChatbotService;
use App\Services\DecisionRulesEngineService;
use App\Services\ExerciseDbService;
use App\Services\GamificationRpgService;
use App\Services\RoutineGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HyperionPlayTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::first() ?? User::create([
            'name' => 'Santiago Test',
            'email' => 'test@hyperion.com',
            'password' => bcrypt('password'),
            'age' => 24,
            'weight_kg' => 74.0,
            'height_cm' => 175.0,
            'body_fat_pct' => 16.5,
            'environment' => 'casa_sin_equipo',
            'available_time_minutes' => 15,
            'injury' => 'ninguna',
            'fitness_goal' => 'resistencia',
            'archetype' => 'titan_agil',
            'current_level' => 1,
            'current_xp' => 45,
            'rpg_title' => 'Recluta de Hierro',
        ]);
    }

    /**
     * HU #5: Validación de Reglas Clínicas con 20 perfiles y Falsos Positivos = 0.
     */
    public function test_hu05_validacion_metricas_falsos_positivos_cero(): void
    {
        $rulesEngine = app(DecisionRulesEngineService::class);
        $resultado = $rulesEngine->ejecutarValidacionMetricas();

        $this->assertEquals('success', $resultado['status']);
        $this->assertCount(20, $resultado['tabla']);
        $this->assertEquals(0, $resultado['matriz']['FP'], 'La tasa de Falsos Positivos (ejercicios de riesgo colados) DEBE ser estrictamente 0.');
        $this->assertGreaterThan(0, $resultado['matriz']['VP']);
        $this->assertEquals(100.0, $resultado['matriz']['Precision']);
    }

    /**
     * HU #6: Integración ExerciseDB con fallback automático a RAG local.
     */
    public function test_hu06_exercisedb_fallback_automatico(): void
    {
        $exerciseDb = app(ExerciseDbService::class);
        $ejercicios = $exerciseDb->getExercises('abs', 'body weight');

        $this->assertIsArray($ejercicios);
        $this->assertNotEmpty($ejercicios);
        $this->assertArrayHasKey('nivel_impacto', $ejercicios[0]);
        $this->assertArrayHasKey('zonas_riesgo_evitar', $ejercicios[0]);
        $this->assertArrayHasKey('entorno', $ejercicios[0]);
    }

    /**
     * HU #7: Entrevista Conversacional con asignación de valores por defecto conservadores.
     */
    public function test_hu07_entrevista_conversacional_defaults_conservadores(): void
    {
        $chatbot = app(ConversationalChatbotService::class);
        
        $this->user->update([
            'available_time_minutes' => 0,
            'environment' => '',
            'injury' => '',
        ]);

        $respuesta = $chatbot->procesarMensaje($this->user, 'Hola, quiero entrenar');
        $this->assertNotNull($respuesta['texto']);

        $respuesta2 = $chatbot->procesarMensaje($this->user, 'Tengo 15 min en casa sin equipo y sin ninguna lesión');
        $this->assertEquals('rutina_generada', $respuesta2['tipo']);
        $this->assertEquals(15, $this->user->fresh()->available_time_minutes);
        $this->assertEquals('casa_sin_equipo', $this->user->fresh()->environment);
    }

    /**
     * HU #8: Generación de rutina personalizada básica.
     */
    public function test_hu08_generacion_rutina_personalizada(): void
    {
        $generator = app(RoutineGeneratorService::class);
        $rutina = $generator->generarRutina($this->user);

        $this->assertNotEmpty($rutina['ejercicios']);
        $this->assertArrayHasKey('series', $rutina['ejercicios'][0]);
        $this->assertArrayHasKey('repeticiones', $rutina['ejercicios'][0]);
        $this->assertArrayHasKey('descanso_segundos', $rutina['ejercicios'][0]);
    }

    /**
     * HU #9: Filtro de bajo impacto por edad (>45) o grasa (>25%) e indicaciones posturales.
     */
    public function test_hu09_filtro_bajo_impacto_por_regla_seguridad(): void
    {
        $generator = app(RoutineGeneratorService::class);
        $this->user->update(['age' => 50, 'body_fat_pct' => 28.0]);

        $rutina = $generator->generarRutina($this->user);

        foreach ($rutina['ejercicios'] as $ej) {
            $this->assertEquals('bajo', strtolower($ej['nivel_impacto']), 'Usuario con sobrepeso o >45 años solo puede recibir ejercicios de bajo impacto.');
            $this->assertNotEmpty($ej['indicacion_postural']);
        }
    }

    /**
     * HU #10: Generación de rutinas rápidas de 15 minutos o micro-rutinas proporcionales.
     */
    public function test_hu10_rutina_rapida_15_minutos(): void
    {
        $generator = app(RoutineGeneratorService::class);
        $this->user->update(['available_time_minutes' => 15]);

        $rutina = $generator->generarRutina($this->user);

        $this->assertLessThanOrEqual(16, $rutina['duracion_minutos']);
        $this->assertGreaterThan(0, $rutina['xp_estimada']);
    }

    /**
     * HU #11: Exclusión estricta de lesión y aviso médico obligatorio.
     */
    public function test_hu11_rutina_segura_lesion_rodilla_con_aviso_medico(): void
    {
        $generator = app(RoutineGeneratorService::class);
        $this->user->update(['injury' => 'rodilla']);

        $rutina = $generator->generarRutina($this->user);

        $this->assertNotNull($rutina['aviso_medico']);
        $this->assertStringContainsString('AVISO MÉDICO', $rutina['aviso_medico']);

        foreach ($rutina['ejercicios'] as $ej) {
            $this->assertNotContains('rodilla', $ej['zonas_riesgo_evitar'] ?? []);
            $this->assertEquals('bajo', strtolower($ej['nivel_impacto']));
        }
    }

    /**
     * HU #12: Historial en BD y rutina de calibración inicial para nuevo usuario.
     */
    public function test_hu12_historial_y_rutina_calibracion_inicial(): void
    {
        $generator = app(RoutineGeneratorService::class);
        $this->user->workoutSessions()->delete();

        $rutina = $generator->generarRutina($this->user);
        $this->assertTrue($rutina['es_calibracion']);
        $this->assertStringContainsString('Calibración Inicial', $rutina['titulo']);
        $this->assertDatabaseHas('workout_sessions', ['id' => $rutina['sesion_id']]);
    }

    /**
     * HU #13: Sobrecarga progresiva asistida (<= 10%) si la previa fue fácil, o reducción si hubo fatiga.
     */
    public function test_hu13_sobrecarga_progresiva_asistida(): void
    {
        $generator = app(RoutineGeneratorService::class);

        WorkoutSession::create([
            'user_id' => $this->user->id,
            'title' => 'Sesión 1',
            'total_duration_minutes' => 15,
            'status' => 'completada',
            'difficulty_rating' => 'muy_facil',
            'fatigue_reported' => false,
            'exercises_data' => [],
            'completed_at' => now(),
        ]);

        $rutina = $generator->generarRutina($this->user);
        $this->assertTrue($rutina['explicabilidad']['sobrecarga_progresiva_aplicada']);
        $this->assertNotNull($rutina['aviso_sobrecarga']);
    }

    /**
     * HU #14: Gamificación RPG (Open Game Mechanics, XP y subida de nivel).
     */
    public function test_hu14_gamificacion_rpg_xp_y_niveles(): void
    {
        $rpg = app(GamificationRpgService::class);
        
        $sesion = WorkoutSession::create([
            'user_id' => $this->user->id,
            'title' => 'Sesión Prueba',
            'total_duration_minutes' => 15,
            'status' => 'en_progreso',
            'exercises_data' => [
                ['series' => 3, 'nivel' => 'principiante'],
                ['series' => 3, 'nivel' => 'intermedio'],
            ],
        ]);

        $this->user->update(['current_xp' => 90, 'current_level' => 1]);

        $resumen = $rpg->procesarFinRutina($this->user, $sesion, 65, 'adecuada', false);

        $this->user->refresh();
        $this->assertEquals(155, $this->user->current_xp);
        $this->assertEquals(2, $this->user->current_level);
        $this->assertEquals('Guerrero de Cobre', $this->user->rpg_title);
        $this->assertTrue($resumen['subio_nivel']);
        $this->assertStringContainsString('BATALLA COMPLETADA', $resumen['mensaje_narrativo']);
    }

    /**
     * HU #15: Explicabilidad sin etiquetas técnicas ni código crudo.
     */
    public function test_hu15_explicabilidad_rutina_lenguaje_natural(): void
    {
        $generator = app(RoutineGeneratorService::class);
        $rutina = $generator->generarRutina($this->user);

        $explicacion = $rutina['explicabilidad']['explicacion_texto'];

        $this->assertNotEmpty($explicacion);
        $this->assertStringNotContainsString('nivel_impacto: bajo', $explicacion);
        $this->assertStringNotContainsString('{', $explicacion);
        $this->assertStringNotContainsString('}', $explicacion);
    }

    /**
     * Test de Rutas HTTP y Compatibilidad con DatasetController.
     */
    public function test_http_rutas_hyperion_y_dataset_controller(): void
    {
        // 1. Probar ruta de DatasetController original intacta
        $respDataset = $this->get('/limpiar-dataset');
        $respDataset->assertStatus(200);

        // 2. Probar vista principal de Hyperion Play
        $respView = $this->get('/hyperion');
        $respView->assertStatus(200);
        $respView->assertSee('HYPERION PLAY');

        // 3. Probar API de chat conversacional
        $respChat = $this->postJson('/api/chat/message', [
            'message' => 'Hola Hyperion, dame una rutina',
        ]);
        $respChat->assertStatus(200);
        $respChat->assertJsonStructure(['status', 'respuesta', 'user']);

        // 4. Probar API de reporte de métricas HU #5
        $respMetrics = $this->getJson('/api/metrics/report');
        $respMetrics->assertStatus(200);
        $respMetrics->assertJsonPath('status', 'success');
        $respMetrics->assertJsonPath('matriz.FP', 0);
    }

    /**
     * Test: Modificar datos de usuario (cambio de entorno, lesiones, biometría).
     */
    public function test_modificar_usuario_entorno_y_datos_biometricos(): void
    {
        $resp = $this->postJson('/api/user/update', [
            'user_id' => $this->user->id,
            'name' => 'Santiago Titán',
            'age' => 26,
            'environment' => 'gimnasio',
            'injury' => 'hombro',
            'weight_kg' => 80.0,
            'height_cm' => 178.0,
            'body_fat_pct' => 15.2,
        ]);

        $resp->assertStatus(200);
        $resp->assertJsonPath('status', 'success');

        $this->user->refresh();
        $this->assertEquals('Santiago Titán', $this->user->name);
        $this->assertEquals('gimnasio', $this->user->environment);
        $this->assertEquals('hombro', $this->user->injury);
        $this->assertEquals(80.0, $this->user->weight_kg);
        $this->assertEquals(15.2, $this->user->body_fat_pct);
    }

    /**
     * Test: Historial de mensajes de chat persistente e independiente por usuario.
     */
    public function test_persistencia_historial_chat_e_independencia_por_usuario(): void
    {
        $user2 = User::create([
            'name' => 'Valeria',
            'email' => 'valeria@test.com',
            'password' => bcrypt('password'),
            'environment' => 'casa_sin_equipo',
        ]);

        // Enviar mensaje con usuario 1
        $this->postJson('/api/chat/message', [
            'user_id' => $this->user->id,
            'message' => 'Hola Hyperion, soy Santiago',
        ]);

        $this->assertCount(2, $this->user->chatMessages); // 1 user + 1 hyperion
        $this->assertCount(0, $user2->chatMessages);

        // Enviar mensaje con usuario 2
        $this->postJson('/api/chat/message', [
            'user_id' => $user2->id,
            'message' => 'Hola Hyperion, soy Valeria',
        ]);

        $this->assertCount(2, $user2->fresh()->chatMessages);

        // Reiniciar chat de usuario 1
        $this->postJson('/api/chat/reset', [
            'user_id' => $this->user->id,
        ]);

        $this->assertCount(0, $this->user->fresh()->chatMessages);
        $this->assertCount(2, $user2->fresh()->chatMessages); // El historial de usuario 2 permanece intacto

        // Probar cambio explícito de usuario activo (switchUser)
        $respSwitch = $this->postJson('/api/user/switch', [
            'user_id' => $user2->id,
        ]);
        $respSwitch->assertStatus(200);
        $respSwitch->assertJsonPath('status', 'success');
        $this->assertEquals($user2->id, session('active_user_id'));
    }
}
