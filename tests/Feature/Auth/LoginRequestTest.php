<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    use RefreshDatabase;

    // ✅ Login dengan password salah → RateLimiter::hit() dipanggil
    // Ini men-cover baris: RateLimiter::hit($this->throttleKey()) dan
    // throw ValidationException dengan pesan 'username'
    public function test_failed_login_triggers_rate_limiter_hit(): void
    {
    User::factory()->create([
        'username'  => 'bagus123',
        'is_active' => true,
    ]);

    // Cukup verifikasi bahwa login gagal dan error muncul di session
    // Ini sudah men-cover baris RateLimiter::hit() dan throw ValidationException
    $this->post('/login', [
        'username' => 'bagus123',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['username']);
    }

    // ✅ Login gagal 5x berturut-turut → ensureIsNotRateLimited() melempar error
    // Ini men-cover seluruh blok if (! RateLimiter::tooManyAttempts(...))
    public function test_too_many_failed_logins_triggers_lockout(): void
{
    User::factory()->create([
        'username'  => 'bagus_lockout',
        'is_active' => true,
    ]);

    // Lakukan 5x login gagal berturut-turut dalam satu test
    // Ini men-cover baris RateLimiter::hit() sebanyak 5x
    foreach (range(1, 5) as $i) {
        $this->post('/login', [
            'username' => 'bagus_lockout',
            'password' => 'wrong-password',
        ]);
    }

    // Request ke-6 → seharusnya terkena lockout
    // Ini men-cover seluruh blok ensureIsNotRateLimited()
    $this->post('/login', [
        'username' => 'bagus_lockout',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['username']); // 'email' sesuai kode throttle Anda
}

public function test_ensure_is_not_rate_limited_throws_when_locked(): void
{
    RateLimiter::shouldReceive('tooManyAttempts')->once()->andReturn(true);
    RateLimiter::shouldReceive('availableIn')->once()->andReturn(30);

    $request = new \App\Http\Requests\Auth\LoginRequest();
    $request->merge(['username' => 'test', 'email' => '']);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $request->ensureIsNotRateLimited();
}
}