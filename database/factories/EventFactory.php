<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\EventGroup;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startAt = $this->faker->dateTimeBetween('-1 month', '+1 month');
        // Membuat end_at selalu 1 jam setelah start_at agar logis
        $endAt = (clone $startAt)->modify('+1 hour');

        return [
            'event_group_id' => EventGroup::factory(), // Otomatis buat group jika tidak diisi
            'title' => $this->faker->sentence(3), // Solusi error "Field title doesn't have a default value"
            'start_at' => $startAt,
            'end_at' => $endAt,
            'pic' => $this->faker->name(),
            'location' => $this->faker->address(),
            'description' => $this->faker->paragraph(),
            'leave_type' => $this->faker->randomElement(['full', 'half', null]),
            'created_by' => User::factory(), // Otomatis buat user pembuat
            'updated_by' => null,
        ];
    }
}
