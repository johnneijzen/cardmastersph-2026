<?php

namespace App\Rules;

use App\Models\Subcategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/**
 * The validation rules of the inventory subcategory forms.
 *
 * The rules live in one place so the create form and the edit form agree on
 * what a valid subcategory is, and on which category it belongs to.
 */
class SubcategoryRules
{
    /**
     * The rules of the details of a subcategory.
     *
     * A subcategory name is unique inside its category, so two categories can
     * hold a subcategory with the same name.
     *
     * @param  int|string|null  $categoryId  The category the subcategory belongs to.
     * @param  Subcategory|null  $subcategory  The record that is being changed, if any.
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public static function details(int|string|null $categoryId = null, ?Subcategory $subcategory = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subcategories', 'name')
                    ->ignore($subcategory?->getKey())
                    ->whereNull('deleted_at')
                    ->where(fn (Builder $query) => $query->where('categories_id', $categoryId)),
            ],
            'categories_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
