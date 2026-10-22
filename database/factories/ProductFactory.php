<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductClass;
use App\Models\ProductSubclass;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 50, 5000);
        $quantity = fake()->numberBetween(1, 20);

        return [
            'name' => Str::title(fake()->unique()->word().' '.fake()->unique()->word()),
            'amount' => round($unitPrice * $quantity, 2),
            'unit' => fake()->randomElement(['pc', 'sheet', 'roll', 'set']),
            'particulars' => fake()->sentence(),
            'unit_price' => $unitPrice,
            'class_id' => ProductClass::factory(),
            'subclass_id' => ProductSubclass::factory(),
        ];
    }
}
