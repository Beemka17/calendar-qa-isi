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
            ->with(['group:id,name,color_hex'])
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

        $events = $query->get();

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
    }

    public function store(StoreEventRequest $request)
    {
        $data = $request->validated();

        $event = Event::create([
            ...$data,
            'created_by' => $request->user()->id,
            'updated_by' => null,
        ]);

        $event->load('group:id,name,color_hex');

        return response()->json($this->toFcEvent($event), 201);
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $this->authorize('update', $event);

        $data = $request->validated();

        $event->update([
            ...$data,
            'updated_by' => $request->user()->id,
        ]);

        $event->load('group:id,name,color_hex');

        return $this->toFcEvent($event);
    }

    public function destroy(Request $request, Event $event)
    {
        $this->authorize('delete', $event);

        $event->delete();

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
            ],
        ];
    }
}