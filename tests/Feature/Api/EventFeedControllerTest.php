<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\EventFeedController;
use App\Models\Event;
use App\Models\EventGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EventFeedControllerTest extends TestCase
{
    use RefreshDatabase;

    // ✅ Test langsung memanggil method index() tanpa lewat HTTP/route
    // Karena controller ini tidak terdaftar di route manapun
    public function test_index_returns_events_in_correct_format(): void
    {
        $user  = User::factory()->create(['is_active' => true]);
        $group = EventGroup::factory()->create(['is_active' => true]);

        Event::factory()->count(3)->create([
            'event_group_id' => $group->id,
            'created_by'     => $user->id,
        ]);

        $controller = new EventFeedController();
        $result     = $controller->index();

        // Hasil adalah collection, konversi ke array
        $data = $result->toArray();

        $this->assertCount(3, $data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('title', $data[0]);
        $this->assertArrayHasKey('start', $data[0]);
        $this->assertArrayHasKey('end', $data[0]);
    }

    // ✅ Test urutan — harus terurut berdasarkan start_at
    public function test_index_returns_events_ordered_by_start_at(): void
    {
        $user  = User::factory()->create(['is_active' => true]);
        $group = EventGroup::factory()->create(['is_active' => true]);

        Event::factory()->create([
            'event_group_id' => $group->id,
            'created_by'     => $user->id,
            'start_at'       => '2025-07-01 09:00:00',
            'end_at'         => '2025-07-01 17:00:00',
            'title'          => 'Event Kedua',
        ]);

        Event::factory()->create([
            'event_group_id' => $group->id,
            'created_by'     => $user->id,
            'start_at'       => '2025-06-01 09:00:00',
            'end_at'         => '2025-06-01 17:00:00',
            'title'          => 'Event Pertama',
        ]);

        $controller = new EventFeedController();
        $result     = $controller->index()->toArray();

        $this->assertEquals('Event Pertama', $result[0]['title']);
        $this->assertEquals('Event Kedua', $result[1]['title']);
    }
}