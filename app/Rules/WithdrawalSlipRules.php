<?php

namespace App\Rules;

use App\Models\JobOrder;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

/**
 * The validation rules of the withdrawal slip form.
 *
 * A slip always belongs to a client and to a job order of that client, and it
 * always names the employee that is responsible for the withdrawal. Every
 * withdrawn item has to be an item that is still in the inventory.
 */
class WithdrawalSlipRules
{
    /**
     * The rules of the details of a withdrawal slip.
     *
     * @param  int|string|null  $clientId  The client the job order has to belong to.
     * @return array<string, array<int, ValidationRule|Closure|string>>
     */
    public static function details(int|string|null $clientId = null): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'job_order_id' => [
                'required',
                'integer',
                Rule::exists('job_orders', 'id')->whereNull('deleted_at'),
                self::jobOrderBelongsToClient($clientId),
            ],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
        ];
    }

    /**
     * The rules of the withdrawn items.
     *
     * The same item may only be named once, so a quantity can never be
     * deducted twice on the same slip.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public static function items(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.inventory_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('inventory', 'id')->whereNull('deleted_at'),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'items.*.particulars' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * The validation messages of the withdrawal slip form.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'items.*.inventory_id.distinct' => __('An item may only be withdrawn once on the same slip.'),
            'items.*.quantity.required' => __('Every withdrawn item needs a quantity.'),
            'items.*.quantity.min' => __('The withdrawn quantity has to be at least one.'),
        ];
    }

    /**
     * The labels of the withdrawn item fields.
     *
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'items.*.inventory_id' => __('item'),
            'items.*.quantity' => __('quantity'),
            'items.*.particulars' => __('particulars'),
        ];
    }

    /**
     * The rule that keeps the job order inside the chosen client.
     */
    protected static function jobOrderBelongsToClient(int|string|null $clientId): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($clientId): void {
            if ($clientId === null || $clientId === '') {
                return;
            }

            $belongsToClient = JobOrder::query()
                ->whereKey($value)
                ->whereHas('order', function (Builder $orders) use ($clientId): void {
                    $orders->where('client_id', $clientId);
                })
                ->exists();

            if (! $belongsToClient) {
                $fail(__('The job order does not belong to the chosen client.'));
            }
        };
    }
}
