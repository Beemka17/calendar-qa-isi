<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    // ✅ Authenticate: request JSON dari guest → return null (tidak redirect)
    // Ini men-trigger baris: return $request->expectsJson() ? null : route('login')
    public function test_unauthenticated_json_request_returns_401_not_redirect(): void
    {
        // JSON request tidak di-redirect, tapi mendapat 401
        $this->getJson('/api/events')
            ->assertStatus(401);
    }

    // ✅ Authenticate: request web biasa dari guest → redirect ke login
    public function test_unauthenticated_web_request_redirects_to_login(): void
    {
        $this->get('/calendar')
            ->assertRedirect(route('login'));
    }

    // ✅ RedirectIfAuthenticated: user yang sudah login
    //    tidak bisa akses halaman guest (login/register) → redirect ke HOME
    public function test_authenticated_user_redirected_from_login_page(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect('/calendar'); // RouteServiceProvider::HOME
    }

    // ✅ RedirectIfAuthenticated: user yang sudah login
    //    tidak bisa akses register
    public function test_authenticated_user_redirected_from_register_page(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get('/register')
            ->assertRedirect('/calendar');
    }
}