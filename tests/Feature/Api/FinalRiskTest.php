<?php

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\EventGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalRiskTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. STRESS TEST: 100 orang cuti dalam satu hari.
     * Menguji performa agregasi di EventController.
     */
    public function test_aggregation_with_large_number_of_leave_events()
    {
        $user = User::factory()->create();
        $cutiGroup = EventGroup::factory()->create(['name' => 'Cuti']);
        
        // Buat 100 event cuti pada tanggal yang sama
        Event::factory()->count(100)->create([
            'event_group_id' => $cutiGroup->id,
            'start_at' => '2026-03-28 08:00:00',
            'end_at' => '2026-03-28 17:00:00',
            'leave_type' => 'full'
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/events?start=2026-03-01&end=2026-03-31');

        $response->assertStatus(200);
        // Pastikan muncul title "🟡 CUTI FULL (100)"
        $response->assertJsonFragment(['title' => '🟡 CUTI FULL (100)']);
    }

    /**
     * 2. EDGE CASE: leave_type bernilai NULL.
     * Menguji fallback logic di Controller.
     */
    public function test_event_with_null_leave_type_defaults_to_full()
    {
        $user = User::factory()->create();
        $cutiGroup = EventGroup::factory()->create(['name' => 'Cuti']);
        
        Event::factory()->create([
            'event_group_id' => $cutiGroup->id,
            'leave_type' => null, // Case NULL
            'start_at' => '2026-03-28 08:00:00'
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/events?start=2026-03-01&end=2026-03-31');

        // Logic di Controller baris 63: $type = $e->leave_type ?? 'full';
        // Maka harus tetap terhitung sebagai 'full'
        $response->assertJsonFragment(['title' => '🟡 CUTI FULL (1)']);
    }

    /**
     * 3. CONSTRAINT TEST: Menghapus grup yang masih ada isinya.
     */
    public function test_cannot_delete_event_group_in_use()
    {
        //$admin = User::factory()->create(['role' => 'admin']);
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true, // Tambahkan ini untuk memastikan tidak tersangkut middleware lain
        ]);
        $group = EventGroup::factory()->create();
        Event::factory()->create(['event_group_id' => $group->id]);

        $response = $this->actingAs($admin)
            ->deleteJson("/api/event-groups/{$group->id}");

        $response->assertStatus(422)
            ->assertJson(['message' => 'Event Group masih digunakan oleh event.']);
    }

    /**
     * 4. SECURITY AUDIT: Akses Guest (Tanpa Login).
     */
    public function test_guest_cannot_access_any_api_endpoints()
    {
        // Mencoba akses index event tanpa actingAs()
        $this->getJson('/api/events')->assertStatus(401);
        
        // Mencoba akses admin area
        $this->postJson('/api/event-groups', [])->assertStatus(401);
    }

    /**
     * 5. SECURITY AUDIT: Akses User Biasa ke Admin Area.
     */
    public function test_regular_user_cannot_create_event_group()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->postJson('/api/event-groups', [
            'name' => 'Illegal Group',
            'color_hex' => '#FF0000',
            'is_active' => true
        ]);

        $response->assertStatus(403); // Forbidden
    }
}