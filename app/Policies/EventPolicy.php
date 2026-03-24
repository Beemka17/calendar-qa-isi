<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EventPolicy
{
    public function update(User $user, Event $event): bool
    {
        if (!$user->is_active) return false;
        if ($user->role === 'admin') return true;

        return $event->created_by === $user->id;
    }

    public function delete(User $user, Event $event): bool
    {
        if (!$user->is_active) return false;
        if ($user->role === 'admin') return true;

        return $event->created_by === $user->id;
    }
}
