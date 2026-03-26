<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'event_group_id',
        'start_at',
        'end_at',
        'location',
        'pic',
        'description',
        'created_by',
        'updated_by',
        'leave_type',
        'attendance',

    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(EventGroup::class, 'event_group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function teams()
{
    return $this->belongsToMany(
        \App\Models\Team::class,
        'event_team',      // nama tabel pivot
        'event_id',        // FK ke events
        'team_id'          // FK ke teams
    );
}
}
