<?php

namespace Database\Factories;

use App\Enums\HabitCadence;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Habit>
 */
class HabitFactory extends Factory
{
    protected $model = Habit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Exercise', 'Read', 'Drink water', 'Meditate', 'Stretch']),
            'cadence' => HabitCadence::Daily,
            'target_per_period' => 1,
            'color' => fake()->randomElement(['#f97316', '#06b6d4', '#8b5cf6', '#10b981', '#ef4444']),
            'icon' => null,
            'is_active' => true,
            'position' => 0,
        ];
    }

    public function weekly(): static
    {
        return $this->state(fn (): array => ['cadence' => HabitCadence::Weekly]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
