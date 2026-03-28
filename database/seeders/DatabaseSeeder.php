<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
        DefaultUsersSeeder::class,
        EventGroupSeeder::class,
        EventSeeder::class,
        TeamSeeder::class,
    ]); 
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    \App\Models\User::query()
    ->where('username', 'admin')
    ->update(['role' => 'admin', 'is_active' => true]);
    }
}
