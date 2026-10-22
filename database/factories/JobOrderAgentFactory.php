<?php

namespace Database\Factories;

use App\Models\JobOrderAgent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobOrderAgent>
 */
class JobOrderAgentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Str::title(fake()->unique()->word()),
        ];
    }
}
