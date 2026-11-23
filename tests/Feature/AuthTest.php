<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prueba que la página de login cargue con el tema RPG y opciones de Google.
     */
    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('HYPERION PLAY');
        $response->assertSee('Continuar con Google');
        $response->assertSee('Entrar a la Forja');
    }

    /**
     * Prueba que la página de registro cargue con generador de contraseñas.
     */
    public function test_register_page_renders_successfully(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('FORJA TU LEYENDA');
        $response->assertSee('Generar Contraseña Segura');
        $response->assertSee('Registrarse con Google');
    }

    /**
     * Prueba el registro con nombre y contraseña generada.
     */
    public function test_user_can_register_with_name_and_password(): void
    {
        $response = $this->post('/register', [
            'name'        => 'Alejandro Titán',
            'email'       => 'alex@hyperion.com',
            'password'    => 'Titan-Fenix-999#',
            'age'         => 26,
            'environment' => 'gimnasio',
        ]);

        $this->assertAuthenticated();
        $user = User::where('email', 'alex@hyperion.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Alejandro Titán', $user->name);
        $this->assertTrue(Hash::check('Titan-Fenix-999#', $user->password));
        $this->assertEquals('gimnasio', $user->environment);
        $response->assertRedirect('/hyperion?user_id=' . $user->id);
    }

    /**
     * Prueba el login con nombre y contraseña.
     */
    public function test_user_can_login_with_name_and_password(): void
    {
        $user = User::create([
            'name'     => 'Carlos Guerrero',
            'email'    => 'carlos@hyperion.com',
            'password' => Hash::make('ClaveSecreta123!'),
        ]);

        $response = $this->post('/login', [
            'login'    => 'Carlos Guerrero',
            'password' => 'ClaveSecreta123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/hyperion?user_id=' . $user->id);
    }

    /**
     * Prueba el login con correo electrónico y contraseña.
     */
    public function test_user_can_login_with_email_and_password(): void
    {
        $user = User::create([
            'name'     => 'Sofia',
            'email'    => 'sofia@hyperion.com',
            'password' => Hash::make('SofiaPass2026$'),
        ]);

        $response = $this->post('/login', [
            'login'    => 'sofia@hyperion.com',
            'password' => 'SofiaPass2026$',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/hyperion?user_id=' . $user->id);
    }

    /**
     * Prueba que credenciales incorrectas sean rechazadas.
     */
    public function test_login_rejects_invalid_password(): void
    {
        User::create([
            'name'     => 'David',
            'email'    => 'david@hyperion.com',
            'password' => Hash::make('David123#'),
        ]);

        $response = $this->post('/login', [
            'login'    => 'David',
            'password' => 'PasswordErroneo',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    /**
     * Prueba el cierre de sesión (logout).
     */
    public function test_user_can_logout(): void
    {
        $user = User::create([
            'name'     => 'Usuario Activo',
            'email'    => 'activo@hyperion.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    /**
     * Prueba el registro/login rápido con cuenta de Google (1 Clic).
     */
    public function test_google_one_click_registers_and_authenticates_user(): void
    {
        $response = $this->postJson('/auth/google/one-click', [
            'email'  => 'aventurero.google@gmail.com',
            'name'   => 'Aventurero Google',
            'avatar' => 'https://lh3.googleusercontent.com/a/avatar123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        $this->assertAuthenticated();
        $user = User::where('email', 'aventurero.google@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Aventurero Google', $user->name);
        $this->assertNotNull($user->google_id);
        $this->assertEquals('https://lh3.googleusercontent.com/a/avatar123', $user->avatar);
    }

    /**
     * Prueba que el redirect de Google redirija a Acceso Directo si no hay credenciales en .env.
     */
    public function test_google_redirect_forwards_to_direct_access_without_client_id(): void
    {
        config(['services.google.client_id' => '']);

        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect('/auth/google/direct');
    }

    /**
     * Prueba que la página dedicada de Acceso Directo con Google cargue correctamente.
     */
    public function test_google_direct_page_renders_successfully(): void
    {
        $response = $this->get('/auth/google/direct');

        $response->assertStatus(200);
        $response->assertSee('Acceso con Cuenta Real de Google');
        $response->assertSee('Abrir Ventana Oficial de Google');
        $response->assertSee('Tu Correo Real de Google (@gmail.com)');
    }

    /**
     * Prueba el login instantáneo con 1 clic en Acceso Directo con Google.
     */
    public function test_google_direct_post_authenticates_instant_one_click(): void
    {
        $response = $this->post('/auth/google/direct');

        $this->assertAuthenticated();
        $user = auth()->user();
        $this->assertNotNull($user);
        $this->assertStringContainsString('@gmail.com', $user->email);
        $this->assertNotNull($user->google_id);
        $response->assertRedirect('/hyperion?user_id=' . $user->id);
    }

    /**
     * Prueba el login en Acceso Directo con Google con correo y alias personalizados.
     */
    public function test_google_direct_post_authenticates_with_custom_gmail(): void
    {
        $response = $this->post('/auth/google/direct', [
            'email' => 'valeria.direct@gmail.com',
            'name'  => 'Valeria Guerrera',
        ]);

        $this->assertAuthenticated();
        $user = User::where('email', 'valeria.direct@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Valeria Guerrera', $user->name);
        $this->assertStringStartsWith('google_direct_', $user->google_id);
        $response->assertRedirect('/hyperion?user_id=' . $user->id);
    }
}
