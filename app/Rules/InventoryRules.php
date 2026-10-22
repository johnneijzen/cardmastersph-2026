<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * The validation rules of the inventory forms.
 *
 * The rules live in one place so the create form, the edit form and the stock
 * adjustment agree on what a valid inventory item is.
 */
class InventoryRules
{
    /**
     * The rules of the details of an inventory item.
     *
     * The quantity is not part of the details. It is set when the item is
     * created and changed afterwards through a stock adjustment only.
     *
     * @param  int|string|null  $categoryId  The category the subcategory has to belong to.
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public static function item(int|string|null $categoryId = null): array
    {
        return [
            'item_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'categories_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
            'subcategories_id' => [
                'required',
                'integer',
                self::subcategoryInCategory($categoryId),
            ],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'size' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * The rules of the opening quantity of a new item.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public static function openingQuantity(): array
    {
        return [
            'current_quantity' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ];
    }

    /**
     * The rules of a stock adjustment.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public static function adjustment(): array
    {
        return [
            'new_quantity' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'adjustment_notes' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * The rule that keeps the subcategory inside the chosen category.
     *
     * The subcategories that were migrated from the legacy application do not
     * belong to a category yet. They stay selectable until they are classified,
     * so an existing inventory item can still be saved.
     */
    protected static function subcategoryInCategory(int|string|null $categoryId): Exists
    {
        $rule = Rule::exists('subcategories', 'id')->whereNull('deleted_at');

        if ($categoryId === null || $categoryId === '') {
            return $rule;
        }

        return $rule->where(fn (Builder $query) => $query
            ->where('categories_id', $categoryId)
            ->orWhereNull('categories_id'));
    }
}
