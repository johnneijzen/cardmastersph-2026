<?php

namespace Database\Factories;

use App\Enums\InventoryMovementType;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantityBefore = fake()->numberBetween(0, 500);
        $quantity = fake()->numberBetween(0, 100);

        return [
            'inventory_id' => Inventory::factory(),
            'type' => InventoryMovementType::Adjustment,
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityBefore + $quantity,
            'notes' => fake()->sentence(),
            'created_by' => null,
        ];
    }
}
