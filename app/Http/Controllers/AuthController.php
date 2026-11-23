<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserQuest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Muestra la vista de inicio de sesión.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('hyperion.index');
        }

        return view('auth.login');
    }

    /**
     * Procesa el inicio de sesión con email o nombre y contraseña.
     */
    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($validated['login']);
        $password = $validated['password'];

        // Buscar por email o por nombre de aventurero
        $user = User::where('email', $loginInput)
            ->orWhere('name', $loginInput)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Credenciales inválidas. Revisa tu nombre de aventurero o contraseña.',
                ], 422);
            }

            return back()->withInput()->withErrors([
                'login' => 'El nombre de aventurero o contraseña no coinciden con nuestros registros.',
            ]);
        }

        Auth::login($user, true);
        session(['active_user_id' => $user->id]);
        cookie()->queue('hyperion_user_id', (string) $user->id, 60 * 24 * 30);

        if ($request->wantsJson()) {
            return response()->json([
                'status'   => 'success',
                'message'  => '¡Bienvenido de vuelta, ' . $user->name . '!',
                'redirect' => route('hyperion.index', ['user_id' => $user->id]),
                'user'     => $user,
            ]);
        }

        return redirect()->route('hyperion.index', ['user_id' => $user->id]);
    }

    /**
     * Muestra la vista de registro de nuevo aventurero.
     */
    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('hyperion.index');
        }

        return view('auth.register');
    }

    /**
     * Registra un nuevo aventurero con nombre y contraseña generada/elegida.
     */
    public function register(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:100',
            'email'                  => 'nullable|email|max:150|unique:users,email',
            'password'               => 'required|string|min:4|max:100',
            'age'                    => 'nullable|integer|min:12|max:100',
            'gender'                 => 'nullable|string',
            'weight_kg'              => 'nullable|numeric|min:30|max:300',
            'height_cm'              => 'nullable|numeric|min:100|max:250',
            'body_fat_pct'           => 'nullable|numeric|min:3|max:60',
            'environment'            => 'nullable|string|in:casa_sin_equipo,casa_basico,gimnasio',
            'injury'                 => 'nullable|string',
            'available_time_minutes' => 'nullable|integer|min:5|max:120',
            'fitness_goal'           => 'nullable|string',
        ]);

        $email = !empty($validated['email'])
            ? trim($validated['email'])
            : 'aventurero_' . Str::slug($validated['name']) . '_' . time() . '@hyperionplay.com';

        $weight = isset($validated['weight_kg']) ? (float)$validated['weight_kg'] : 70.0;
        $height = isset($validated['height_cm']) ? (float)$validated['height_cm'] : 170.0;
        $age = isset($validated['age']) ? (int)$validated['age'] : 25;
        $gender = $validated['gender'] ?? 'no_disponible';

        $bodyFat = isset($validated['body_fat_pct']) ? (float)$validated['body_fat_pct'] : null;
        if ($bodyFat === null && $height > 0) {
            $heightM = $height / 100.0;
            $bmi = $weight / ($heightM * $heightM);
            $sexFactor = ($gender === 'masculino') ? 1.0 : (($gender === 'femenino') ? 0.0 : 0.5);
            $bodyFat = round(max(5.0, min(50.0, (1.20 * $bmi) + (0.23 * $age) - (10.8 * $sexFactor) - 5.4)), 1);
        }

        $user = User::create([
            'name'                   => trim($validated['name']),
            'email'                  => $email,
            'password'               => Hash::make($validated['password']),
            'age'                    => $age,
            'gender'                 => $gender,
            'weight_kg'              => $weight,
            'height_cm'              => $height,
            'body_fat_pct'           => $bodyFat ?? 18.0,
            'environment'            => $validated['environment'] ?? 'casa_sin_equipo',
            'available_time_minutes' => $validated['available_time_minutes'] ?? 15,
            'injury'                 => $validated['injury'] ?? 'ninguna',
            'fitness_goal'           => $validated['fitness_goal'] ?? 'resistencia',
            'archetype'              => 'titan_agil',
            'current_level'          => 1,
            'current_xp'             => 0,
            'rpg_title'              => 'Aspirante',
            'interview_completed'    => false,
        ]);

        // Misión inicial
        UserQuest::create([
            'user_id'       => $user->id,
            'quest_key'     => 'mision_calibracion_' . $user->id,
            'title'         => 'El Despertar del Guerrero',
            'description'   => 'Completa tu primera sesión guiada por Hyperion',
            'target_count'  => 1,
            'current_count' => 0,
            'reward_xp'     => 30,
            'completed'     => false,
        ]);

        Auth::login($user, true);
        session(['active_user_id' => $user->id]);
        cookie()->queue('hyperion_user_id', (string) $user->id, 60 * 24 * 30);

        if ($request->wantsJson()) {
            return response()->json([
                'status'   => 'success',
                'message'  => '¡Aventurero ' . $user->name . ' registrado con éxito!',
                'redirect' => route('hyperion.index', ['user_id' => $user->id]),
                'user'     => $user,
            ]);
        }

        return redirect()->route('hyperion.index', ['user_id' => $user->id]);
    }

    /**
     * Cierra la sesión activa.
     */
    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        cookie()->queue(cookie()->forget('hyperion_user_id'));

        if ($request->wantsJson()) {
            return response()->json([
                'status'   => 'success',
                'message'  => 'Sesión cerrada correctamente.',
                'redirect' => route('login'),
            ]);
        }

        return redirect()->route('login');
    }

    /**
     * Muestra la pantalla dedicada de Acceso Directo con Google.
     */
    public function showGoogleDirect(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('hyperion.index');
        }

        return view('auth.google_direct');
    }

    /**
     * Procesa el acceso directo con Google (con email y foto real personalizada).
     */
    public function processGoogleDirect(Request $request): RedirectResponse
    {
        $email = trim($request->input('email', ''));
        $name = trim($request->input('name', ''));
        $avatar = trim($request->input('avatar', ''));

        // Si se envió vacío (acceso rápido instantáneo 1 clic)
        if (empty($email)) {
            $email = 'aventurero.google.' . Str::lower(Str::random(6)) . '@gmail.com';
        }

        if (empty($name)) {
            $name = explode('@', $email)[0];
            $name = ucfirst(preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ]/', '', $name));
            if (empty($name)) {
                $name = 'Aventurero Google';
            }
        }

        // Si no proveyó avatar manual, intentar avatar dinámico de Google o fallback oficial
        if (empty($avatar)) {
            $avatar = 'https://unavatar.io/google/' . urlencode($email) . '?fallback=' . urlencode('https://lh3.googleusercontent.com/a/default-user=s96-c');
        }

        $googleId = 'google_direct_' . md5($email);

        $user = User::where('email', $email)
            ->orWhere('google_id', $googleId)
            ->first();

        if ($user) {
            if (empty($user->google_id)) {
                $user->google_id = $googleId;
            }
            if (!empty($avatar)) {
                $user->avatar = $avatar;
            }
            $user->save();
        } else {
            $user = User::create([
                'name'                   => $name,
                'email'                  => $email,
                'google_id'              => $googleId,
                'avatar'                 => $avatar,
                'password'               => Hash::make(Str::random(32)),
                'age'                    => 25,
                'gender'                 => 'no_disponible',
                'weight_kg'              => 70.0,
                'height_cm'              => 170.0,
                'body_fat_pct'           => 18.0,
                'environment'            => 'casa_sin_equipo',
                'available_time_minutes' => 15,
                'injury'                 => 'ninguna',
                'fitness_goal'           => 'resistencia',
                'archetype'              => 'titan_agil',
                'current_level'          => 1,
                'current_xp'             => 0,
                'rpg_title'              => 'Aspirante',
                'interview_completed'    => false,
            ]);

            UserQuest::create([
                'user_id'       => $user->id,
                'quest_key'     => 'mision_calibracion_' . $user->id,
                'title'         => 'El Despertar del Guerrero',
                'description'   => 'Completa tu primera sesión guiada por Hyperion',
                'target_count'  => 1,
                'current_count' => 0,
                'reward_xp'     => 30,
                'completed'     => false,
            ]);
        }

        Auth::login($user, true);
        session(['active_user_id' => $user->id]);
        cookie()->queue('hyperion_user_id', (string) $user->id, 60 * 24 * 30);

        return redirect()->route('hyperion.index', ['user_id' => $user->id]);
    }

    /**
     * Redirige al flujo de autenticación de Google OAuth o a Acceso Directo con Google.
     */
    public function googleRedirect(Request $request): RedirectResponse
    {
        $clientId = $request->input('client_id')
            ?? session('google_client_id')
            ?? config('services.google.client_id');
        $clientSecret = $request->input('client_secret')
            ?? session('google_client_secret')
            ?? config('services.google.client_secret');

        if ($request->filled('client_id')) {
            session([
                'google_client_id'     => $clientId,
                'google_client_secret' => $clientSecret,
            ]);
        }

        if (empty($clientId)) {
            // Acceso directo con Google sin necesidad de configurar credenciales Cloud
            return redirect()->route('auth.google.direct');
        }

        $redirectUri = config('services.google.redirect');
        $state = Str::random(40);
        session(['google_oauth_state' => $state]);

        $query = http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => url($redirectUri),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    /**
     * Procesa el retorno de Google OAuth.
     */
    public function googleCallback(Request $request): RedirectResponse
    {
        $state = $request->query('state');
        if (!$state || $state !== session('google_oauth_state')) {
            return redirect()->route('login')->withErrors(['google' => 'Error de validación de seguridad (state inválido).']);
        }

        $code = $request->query('code');
        if (!$code) {
            return redirect()->route('login')->withErrors(['google' => 'No se recibió autorización de Google.']);
        }

        $clientId = session('google_client_id') ?? config('services.google.client_id');
        $clientSecret = session('google_client_secret') ?? config('services.google.client_secret');
        $redirectUri = url(config('services.google.redirect'));

        // 1. Intercambiar code por Access Token
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ]);

        if (!$response->successful()) {
            return redirect()->route('login')->withErrors(['google' => 'Error al comunicarse con Google OAuth.']);
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'] ?? null;

        // 2. Obtener datos del perfil de Google
        $userInfo = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');
        if (!$userInfo->successful()) {
            return redirect()->route('login')->withErrors(['google' => 'No se pudo obtener la información de perfil de Google.']);
        }

        $googleData = $userInfo->json();
        $googleId = $googleData['sub'] ?? null;
        $email = $googleData['email'] ?? null;
        $name = $googleData['name'] ?? 'Aventurero Google';
        $picture = $googleData['picture'] ?? null;

        // 3. Buscar o crear el usuario
        $user = User::where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $user->google_id = $googleId;
            if ($picture) {
                $user->avatar = $picture;
            }
            $user->save();
        } else {
            $user = User::create([
                'name'                   => $name,
                'email'                  => $email,
                'google_id'              => $googleId,
                'avatar'                 => $picture,
                'password'               => Hash::make(Str::random(32)),
                'age'                    => 25,
                'gender'                 => 'no_disponible',
                'weight_kg'              => 70.0,
                'height_cm'              => 170.0,
                'body_fat_pct'           => 18.0,
                'environment'            => 'casa_sin_equipo',
                'available_time_minutes' => 15,
                'injury'                 => 'ninguna',
                'fitness_goal'           => 'resistencia',
                'archetype'              => 'titan_agil',
                'current_level'          => 1,
                'current_xp'             => 0,
                'rpg_title'              => 'Aspirante',
                'interview_completed'    => false,
            ]);

            UserQuest::create([
                'user_id'       => $user->id,
                'quest_key'     => 'mision_calibracion_' . $user->id,
                'title'         => 'El Despertar del Guerrero',
                'description'   => 'Completa tu primera sesión guiada por Hyperion',
                'target_count'  => 1,
                'current_count' => 0,
                'reward_xp'     => 30,
                'completed'     => false,
            ]);
        }

        Auth::login($user, true);
        session(['active_user_id' => $user->id]);
        cookie()->queue('hyperion_user_id', (string) $user->id, 60 * 24 * 30);

        return redirect()->route('hyperion.index', ['user_id' => $user->id]);
    }

    /**
     * Registro/Inicio rápido con cuenta de Google (un solo clic sin depender de credenciales cloud).
     */
    public function googleOneClick(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'     => 'required|email|max:150',
            'name'      => 'nullable|string|max:100',
            'avatar'    => 'nullable|string',
            'google_id' => 'nullable|string',
        ]);

        $email = trim($validated['email']);
        $name = !empty($validated['name']) ? trim($validated['name']) : explode('@', $email)[0];
        $avatar = $validated['avatar'] ?? null;
        $googleId = $validated['google_id'] ?? ('google_sim_' . md5($email));

        $user = User::where('email', $email)
            ->orWhere('google_id', $googleId)
            ->first();

        if ($user) {
            if (empty($user->google_id)) {
                $user->google_id = $googleId;
            }
            if ($avatar && empty($user->avatar)) {
                $user->avatar = $avatar;
            }
            $user->save();
        } else {
            $user = User::create([
                'name'                   => $name,
                'email'                  => $email,
                'google_id'              => $googleId,
                'avatar'                 => $avatar,
                'password'               => Hash::make(Str::random(32)),
                'age'                    => 25,
                'gender'                 => 'no_disponible',
                'weight_kg'              => 70.0,
                'height_cm'              => 170.0,
                'body_fat_pct'           => 18.0,
                'environment'            => 'casa_sin_equipo',
                'available_time_minutes' => 15,
                'injury'                 => 'ninguna',
                'fitness_goal'           => 'resistencia',
                'archetype'              => 'titan_agil',
                'current_level'          => 1,
                'current_xp'             => 0,
                'rpg_title'              => 'Aspirante',
                'interview_completed'    => false,
            ]);

            UserQuest::create([
                'user_id'       => $user->id,
                'quest_key'     => 'mision_calibracion_' . $user->id,
                'title'         => 'El Despertar del Guerrero',
                'description'   => 'Completa tu primera sesión guiada por Hyperion',
                'target_count'  => 1,
                'current_count' => 0,
                'reward_xp'     => 30,
                'completed'     => false,
            ]);
        }

        Auth::login($user, true);
        session(['active_user_id' => $user->id]);
        cookie()->queue('hyperion_user_id', (string) $user->id, 60 * 24 * 30);

        return response()->json([
            'status'   => 'success',
            'message'  => '¡Bienvenido con Google, ' . $user->name . '!',
            'redirect' => route('hyperion.index', ['user_id' => $user->id]),
            'user'     => $user,
        ]);
    }
}
