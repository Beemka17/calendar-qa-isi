<?php

namespace Tests\Feature\Api;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamControllerTest extends TestCase
{
    use RefreshDatabase;

    // ✅ GET /api/teams — mengembalikan daftar team
    public function test_authenticated_user_can_list_teams(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Team::factory()->count(3)->create();

        $this->actingAs($user)
            ->getJson('/api/teams')
            ->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => ['id', 'name'],
            ]);
    }

    // ✅ Response hanya mengandung id dan name (tidak bocorkan data lain)
    public function test_team_list_only_returns_id_and_name(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Team::factory()->create(['name' => 'QC Team']);

        $response = $this->actingAs($user)->getJson('/api/teams');

        $data = $response->json();
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertArrayNotHasKey('created_at', $data[0]);
    }
}