<?php

namespace App\Rules;

use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The validation rules of the inventory category forms.
 *
 * The rules live in one place so the create form and the edit form agree on
 * what a valid category is.
 */
class CategoryRules
{
    /**
     * The rules of the details of an inventory category.
     *
     * A category name is unique among the active categories, so a category
     * that was archived does not block the name from being used again.
     *
     * @param  Category|null  $category  The record that is being changed, if any.
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public static function details(?Category $category = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->ignore($category?->getKey())
                    ->whereNull('deleted_at'),
            ],
        ];
    }
}
