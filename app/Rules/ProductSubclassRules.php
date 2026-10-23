<?php

namespace App\Rules;

use App\Models\ProductSubclass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/**
 * The validation rules of the job order subclass forms.
 *
 * The rules live in one place so the create form and the edit form agree on
 * what a valid subclass is, and on which class it belongs to.
 */
class ProductSubclassRules
{
    /**
     * The rules of the details of a subclass.
     *
     * A subclass name is unique inside its class, so two classes can hold a
     * subclass with the same name. A new subclass has to belong to an active
     * class, while an existing subclass may keep the class that was archived
     * after the subclass was created.
     *
     * @param  int|string|null  $productClassId  The class the subclass belongs to.
     * @param  ProductSubclass|null  $productSubclass  The record that is being changed, if any.
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public static function details(int|string|null $productClassId = null, ?ProductSubclass $productSubclass = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_subclasses', 'name')
                    ->ignore($productSubclass?->getKey())
                    ->whereNull('deleted_at')
                    ->where(fn (Builder $query) => $query->where('product_class_id', $productClassId)),
            ],
            'product_class_id' => [
                'required',
                'integer',
                Rule::exists('product_classes', 'id')
                    ->where(fn (Builder $query) => $query
                        ->whereNull('deleted_at')
                        ->when(
                            $productSubclass?->product_class_id,
                            fn (Builder $query, int $currentClassId) => $query->orWhere('id', $currentClassId),
                        )),
            ],
        ];
    }
}
