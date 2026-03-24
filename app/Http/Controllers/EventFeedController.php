<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EventFeedController extends Controller
{
    //
    public function index()
    {
        return Event::query()
            ->orderBy('start_at')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'start' => $e->start_at->toISOString(),
                'end' => $e->end_at?->toISOString(),
            ]);
    }
}
