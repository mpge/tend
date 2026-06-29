<?php

namespace Database\Factories;

use App\Enums\TaskBucket;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'notes' => null,
            'bucket' => TaskBucket::Important,
            'status' => TaskStatus::Open,
            'priority' => 0,
            'due_date' => null,
            'position' => 0,
            'completed_at' => null,
        ];
    }

    public function eventual(): static
    {
        return $this->state(fn (): array => ['bucket' => TaskBucket::Eventual]);
    }

    public function done(): static
    {
        return $this->state(fn (): array => [
            'status' => TaskStatus::Done,
            'completed_at' => now(),
        ]);
    }
}
