<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Subcategory;
use Illuminate\Validation\ValidationException;

/**
 * Writes the inventory subcategories.
 *
 * A subcategory that still holds inventory items is never removed, because
 * the items would lose their classification.
 */
class SubcategoryService
{
    /**
     * Create a subcategory inside its category.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Subcategory
    {
        return Subcategory::create([
            'name' => trim((string) $data['name']),
            'categories_id' => (int) $data['categories_id'],
        ]);
    }

    /**
     * Change the name of a subcategory or the category it belongs to.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Subcategory $subcategory, array $data): Subcategory
    {
        $subcategory->fill([
            'name' => trim((string) $data['name']),
            'categories_id' => (int) $data['categories_id'],
        ]);

        $subcategory->save();

        return $subcategory;
    }

    /**
     * Remove a subcategory from the active list.
     */
    public function delete(Subcategory $subcategory): void
    {
        if ($this->isUsedByAnInventoryItem($subcategory)) {
            throw ValidationException::withMessages([
                'subcategory' => __('This subcategory still holds inventory items and cannot be removed. Move the items to another subcategory first.'),
            ]);
        }

        $subcategory->delete();
    }

    /**
     * Whether an inventory item still points at the subcategory.
     */
    protected function isUsedByAnInventoryItem(Subcategory $subcategory): bool
    {
        return Inventory::withTrashed()
            ->where('subcategories_id', $subcategory->getKey())
            ->exists();
    }
}
