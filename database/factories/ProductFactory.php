<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductClass;
use App\Models\ProductSubclass;
use App\Support\Money\MoneyContext;
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
        $unitPriceCents = fake()->numberBetween(5000, 500000);
        $quantity = fake()->numberBetween(1, 20);

        return [
            'name' => Str::title(fake()->unique()->word().' '.fake()->unique()->word()),
            'amount' => MoneyContext::toStorage(MoneyContext::ofMinor($unitPriceCents * $quantity)),
            'unit' => fake()->randomElement(['pc', 'sheet', 'roll', 'set']),
            'particulars' => fake()->sentence(),
            'unit_price' => MoneyContext::toStorage(MoneyContext::ofMinor($unitPriceCents)),
            'class_id' => ProductClass::factory(),
            'subclass_id' => fn (array $attributes): int => ProductSubclass::factory()
                ->create(['product_class_id' => $attributes['class_id']])
                ->getKey(),
        ];
    }
}
