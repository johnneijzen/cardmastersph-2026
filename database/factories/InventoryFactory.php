<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'categories_id' => Category::factory(),
            'subcategories_id' => Subcategory::factory(),
            'price' => (string) fake()->randomFloat(2, 10, 5000),
            'size' => (string) fake()->numberBetween(1, 100),
            'color' => fake()->safeColorName(),
            'type' => fake()->randomElement(['Raw Material', 'Finished Goods', 'Consumable']),
            'current_quantity' => (string) fake()->numberBetween(0, 500),
        ];
    }

    /**
     * Indicate that the item is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_quantity' => '0',
        ]);
    }
}
