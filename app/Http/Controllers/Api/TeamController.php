<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Team;

class TeamController extends Controller
{
    //
    public function index()
    {
        return Team::select('id', 'name')->get();
    }
}
