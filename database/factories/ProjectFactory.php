<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Project;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title'       => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'lead_id'     => User::factory(),
            'status'      => ProjectStatus::ACTIVE,
        ];
    }
}
