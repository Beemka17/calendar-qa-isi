<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EventGroupController extends Controller
{
    public function index()
    {
        return EventGroup::query()
            ->orderBy('name')
            ->get(['id', 'name', 'color_hex', 'is_active']);
    }

    public function store(Request $request)
    {
        Gate::authorize('admin');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:event_groups,name'],
            'color_hex' => ['required', 'string', 'max:16'],
            'is_active' => ['required', 'boolean'],
        ]);

        return response()->json(EventGroup::create($data), 201);
    }

    public function update(Request $request, EventGroup $eventGroup)
    {
        Gate::authorize('admin');

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120', 'unique:event_groups,name,' . $eventGroup->id],
            'color_hex' => ['sometimes', 'required', 'string', 'max:16'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ]);

        $eventGroup->update($data);

        //return $eventGroup->fresh(['id', 'name', 'color_hex', 'is_active']);
        return response()->json($eventGroup->fresh()->only(['id','name','color_hex','is_active']));
    }

    public function destroy(EventGroup $eventGroup)
    {
        Gate::authorize('admin');

        // soft approach: jangan hapus jika sudah dipakai
        $inUse = $eventGroup->events()->exists();
        if ($inUse) {
            return response()->json([
                'message' => 'Event Group masih digunakan oleh event.',
            ], 422);
        }

        $eventGroup->delete();

        return response()->noContent();
    }
}