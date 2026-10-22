<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Subcategory;
use Illuminate\Validation\ValidationException;

/**
 * Writes the inventory categories.
 *
 * A category that still holds subcategories or inventory items is never
 * removed, because those records would lose their classification.
 */
class CategoryService
{
    /**
     * Create a category.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        return Category::create([
            'name' => trim((string) $data['name']),
        ]);
    }

    /**
     * Change the name of a category.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        $category->fill([
            'name' => trim((string) $data['name']),
        ]);

        $category->save();

        return $category;
    }

    /**
     * Remove a category from the active list.
     */
    public function delete(Category $category): void
    {
        if ($this->isUsedByASubcategory($category)) {
            throw ValidationException::withMessages([
                'category' => __('This category still holds subcategories and cannot be removed. Move the subcategories to another category first.'),
            ]);
        }

        if ($this->isUsedByAnInventoryItem($category)) {
            throw ValidationException::withMessages([
                'category' => __('This category still holds inventory items and cannot be removed. Move the items to another category first.'),
            ]);
        }

        $category->delete();
    }

    /**
     * Whether a subcategory still points at the category.
     */
    protected function isUsedByASubcategory(Category $category): bool
    {
        return Subcategory::withTrashed()
            ->where('categories_id', $category->getKey())
            ->exists();
    }

    /**
     * Whether an inventory item still points at the category.
     */
    protected function isUsedByAnInventoryItem(Category $category): bool
    {
        return Inventory::withTrashed()
            ->where('categories_id', $category->getKey())
            ->exists();
    }
}
