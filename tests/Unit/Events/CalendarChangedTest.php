<?php

namespace Tests\Unit\Events;

use App\Events\CalendarChanged;
use Illuminate\Broadcasting\Channel;
use Tests\TestCase;

class CalendarChangedTest extends TestCase
{
    // ✅ Event broadcast ke channel 'calendar'
    public function test_broadcasts_on_calendar_channel(): void
    {
        $event    = new CalendarChanged('created', 42);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(Channel::class, $channels[0]);
        $this->assertEquals('calendar', $channels[0]->name);
    }

    // ✅ Nama event broadcast sesuai
    public function test_broadcast_event_name_is_correct(): void
    {
        $event = new CalendarChanged('updated', 99);
        $this->assertEquals('calendar.changed', $event->broadcastAs());
    }

    // ✅ Properti action dan eventId tersimpan dengan benar
    public function test_stores_action_and_event_id(): void
    {
        $event = new CalendarChanged('deleted', 7);

        $this->assertEquals('deleted', $event->action);
        $this->assertEquals(7, $event->eventId);
    }
}