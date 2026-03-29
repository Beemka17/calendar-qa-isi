<?php

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\EventGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    // ✅ GET /api/event-groups — semua user bisa akses (index publik)
    public function test_anyone_authenticated_can_list_event_groups(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        EventGroup::factory()->count(3)->create();

        $this->actingAs($user)
            ->getJson('/api/event-groups')
            ->assertStatus(200)
            ->assertJsonCount(3);
    }

    // ✅ Admin aktif bisa buat event group
    public function test_admin_can_create_event_group(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->postJson('/api/event-groups', [
                'name'      => 'QC Meeting',
                'color_hex' => '#FF5733',
                'is_active' => true,
            ])
            ->assertStatus(201)
            ->assertJsonFragment(['name' => 'QC Meeting']);
    }

    // ❌ User biasa tidak bisa buat event group (403)
    public function test_regular_user_cannot_create_event_group(): void
    {
        $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

        $this->actingAs($user)
            ->postJson('/api/event-groups', [
                'name'      => 'Unauthorized Group',
                'color_hex' => '#000000',
                'is_active' => true,
            ])
            ->assertStatus(403);
    }

    // ✅ Admin bisa update event group
    public function test_admin_can_update_event_group(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $group = EventGroup::factory()->create(['name' => 'Old Name']);

        $this->actingAs($admin)
            ->patchJson("/api/event-groups/{$group->id}", ['name' => 'New Name'])
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'New Name']);
    }

    // ✅ Admin bisa hapus event group yang tidak dipakai
    public function test_admin_can_delete_unused_event_group(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $group = EventGroup::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/event-groups/{$group->id}")
            ->assertStatus(204);
    }

    // ❌ Admin tidak bisa hapus event group yang sudah dipakai event (422)
    public function test_admin_cannot_delete_event_group_in_use(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $user  = User::factory()->create(['is_active' => true]);
        $group = EventGroup::factory()->create(['is_active' => true]);
        Event::factory()->create(['event_group_id' => $group->id, 'created_by' => $user->id]);

        $this->actingAs($admin)
            ->deleteJson("/api/event-groups/{$group->id}")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Event Group masih digunakan oleh event.']);
    }
}