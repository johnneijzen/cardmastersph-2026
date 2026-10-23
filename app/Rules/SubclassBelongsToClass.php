<?php

namespace App\Rules;

use App\Models\ProductSubclass;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The validation rule of a job order line item.
 *
 * The chosen subclass has to belong to the chosen class. Archived classes and
 * subclasses stay valid, because a historical job order keeps the
 * classification it was created with.
 */
class SubclassBelongsToClass implements ValidationRule
{
    /**
     * Validate the given value.
     *
     * @param  array<string, mixed>  $value
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $line = (array) $value;

        $classId = (int) ($line['product_class_id'] ?? 0);
        $subclassId = (int) ($line['subclass_id'] ?? 0);

        if ($classId < 1 || $subclassId < 1) {
            return;
        }

        $belongsToClass = ProductSubclass::withTrashed()
            ->whereKey($subclassId)
            ->where('product_class_id', $classId)
            ->exists();

        if (! $belongsToClass) {
            $fail(__('The chosen subclass does not belong to the chosen class.'));
        }
    }
}
