<?php

namespace Database\Factories;

use App\Models\Habit;
use App\Models\HabitEntry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HabitEntry>
 */
class HabitEntryFactory extends Factory
{
    protected $model = HabitEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'habit_id' => Habit::factory(),
            'entry_date' => CarbonImmutable::now()->toDateString(),
        ];
    }

    public function on(string $date): static
    {
        return $this->state(fn (): array => ['entry_date' => $date]);
    }
}
