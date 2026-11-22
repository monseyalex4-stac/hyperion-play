<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GamificationRpgService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HyperionViewController extends Controller
{
    protected GamificationRpgService $rpgService;

    public function __construct(GamificationRpgService $rpgService)
    {
        $this->rpgService = $rpgService;
    }

    public function index(Request $request): View
    {
        $userId = $request->query('user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            $user = User::first();
        }

        if (!$user) {
            $user = User::create([
                'name' => 'Santiago (Aventurero)',
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

        $allUsers = User::orderBy('id', 'asc')->get();
        $user->load('quests');
        $nivelInfo = $this->rpgService->obtenerInfoNivelPorXp($user->current_xp ?? 0);
        $sesiones = $user->workoutSessions()->latest()->take(5)->get();

        return view('hyperion', [
            'user' => $user,
            'allUsers' => $allUsers,
            'nivelInfo' => $nivelInfo,
            'sesiones' => $sesiones,
        ]);
    }
}
