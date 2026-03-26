<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Models\EventGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        // FullCalendar akan mengirim start/end (ISO / YYYY-MM-DD)
        $start = $request->query('start');
        $end   = $request->query('end');

        $groupIds = $request->query('group_ids'); // bisa array
        $q        = $request->query('q');
        $pic      = $request->query('pic');
        $location = $request->query('location');

        $query = Event::query()
            ->with(['group:id,name,color_hex', 'teams:id,name'])
            ->when($start && $end, function ($q2) use ($start, $end) {
                $startAt = Carbon::parse($start);
                $endAt   = Carbon::parse($end);

                // event overlap range:
                $q2->where('start_at', '<', $endAt)
                   ->where('end_at', '>', $startAt);
            })
            ->when($groupIds, function ($q2) use ($groupIds) {
                $ids = is_array($groupIds) ? $groupIds : [$groupIds];
                $ids = array_filter(array_map('intval', $ids));
                if (count($ids)) $q2->whereIn('event_group_id', $ids);
            })
            ->when($q, function ($q2) use ($q) {
                $q2->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->when($pic, fn($q2) => $q2->where('pic', 'like', "%{$pic}%"))
            ->when($location, fn($q2) => $q2->where('location', 'like', "%{$location}%"))
            ->orderBy('start_at');

        //$events = $query->get();
        $events = $query->with('teams:id,name')->get();

        $result = [];
        $cutiGrouped = [];

        foreach ($events as $e) {

        // 🔥 CEK apakah CUTI
        if ($e->group?->name === 'Cuti') {

        $date = $e->start_at->format('Y-m-d');
        $type = $e->leave_type ?? 'full';

        $cutiGrouped[$date][$type][] = $e;

        } else {

        $color = $e->group?->color_hex ?? '#1d4ed8';

        $result[] = [
            'id' => $e->id,
            'title' => $e->title,
            'start' => $e->start_at->toIso8601String(),
            'end' => $e->end_at->toIso8601String(),
            'backgroundColor' => $color,
            'borderColor' => $color,
            'extendedProps' => [
                'event_group_id' => $e->event_group_id,
                'event_group_name' => $e->group?->name,
                'location' => $e->location,
                'pic' => $e->pic,
                'description' => $e->description,
                'leave_type' => $e->leave_type,
                'teams' => $e->teams->pluck('name'),
                'team_ids' => $e->teams->pluck('id'),
                'attendance' => $e->attendance,
                'teams' => $e->teams->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                ]),
                ],
            ];
            }
        }

        foreach ($cutiGrouped as $date => $types) {

        foreach (['full', 'half'] as $type) {

            if (!isset($types[$type])) continue;

            $items = collect($types[$type]);
            $names = $items->pluck('pic')->values();
            $list = $items->map(function ($e) {
        return [
            'id' => $e->id,
            'name' => $e->pic ?: 'Tanpa Nama',
        ];
        })->values();
            $count = $names->count();

            $result[] = [
                'title' => ($type === 'full' ? '🟡 CUTI FULL' : '🟠 CUTI HALF') . " ($count)",
                'start' => $date,
                'allDay' => true,
                'backgroundColor' => $type === 'full' ? '#facc15' : '#fb923c',
                'borderColor' => $type === 'full' ? '#facc15' : '#fb923c',

                'extendedProps' => [
                    'type' => 'cuti_aggregate',
                    'leave_type' => $type,
                    'list' => $list,
                    //'names' => $names,
                    ]
                ];
            }
        }
        return $result;

        /*
        // FullCalendar JSON format
        return $events->map(function (Event $e) {
            $color = $e->group?->color_hex ?? '#1d4ed8';

            return [
                'id' => $e->id,
                'title' => $e->title,
                'start' => $e->start_at->toIso8601String(),
                'end' => $e->end_at->toIso8601String(),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'event_group_id' => $e->event_group_id,
                    'event_group_name' => $e->group?->name,
                    'location' => $e->location,
                    'pic' => $e->pic,
                    'description' => $e->description,
                    'created_by' => $e->created_by,
                ],
            ];
        });
        */
    }

    public function show(Event $event)
    {
    return response()->json([
        'id' => $event->id,
        'title' => $event->title,
        'event_group_id' => $event->event_group_id,
        'start_at' => $event->start_at->toIso8601String(),
        'end_at' => $event->end_at->toIso8601String(),
        'location' => $event->location,
        'pic' => $event->pic,
        'description' => $event->description,
        'leave_type' => $event->leave_type,
    ]);
    }
    
    public function store(StoreEventRequest $request)
    {
    
        try {
    // code
        $data = $request->validated();

        unset($data['team_ids']);

        $event = Event::create([
            ...$data,
            'attendance' => $request->attendance,
            'created_by' => $request->user()->id,
            'leave_type' => $request->leave_type,
            'updated_by' => null,
        ]);

        // 🔥 sync team
        $teamIds = $request->input('team_ids', []);

        $event->teams()->sync($teamIds);

        $event->load('group:id,name,color_hex');

        event(new \App\Events\CalendarChanged('created', $event->id));

        return response()->json($this->toFcEvent($event), 201);
    } catch (\Throwable $e) {
    dd($e->getMessage());
    }
        
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $this->authorize('update', $event);

        $data = $request->validated();

        $event->update([
            ...$data,
            'leave_type' => $request->leave_type,
            'updated_by' => $request->user()->id,
        ]);

        $event->teams()->sync($data['team_ids'] ?? []);

        $event->load('group:id,name,color_hex');

        event(new \App\Events\CalendarChanged('updated', $event->id));

        event(new \App\Events\CalendarChanged('moved', $event->id));
        event(new \App\Events\CalendarChanged('resized', $event->id));

        return $this->toFcEvent($event);
    }

    public function destroy(Request $request, Event $event)
    {
        $this->authorize('delete', $event);

        $event->delete();

        event(new \App\Events\CalendarChanged('deleted', $event->id));

        return response()->noContent();
    }

    private function toFcEvent(Event $e): array
    {
        $color = $e->group?->color_hex ?? '#1d4ed8';

        return [
            'id' => $e->id,
            'title' => $e->title,
            'start' => $e->start_at->toIso8601String(),
            'end' => $e->end_at->toIso8601String(),
            'backgroundColor' => $color,
            'borderColor' => $color,
            'extendedProps' => [
                'event_group_id' => $e->event_group_id,
                'event_group_name' => $e->group?->name,
                'location' => $e->location,
                'pic' => $e->pic,
                'description' => $e->description,
                'created_by' => $e->created_by,
                'teams' => $e->teams->pluck('name'),
                'attendance' => $e->attendance,
                'teams' => $e->teams->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                ]),
            ],
        ];
    }
}