<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'title'       => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'assigned_to' => null,
            'status'      => TaskStatus::TODO,
            'priority'    => TaskPriority::MEDIUM,
            'due_date'    => fake()->optional()->dateTimeBetween('now', '+60 days'),
        ];
    }

    public function assigned(User $user): static
    {
        return $this->state(['assigned_to' => $user->id]);
    }

    public function overdue(): static
    {
        return $this->state([
            'due_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'status'   => TaskStatus::IN_PROGRESS,
        ]);
    }

    public function done(): static
    {
        return $this->state([
            'status'       => TaskStatus::DONE,
            'completed_at' => now(),
        ]);
    }
}
