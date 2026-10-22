<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Writes the inventory items and the stock movements that explain their
 * quantities.
 *
 * A quantity is never changed in place. Every change is written through a
 * movement that keeps the quantity before, the change and the quantity after,
 * inside the same transaction as the item itself.
 */
class InventoryService
{
    /**
     * Create an inventory item together with its opening movement.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $userId = null): Inventory
    {
        return DB::transaction(function () use ($data, $userId): Inventory {
            $quantity = (int) ($data['current_quantity'] ?? 0);

            $inventory = Inventory::create([
                'item_name' => $data['item_name'],
                'description' => $this->nullableString($data['description'] ?? null),
                'categories_id' => (int) $data['categories_id'],
                'subcategories_id' => (int) $data['subcategories_id'],
                'price' => $this->nullableString($data['price'] ?? null),
                'size' => $this->nullableString($data['size'] ?? null),
                'color' => $this->nullableString($data['color'] ?? null),
                'type' => $this->nullableString($data['type'] ?? null),
                'current_quantity' => (string) $quantity,
            ]);

            $this->recordMovement(
                $inventory,
                InventoryMovementType::Opening,
                0,
                $quantity,
                __('Opening balance of the item.'),
                $userId,
            );

            return $inventory;
        });
    }

    /**
     * Change the details of an inventory item.
     *
     * The quantity is deliberately not written here. It only changes through
     * an adjustment, so every change of stock carries a reason.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Inventory $inventory, array $data): Inventory
    {
        $inventory->fill([
            'item_name' => $data['item_name'],
            'description' => $this->nullableString($data['description'] ?? null),
            'categories_id' => (int) $data['categories_id'],
            'subcategories_id' => (int) $data['subcategories_id'],
            'price' => $this->nullableString($data['price'] ?? null),
            'size' => $this->nullableString($data['size'] ?? null),
            'color' => $this->nullableString($data['color'] ?? null),
            'type' => $this->nullableString($data['type'] ?? null),
        ]);

        $inventory->save();

        return $inventory;
    }

    /**
     * Set the counted quantity of an item and record why it changed.
     */
    public function adjustQuantity(
        Inventory $inventory,
        int $newQuantity,
        string $notes,
        ?int $userId = null,
    ): InventoryMovement {
        if ($newQuantity < 0) {
            throw ValidationException::withMessages([
                'new_quantity' => __('The quantity may not be below zero.'),
            ]);
        }

        if (trim($notes) === '') {
            throw ValidationException::withMessages([
                'adjustment_notes' => __('A reason is required to change the quantity.'),
            ]);
        }

        $quantityBefore = $inventory->quantity();

        if ($newQuantity === $quantityBefore) {
            throw ValidationException::withMessages([
                'new_quantity' => __('The new quantity has to differ from the current quantity.'),
            ]);
        }

        return DB::transaction(function () use ($inventory, $newQuantity, $quantityBefore, $notes, $userId): InventoryMovement {
            $inventory->forceFill([
                'current_quantity' => (string) $newQuantity,
            ])->save();

            return $this->recordMovement(
                $inventory,
                InventoryMovementType::Adjustment,
                $quantityBefore,
                $newQuantity,
                $notes,
                $userId,
            );
        });
    }

    /**
     * Remove an item from the active list.
     *
     * An item that a withdrawal slip still names cannot be removed, because
     * the slip would lose the item it withdrew. The item is archived instead
     * of deleted, so its stock movements stay readable.
     */
    public function delete(Inventory $inventory): void
    {
        if ($inventory->withdrawalSlips()->exists()) {
            throw ValidationException::withMessages([
                'inventory' => __('This item was already withdrawn and cannot be removed. Keep the item so the withdrawal history stays complete.'),
            ]);
        }

        $inventory->delete();
    }

    /**
     * Write a movement and attach the account that recorded it.
     */
    protected function recordMovement(
        Inventory $inventory,
        InventoryMovementType $type,
        int $quantityBefore,
        int $quantityAfter,
        ?string $notes,
        ?int $userId,
    ): InventoryMovement {
        return $inventory->movements()->create([
            'type' => $type,
            'quantity' => $quantityAfter - $quantityBefore,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'notes' => $notes,
            'created_by' => $this->actorId($userId),
        ]);
    }

    /**
     * The account that is recorded as the author of a movement.
     */
    protected function actorId(?int $userId): ?int
    {
        if ($userId !== null) {
            return $userId;
        }

        $actorId = auth()->id();

        return $actorId === null ? null : (int) $actorId;
    }

    /**
     * An empty value is stored as nothing instead of an empty text.
     */
    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
