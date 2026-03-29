<?php

namespace Tests\Feature\Policies;

use App\Models\Event;
use App\Models\EventGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPolicyTest extends TestCase
{
    use RefreshDatabase;

    // ✅ Admin aktif boleh update event siapapun
    public function test_admin_can_update_any_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'user', 'is_active' => true]);
        $group = EventGroup::factory()->create(['is_active' => true]);
        $event = Event::factory()->create(['created_by' => $other->id, 'event_group_id' => $group->id]);

        $this->assertTrue($admin->can('update', $event));
    }

    // ✅ User aktif boleh update event MILIK SENDIRI
    public function test_active_user_can_update_own_event(): void
    {
        $user  = User::factory()->create(['role' => 'user', 'is_active' => true]);
        $group = EventGroup::factory()->create(['is_active' => true]);
        $event = Event::factory()->create(['created_by' => $user->id, 'event_group_id' => $group->id]);

        $this->assertTrue($user->can('update', $event));
    }

    // ❌ User aktif TIDAK boleh update event milik orang lain
    public function test_active_user_cannot_update_others_event(): void
    {
        $user  = User::factory()->create(['role' => 'user', 'is_active' => true]);
        $owner = User::factory()->create(['role' => 'user', 'is_active' => true]);
        $group = EventGroup::factory()->create(['is_active' => true]);
        $event = Event::factory()->create(['created_by' => $owner->id, 'event_group_id' => $group->id]);

        $this->assertFalse($user->can('update', $event));
    }

    // ❌ User NON-AKTIF tidak boleh update event milik sendiri sekalipun
    public function test_inactive_user_cannot_update_own_event(): void
    {
        $user  = User::factory()->create(['role' => 'user', 'is_active' => false]);
        $group = EventGroup::factory()->create(['is_active' => true]);
        $event = Event::factory()->create(['created_by' => $user->id, 'event_group_id' => $group->id]);

        $this->assertFalse($user->can('update', $event));
    }

    // ❌ Admin NON-AKTIF juga tidak boleh update
    public function test_inactive_admin_cannot_update_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => false]);
        $group = EventGroup::factory()->create(['is_active' => true]);
        $event = Event::factory()->create(['created_by' => $admin->id, 'event_group_id' => $group->id]);

        $this->assertFalse($admin->can('update', $event));
    }

    // ✅ Admin aktif boleh delete event siapapun
    public function test_admin_can_delete_any_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'user', 'is_active' => true]);
        $group = EventGroup::factory()->create(['is_active' => true]);
        $event = Event::factory()->create(['created_by' => $other->id, 'event_group_id' => $group->id]);

        $this->assertTrue($admin->can('delete', $event));
    }

    // ❌ User biasa tidak boleh delete event orang lain
    public function test_user_cannot_delete_others_event(): void
    {
        $user  = User::factory()->create(['role' => 'user', 'is_active' => true]);
        $owner = User::factory()->create(['role' => 'user', 'is_active' => true]);
        $group = EventGroup::factory()->create(['is_active' => true]);
        $event = Event::factory()->create(['created_by' => $owner->id, 'event_group_id' => $group->id]);

        $this->assertFalse($user->can('delete', $event));
    }
}