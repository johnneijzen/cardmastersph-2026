<?php

namespace App\Rules;

use App\Support\Money\MoneyContext;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The validation rule of a monetary input of a job order.
 *
 * The amount has to be an exact decimal string that fits a `DECIMAL(13,2)`
 * column: at most two decimal places and at most eleven whole digits. The
 * amount is never read as a float.
 */
class MonetaryAmount implements ValidationRule
{
    /**
     * Validate the given value.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) && ! is_int($value)) {
            $fail(__('The :attribute has to be an amount without a currency symbol.'));

            return;
        }

        $amount = (string) $value;

        if (preg_match('/^\d{1,11}(\.\d{1,2})?$/', $amount) !== 1) {
            $fail(__('The :attribute has to be an amount with at most two decimal places.'));

            return;
        }

        $money = MoneyContext::of($amount);

        if ($money->isGreaterThan(MoneyContext::of('99999999999.99'))) {
            $fail(__('The :attribute is larger than the amount the application can store.'));
        }
    }
}
