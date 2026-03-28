<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
      // Tambahkan method unverified() atau secara manual tandai email_verified_at
    $user = User::factory()->create([
        'email_verified_at' => now(), 
    ]);

    $response = $this->actingAs($user)->get('/calendar');

    $response->assertStatus(200);
    }
}
