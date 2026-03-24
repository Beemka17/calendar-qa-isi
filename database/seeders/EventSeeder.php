<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\EventGroup;
use App\Models\User;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (!$user) return;

        $groups = EventGroup::where('is_active', true)->get();
        if ($groups->isEmpty()) return;

        $base = Carbon::now()->startOfWeek(); // Monday

        for ($i = 0; $i < 12; $i++) {
            $g = $groups[$i % $groups->count()];
            $start = $base->copy()->addDays($i % 7)->setTime(9 + ($i % 4), 0);
            $end = $start->copy()->addHours(1 + ($i % 3));

            Event::create([
                'title' => $g->name . ' Activity #' . ($i + 1),
                'event_group_id' => $g->id,
                'start_at' => $start,
                'end_at' => $end,
                'location' => 'QA Room',
                'pic' => 'QA Team',
                'description' => 'Seeded event sample',
                'created_by' => $user->id,
                'updated_by' => null,
            ]);
        }
    }
}