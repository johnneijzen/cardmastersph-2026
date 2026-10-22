<?php

namespace Database\Factories;

use App\Models\ProductSubclass;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductSubclass>
 */
class ProductSubclassFactory extends Factory
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
