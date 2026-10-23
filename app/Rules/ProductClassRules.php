<?php

namespace App\Rules;

use App\Models\ProductClass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The validation rules of the job order class forms.
 *
 * The rules live in one place so the create form and the edit form agree on
 * what a valid class is.
 */
class ProductClassRules
{
    /**
     * The rules of the details of a job order class.
     *
     * A class name is unique among the active classes, so a class that was
     * archived does not block the name from being used again.
     *
     * @param  ProductClass|null  $productClass  The record that is being changed, if any.
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public static function details(?ProductClass $productClass = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_classes', 'name')
                    ->ignore($productClass?->getKey())
                    ->whereNull('deleted_at'),
            ],
        ];
    }
}
