<?php

namespace App\Rules;

use App\Enums\JobOrderStatus;
use App\Models\ClientContactPerson;
use App\Models\JobOrder;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/**
 * The validation rules of the job order forms.
 *
 * A job order always names an active client, one of the contract persons of
 * that client, the agent it came through and the employee that is responsible
 * for the work. Every line item names its class, its subclass and an exact
 * unit price, and the subclass has to belong to the class.
 */
class JobOrderRules
{
    /**
     * The rules of the details of a job order.
     *
     * @param  int|string|null  $clientId  The client that is chosen in the form.
     * @param  JobOrder|null  $jobOrder  The order that is being changed, if any.
     * @return array<string, array<int, ValidationRule|Closure|string>>
     */
    public static function details(int|string|null $clientId = null, ?JobOrder $jobOrder = null): array
    {
        return [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where(function (Builder $query) use ($jobOrder): void {
                    $query->whereNull('deleted_at');

                    $currentClientId = $jobOrder?->order?->client_id;

                    if ($currentClientId !== null) {
                        $query->orWhere('id', $currentClientId);
                    }
                }),
            ],
            'contact_person_id' => [
                'required',
                'integer',
                'exists:client_contact_persons,id',
                self::contactPersonBelongsToClient($clientId),
            ],
            'agent_id' => ['required', 'integer', 'exists:job_order_agents,id'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'order_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:order_date'],
            'status' => ['required', Rule::enum(JobOrderStatus::class)],
            'job_order_rating' => ['required', 'integer', 'between:1,10'],
            'down_payment' => ['required', new MonetaryAmount],
            'purchase_order_id' => ['nullable', 'string', 'max:255'],
            'delivery_receipt_id' => ['nullable', 'string', 'max:255'],
            'billing_statement_id' => ['nullable', 'string', 'max:255'],
            'overall_remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * The rules of the line items of a job order.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public static function items(): array
    {
        return [
            'orderProducts' => ['required', 'array', 'min:1', 'max:100'],
            'orderProducts.*' => [new SubclassBelongsToClass],
            'orderProducts.*.id' => ['nullable', 'integer'],
            'orderProducts.*.name' => ['required', 'string', 'max:255'],
            'orderProducts.*.particulars' => ['nullable', 'string', 'max:255'],
            'orderProducts.*.unit' => ['nullable', 'string', 'max:50'],
            'orderProducts.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'orderProducts.*.unit_price' => ['required', new MonetaryAmount],
            'orderProducts.*.product_class_id' => ['required', 'integer', 'exists:product_classes,id'],
            'orderProducts.*.subclass_id' => ['required', 'integer', 'exists:product_subclasses,id'],
        ];
    }

    /**
     * The validation messages of the job order form.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'orderProducts.required' => __('A job order needs at least one line item.'),
            'orderProducts.min' => __('A job order needs at least one line item.'),
            'orderProducts.*.quantity.min' => __('Every line item needs a quantity of at least one.'),
            'orderProducts.*.unit_price.required' => __('Every line item needs a unit price.'),
        ];
    }

    /**
     * The labels of the job order fields.
     *
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'client_id' => __('client'),
            'contact_person_id' => __('contract person'),
            'agent_id' => __('agent'),
            'employee_id' => __('artist in charge'),
            'order_date' => __('order date'),
            'due_date' => __('due date'),
            'job_order_rating' => __('rating'),
            'down_payment' => __('down payment'),
            'orderProducts.*.name' => __('product'),
            'orderProducts.*.quantity' => __('quantity'),
            'orderProducts.*.unit_price' => __('unit price'),
            'orderProducts.*.product_class_id' => __('class'),
            'orderProducts.*.subclass_id' => __('subclass'),
        ];
    }

    /**
     * The rule that keeps the contract person inside the chosen client.
     */
    protected static function contactPersonBelongsToClient(int|string|null $clientId): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($clientId): void {
            if ($clientId === null || $clientId === '') {
                return;
            }

            $belongsToClient = ClientContactPerson::query()
                ->whereKey($value)
                ->where('client_id', $clientId)
                ->exists();

            if (! $belongsToClient) {
                $fail(__('The contract person does not belong to the chosen client.'));
            }
        };
    }
}
