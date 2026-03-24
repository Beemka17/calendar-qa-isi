<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DefaultUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
         // === ADMIN ===
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'QA Administrator',
                'email' => 'admin@quality.local',
                'email_verified_at' => now(),
                'password' => Hash::make('Admin@12345'),
                'role' => 'admin',
                'is_active' => true,
                'remember_token' => Str::random(10),
            ]
        );

        // === USER BIASA ===
        User::updateOrCreate(
            ['username' => 'user1'],
            [
                'name' => 'QA User',
                'email' => 'user1@quality.local',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'role' => 'user',
                'is_active' => true,
                'remember_token' => Str::random(10),
            ]
        );
    }
}
