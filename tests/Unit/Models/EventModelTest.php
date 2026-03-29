<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\EventGroup;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventModelTest extends TestCase
{
    use RefreshDatabase;

    // ✅ Event belongsTo EventGroup via relasi group()
    public function test_event_belongs_to_group(): void
    {
        $group = EventGroup::factory()->create();
        $event = Event::factory()->create(['event_group_id' => $group->id]);

        $this->assertInstanceOf(EventGroup::class, $event->group);
        $this->assertEquals($group->id, $event->group->id);
    }

    // ✅ Event belongsTo User via creator()
    public function test_event_belongs_to_creator(): void
    {
        $user  = User::factory()->create();
        $group = EventGroup::factory()->create();
        $event = Event::factory()->create(['created_by' => $user->id, 'event_group_id' => $group->id]);

        $this->assertInstanceOf(User::class, $event->creator);
        $this->assertEquals($user->id, $event->creator->id);
    }

    // ✅ Event belongsToMany Team via pivot event_team
    public function test_event_can_have_multiple_teams(): void
    {
        $user  = User::factory()->create();
        $group = EventGroup::factory()->create();
        $event = Event::factory()->create(['created_by' => $user->id, 'event_group_id' => $group->id]);
        $teams = Team::factory()->count(3)->create();

        $event->teams()->sync($teams->pluck('id'));

        $this->assertCount(3, $event->fresh()->teams);
    }

    // ✅ SoftDelete bekerja — event tidak benar-benar terhapus
    public function test_event_is_soft_deleted(): void
    {
        $user  = User::factory()->create();
        $group = EventGroup::factory()->create();
        $event = Event::factory()->create(['created_by' => $user->id, 'event_group_id' => $group->id]);

        $event->delete();

        $this->assertSoftDeleted('events', ['id' => $event->id]);
        $this->assertNull(Event::find($event->id));
        $this->assertNotNull(Event::withTrashed()->find($event->id));
    }

    // ✅ Event belongsTo User via updater() (updated_by)
public function test_event_belongs_to_updater(): void
{
    $creator = User::factory()->create();
    $updater = User::factory()->create();
    $group   = EventGroup::factory()->create();

    $event = Event::factory()->create([
        'created_by'     => $creator->id,
        'updated_by'     => $updater->id,
        'event_group_id' => $group->id,
    ]);

    $this->assertInstanceOf(User::class, $event->updater);
    $this->assertEquals($updater->id, $event->updater->id);
}
}