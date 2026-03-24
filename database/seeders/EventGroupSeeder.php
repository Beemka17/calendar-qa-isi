<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EventGroup;

class EventGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'Audit', 'color_hex' => '#ef4444', 'is_active' => true],
            ['name' => 'Meeting', 'color_hex' => '#3b82f6', 'is_active' => true],
            ['name' => 'Training', 'color_hex' => '#22c55e', 'is_active' => true],
            ['name' => 'Supplier', 'color_hex' => '#f59e0b', 'is_active' => true],
            ['name' => 'Internal', 'color_hex' => '#a855f7', 'is_active' => true],
        ];

        foreach ($groups as $g) {
            EventGroup::updateOrCreate(['name' => $g['name']], $g);
        }
    }
}