<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\EventGroup;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamModelTest extends TestCase
{
    use RefreshDatabase;

    // ✅ Team belongsToMany Event via pivot event_team
    // Men-cover relasi events() di Team model
    public function test_team_belongs_to_many_events(): void
    {
        $user  = User::factory()->create();
        $group = EventGroup::factory()->create();
        $team  = Team::factory()->create();

        $event1 = Event::factory()->create([
            'created_by'     => $user->id,
            'event_group_id' => $group->id,
        ]);
        $event2 = Event::factory()->create([
            'created_by'     => $user->id,
            'event_group_id' => $group->id,
        ]);

        // Assign team ke kedua event via pivot
        $team->events()->attach([$event1->id, $event2->id]);

        $this->assertCount(2, $team->fresh()->events);
    }

    // ✅ Relasi bersifat dua arah — event juga punya team ini
    public function test_team_event_relationship_is_bidirectional(): void
    {
        $user  = User::factory()->create();
        $group = EventGroup::factory()->create();
        $team  = Team::factory()->create(['name' => 'QC Team']);
        $event = Event::factory()->create([
            'created_by'     => $user->id,
            'event_group_id' => $group->id,
        ]);

        $event->teams()->sync([$team->id]);

        // Dari sisi Team → Event
        $this->assertTrue($team->events->contains($event->id));

        // Dari sisi Event → Team
        $this->assertTrue($event->teams->contains($team->id));
    }
}