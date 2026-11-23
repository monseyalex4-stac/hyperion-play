<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GamificationRpgService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HyperionViewController extends Controller
{
    protected GamificationRpgService $rpgService;

    public function __construct(GamificationRpgService $rpgService)
    {
        $this->rpgService = $rpgService;
    }

    public function index(Request $request): View
    {
        // 1. Prioridad: Parámetro en URL (?user_id=X), luego sesión activa, usuario Auth o cookie
        $userId = $request->query('user_id')
            ?? session('active_user_id')
            ?? Auth::id()
            ?? $request->cookie('hyperion_user_id');

        $user = $userId ? User::find($userId) : null;

        // 2. Si no se encontró por ID, intentar con Auth si existe
        if (!$user && Auth::check()) {
            $user = Auth::user();
        }

        // 3. Fallback: El último usuario actualizado o el primero
        if (!$user) {
            $user = User::latest('updated_at')->first() ?? User::first();
        }

        if (!$user) {
            $user = User::create([
                'name' => 'Aventurero',
                'email' => 'aventurero@hyperionplay.com',
                'password' => bcrypt('password123'),
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

        // Persistir el usuario activo en sesión y cookie (por 30 días)
        session(['active_user_id' => $user->id]);
        cookie()->queue('hyperion_user_id', (string) $user->id, 60 * 24 * 30);

        $allUsers = User::orderBy('id', 'asc')->get();
        $user->load('quests');
        $nivelInfo = $this->rpgService->obtenerInfoNivelPorXp($user->current_xp ?? 0);
        $sesiones = $user->workoutSessions()->latest()->take(6)->get();

        // Cargar historial de chat persistente del usuario
        $chatMessages = $user->chatMessages()->latest('id')->take(40)->get()->reverse()->values();

        // Recuperar última rutina generada en el contexto para restaurar en panel
        $ultimaRutina = null;
        foreach ($chatMessages->reverse() as $msg) {
            if (!empty($msg->routine_data)) {
                $ultimaRutina = $msg->routine_data;
                break;
            }
        }

        if (!$ultimaRutina) {
            $sesionEnProgreso = $user->workoutSessions()->where('status', 'en_progreso')->latest()->first();
            if ($sesionEnProgreso && !empty($sesionEnProgreso->exercises_data)) {
                $ultimaRutina = [
                    'session_id'       => $sesionEnProgreso->id,
                    'titulo'           => $sesionEnProgreso->title,
                    'duracion_minutos' => $sesionEnProgreso->total_duration_minutes,
                    'arquetipo'        => $sesionEnProgreso->archetype,
                    'xp_estimada'      => $sesionEnProgreso->xp_earned ?: 35,
                    'ejercicios'       => $sesionEnProgreso->exercises_data,
                    'aviso_medico'     => $sesionEnProgreso->medical_disclaimer_displayed ? '⚠️ Precaución médica aplicada.' : null,
                ];
            }
        }

        return view('hyperion', [
            'user'         => $user,
            'allUsers'     => $allUsers,
            'nivelInfo'    => $nivelInfo,
            'sesiones'     => $sesiones,
            'chatMessages' => $chatMessages,
            'ultimaRutina' => $ultimaRutina,
        ]);
    }
}
