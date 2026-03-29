<?php

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\EventGroup;
use App\Models\User;
use App\Events\CalendarChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_active' => true]);
        $this->group = EventGroup::factory()->create(['name' => 'Meeting', 'is_active' => true]);
    }

    /** @test */
    public function user_can_create_event_and_triggers_broadcast()
    {
        EventFacade::fake();

        $response = $this->actingAs($this->user)->postJson('/api/events', [
            'title' => 'Project Kickoff',
            'event_group_id' => $this->group->id,
            'start_at' => now()->toIso8601String(),
            'end_at' => now()->addHour()->toIso8601String(),
            'pic' => 'Galih',
            'location' => 'Room A1',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('events', ['title' => 'Project Kickoff']);
        
        // Memastikan event broadcast terkirim untuk update real-time
        EventFacade::assertDispatched(CalendarChanged::class);
    }

    /** @test */
    public function cuti_is_aggregated_correctly_in_index()
    {
        $cutiGroup = EventGroup::factory()->create(['name' => 'Cuti']);
        
        // Buat 2 user cuti di hari yang sama
        Event::factory()->create([
            'title' => 'Cuti Tahunan 1',
            'event_group_id' => $cutiGroup->id,
            'leave_type' => 'full',
            'start_at' => '2026-03-28 08:00:00',
            'end_at' => '2026-03-28 17:00:00',
            'pic' => 'User A'
        ]);

        Event::factory()->create([
            'title' => 'Cuti Tahunan 2',
            'event_group_id' => $cutiGroup->id,
            'leave_type' => 'full',
            'start_at' => '2026-03-28 08:00:00',
            'end_at' => '2026-03-28 17:00:00',
            'pic' => 'User B'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/events?start=2026-03-01&end=2026-03-31');

        // Pastikan response mengandung title "🟡 CUTI FULL (2)" sesuai logic di Controller
        $response->assertJsonFragment(['title' => '🟡 CUTI FULL (2)']);
    }

    /** @test */
    public function user_cannot_delete_other_users_event()
    {
        $otherUser = User::factory()->create();
        $event = Event::factory()->create(['created_by' => $otherUser->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(403); // Forbidden berdasarkan EventPolicy
    }
}