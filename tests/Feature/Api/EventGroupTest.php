<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\EventGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventGroupTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function non_admin_cannot_create_event_group()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->postJson('/api/event-groups', [
            'name' => 'New Category',
            'color_hex' => '#FF0000',
            'is_active' => true
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_create_event_group()
    {
        $admin = User::factory()->create([
    'role' => 'admin',
    'is_active' => true, // Tambahkan ini untuk memastikan tidak tersangkut middleware lain
]);

        $response = $this->actingAs($admin)->postJson('/api/event-groups', [
            'name' => 'Internal Audit',
            'color_hex' => '#00FF00',
            'is_active' => true
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('event_groups', ['name' => 'Internal Audit']);
    }
}