<?php

namespace Tests\Feature\Api;

use App\Events\CalendarChanged;
use App\Models\Event;
use App\Models\EventGroup;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\TestCase;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    // ✅ GET /api/events — guest tidak bisa akses
    public function test_unauthenticated_user_cannot_access_events(): void
    {
        $response = $this->getJson('/api/events');
        $response->assertStatus(401);
    }

    // ✅ GET /api/events — user aktif bisa ambil data
    public function test_authenticated_user_can_get_events(): void
    {
        $user  = User::factory()->create(['is_active' => true]);
        $group = EventGroup::factory()->create(['is_active' => true]);
        Event::factory()->count(3)->create(['event_group_id' => $group->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/events');
        $response->assertStatus(200)->assertJsonCount(3);
    }

    // ✅ GET /api/events?start=&end= — filter range tanggal bekerja
    public function test_event_index_filters_by_date_range(): void
    {
        $user  = User::factory()->create(['is_active' => true]);
        $group = EventGroup::factory()->create(['is_active' => true]);

        // event di dalam range
        Event::factory()->create([
            'event_group_id' => $group->id,
            'created_by'     => $user->id,
            'start_at'       => '2025-06-10 09:00:00',
            'end_at'         => '2025-06-10 17:00:00',
        ]);

        // event di luar range
        Event::factory()->create([
            'event_group_id' => $group->id,
            'created_by'     => $user->id,
            'start_at'       => '2025-07-01 09:00:00',
            'end_at'         => '2025-07-01 17:00:00',
        ]);

        $response = $this->actingAs($user)->getJson('/api/events?start=2025-06-01&end=2025-06-30');
        $response->assertStatus(200)->assertJsonCount(1);
    }

    // ✅ GET /api/events/{id} — show single event
    public function test_show_returns_event_detail(): void
    {
        $user  = User::factory()->create(['is_active' => true]);
        $group = EventGroup::factory()->create(['is_active' => true]);
        $event = Event::factory()->create(['event_group_id' => $group->id, 'created_by' => $user->id]);

        $this->actingAs($user)
            ->getJson("/api/events/{$event->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $event->id]);
    }

    // ✅ POST /api/events — user aktif bisa buat event
    public function test_active_user_can_store_event(): void
    {
        EventFacade::fake();

        $user  = User::factory()->create(['is_active' => true]);
        $group = EventGroup::factory()->create(['is_active' => true]);

        $payload = [
            'title'          => 'Meeting Q2',
            'event_group_id' => $group->id,
            'start_at'       => '2025-06-10 09:00:00',
            'end_at'         => '2025-06-10 17:00:00',
        ];

        $this->actingAs($user)
            ->postJson('/api/events', $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['title' => 'Meeting Q2']);

        $this->assertDatabaseHas('events', ['title' => 'Meeting Q2', 'created_by' => $user->id]);

        // ✅ Pastikan event broadcast dikirim
        EventFacade::assertDispatched(CalendarChanged::class);
    }

    // ❌ POST /api/events — user NON-AKTIF tidak bisa buat event (403)
    public function test_inactive_user_cannot_store_event(): void
    {
        $user  = User::factory()->create(['is_active' => false]);
        $group = EventGroup::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->postJson('/api/events', [
                'title'          => 'Test',
                'event_group_id' => $group->id,
                'start_at'       => '2025-06-10 09:00:00',
                'end_at'         => '2025-06-10 17:00:00',
            ])
            ->assertStatus(403);
    }

    // ✅ POST /api/events — validasi title wajib diisi
    public function test_store_validates_required_title(): void
    {
        $user  = User::factory()->create(['is_active' => true]);
        $group = EventGroup::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->postJson('/api/events', [
                'event_group_id' => $group->id,
                'start_at'       => '2025-06-10 09:00:00',
                'end_at'         => '2025-06-10 17:00:00',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    // ✅ PUT /api/events/{id} — pemilik event bisa update
    public function test_owner_can_update_event(): void
    {
        EventFacade::fake();

        $user  = User::factory()->create(['is_active' => true, 'role' => 'user']);
        $group = EventGroup::factory()->create(['is_active' => true]);
        $event = Event::factory()->create([
            'event_group_id' => $group->id,
            'created_by'     => $user->id,
        ]);

        $this->actingAs($user)
            ->patchJson("/api/events/{$event->id}", [
                'title'   => 'Updated Title',
                'start_at'=> '2025-06-11 09:00:00',
                'end_at'  => '2025-06-11 17:00:00',
            ])
            ->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Title']);
    }

    // ❌ PUT /api/events/{id} — user lain tidak bisa update (403)
    public function test_non_owner_cannot_update_event(): void
    {
        $owner = User::factory()->create(['is_active' => true, 'role' => 'user']);
        $other = User::factory()->create(['is_active' => true, 'role' => 'user']);
        $group = EventGroup::factory()->create(['is_active' => true]);
        $event = Event::factory()->create([
            'event_group_id' => $group->id,
            'created_by'     => $owner->id,
        ]);

        $this->actingAs($other)
            ->patchJson("/api/events/{$event->id}", ['title' => 'Hack'])
            ->assertStatus(403);
    }

    // ✅ DELETE /api/events/{id} — pemilik bisa hapus
    public function test_owner_can_delete_event(): void
    {
        EventFacade::fake();

        $user  = User::factory()->create(['is_active' => true, 'role' => 'user']);
        $group = EventGroup::factory()->create(['is_active' => true]);
        $event = Event::factory()->create(['event_group_id' => $group->id, 'created_by' => $user->id]);

        $this->actingAs($user)
            ->deleteJson("/api/events/{$event->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('events', ['id' => $event->id]);
    }

    // ❌ DELETE /api/events/{id} — user lain tidak bisa hapus (403)
    public function test_non_owner_cannot_delete_event(): void
    {
        $owner = User::factory()->create(['is_active' => true, 'role' => 'user']);
        $other = User::factory()->create(['is_active' => true, 'role' => 'user']);
        $group = EventGroup::factory()->create(['is_active' => true]);
        $event = Event::factory()->create(['event_group_id' => $group->id, 'created_by' => $owner->id]);

        $this->actingAs($other)
            ->deleteJson("/api/events/{$event->id}")
            ->assertStatus(403);
    }

    // ✅ POST /api/events dengan team_ids — relasi pivot tersinkronisasi
    public function test_store_event_syncs_teams(): void
    {
        EventFacade::fake();

        $user  = User::factory()->create(['is_active' => true]);
        $group = EventGroup::factory()->create(['is_active' => true]);
        $teams = Team::factory()->count(2)->create();

        $this->actingAs($user)
            ->postJson('/api/events', [
                'title'          => 'Team Event',
                'event_group_id' => $group->id,
                'start_at'       => '2025-06-10 09:00:00',
                'end_at'         => '2025-06-10 17:00:00',
                'team_ids'       => $teams->pluck('id')->toArray(),
            ])
            ->assertStatus(201);

        $event = Event::where('title', 'Team Event')->first();
        $this->assertCount(2, $event->teams);
    }

    // ✅ UpdateEventRequest::authorize() — user non-aktif ditolak (403)
// Ini men-cover baris: return $this->user()?->is_active === true
public function test_inactive_user_cannot_update_event(): void
{
    $inactive = User::factory()->create(['is_active' => false, 'role' => 'user']);
    $group    = EventGroup::factory()->create(['is_active' => true]);
    $event    = Event::factory()->create([
        'event_group_id' => $group->id,
        'created_by'     => $inactive->id,
    ]);

    $this->actingAs($inactive)
        ->patchJson("/api/events/{$event->id}", ['title' => 'Test'])
        ->assertStatus(403);
}

// ✅ UpdateEventRequest::rules() — validasi end_at harus >= start_at
public function test_update_event_fails_when_end_before_start(): void
{
    $user  = User::factory()->create(['is_active' => true, 'role' => 'user']);
    $group = EventGroup::factory()->create(['is_active' => true]);
    $event = Event::factory()->create([
        'event_group_id' => $group->id,
        'created_by'     => $user->id,
    ]);

    $this->actingAs($user)
        ->patchJson("/api/events/{$event->id}", [
            'start_at' => '2025-06-10 17:00:00',
            'end_at'   => '2025-06-10 09:00:00', // end sebelum start
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['end_at']);
}

// ✅ UpdateEventRequest::rules() — leave_type hanya boleh 'full' atau 'half'
public function test_update_event_fails_with_invalid_leave_type(): void
{
    $user  = User::factory()->create(['is_active' => true, 'role' => 'user']);
    $group = EventGroup::factory()->create(['is_active' => true]);
    $event = Event::factory()->create([
        'event_group_id' => $group->id,
        'created_by'     => $user->id,
    ]);

    $this->actingAs($user)
        ->patchJson("/api/events/{$event->id}", [
            'leave_type' => 'invalid_value',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['leave_type']);
}
}