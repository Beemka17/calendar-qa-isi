<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Team;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    
public function run()
{
    Team::insert([
        ['name' => 'Audit'],
        ['name' => 'Testroom'],
        ['name' => 'CS'],
        ['name' => 'QAE'],
        ['name' => 'PQC'],
        ['name' => 'Manajer-Advisor'],
        ['name' => 'Ka. Seksi'],
        ['name' => 'Admin'],
    ]);
}
}
