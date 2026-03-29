<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Team extends Model
{
    //
    use HasFactory;
    protected $fillable = ['name'];

    public function events()
{
    return $this->belongsToMany(
        \App\Models\Event::class,
        'event_team',
        'team_id',
        'event_id'
    );
}
}
