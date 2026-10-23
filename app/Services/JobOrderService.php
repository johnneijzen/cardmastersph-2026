<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\CashReceipt;
use App\Models\JobOrder;
use App\Models\Order;
use App\Models\Product;
use App\Support\Money\MoneyContext;
use Brick\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Writes the job orders and their invoices.
 *
 * A job order is the invoice (`orders`), its line items (`products` with the
 * `orders_products` pivot) and the operational record (`job_orders`). The
 * three are written in one transaction, so a job order is never stored
 * without its lines or without its invoice.
 *
 * The amounts are calculated with Money objects and written as exact decimal
 * strings. The balance is always derived from the billed amount, the down
 * payment and the payments already received; it is never taken from the form.
 */
class JobOrderService
{
    public function __construct(
        private readonly JobOrderPricingService $pricing,
        private readonly NumberSequenceService $numbers,
    ) {}

    /**
     * Create a job order with its invoice and its line items.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $userId = null): JobOrder
    {
        $lines = $this->lines($data['orderProducts'] ?? []);
        $amounts = $this->amounts($lines, (string) $data['down_payment'], MoneyContext::zero());
        $orderDate = Carbon::parse((string) $data['order_date']);

        return DB::transaction(function () use ($data, $lines, $amounts, $orderDate, $userId): JobOrder {
            $order = Order::create([
                ...$this->orderAttributes($data, $amounts),
                'order_date' => $orderDate->toDateString(),
            ]);

            $jobOrder = JobOrder::create([
                'job_order_number' => $this->numbers->next('job-order', 'JO', $orderDate),
                'employee_id' => (int) $data['employee_id'],
                'order_id' => $order->getKey(),
                'job_order_rating' => (int) $data['job_order_rating'],
                'created_by' => $userId ?? Auth::id(),
                'updated_by' => $userId ?? Auth::id(),
            ]);

            $this->writeLines($order, $lines);

            return $jobOrder->load([
                'order.client',
                'order.client_contact_persons',
                'order.job_order_agent',
                'order.products',
                'employee',
            ]);
        });
    }

    /**
     * Change a job order and its line items.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(JobOrder $jobOrder, array $data, ?int $userId = null): JobOrder
    {
        $lines = $this->lines($data['orderProducts'] ?? []);

        return DB::transaction(function () use ($jobOrder, $data, $lines, $userId): JobOrder {
            $locked = JobOrder::query()
                ->whereKey($jobOrder->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $order = $locked->order()->firstOrFail();
            $amounts = $this->amounts(
                $lines,
                (string) $data['down_payment'],
                $this->paymentsOf($locked),
            );

            $order->fill([
                ...$this->orderAttributes($data, $amounts),
                'order_date' => Carbon::parse((string) $data['order_date'])->toDateString(),
            ])->save();

            $existing = $order->products()->get()->keyBy('id');
            $kept = [];

            foreach ($lines as $line) {
                $product = $line['id'] === null ? null : $existing->get($line['id']);

                if ($line['id'] !== null && $product === null) {
                    throw ValidationException::withMessages([
                        'orderProducts' => __('A line item does not belong to this job order.'),
                    ]);
                }

                $attributes = [
                    'name' => $line['name'],
                    'amount' => $line['amount'],
                    'particulars' => $line['particulars'],
                    'unit' => $line['unit'],
                    'unit_price' => $line['unit_price'],
                    'class_id' => $line['product_class_id'],
                    'subclass_id' => $line['subclass_id'],
                ];

                if ($product === null) {
                    $product = Product::create($attributes);
                } else {
                    $product->update($attributes);
                }

                $kept[$product->getKey()] = ['qty' => $line['quantity']];
            }

            $order->products()->sync($kept);

            $removed = array_diff($existing->keys()->all(), array_keys($kept));

            if ($removed !== []) {
                $removedProducts = Product::query()
                    ->whereKey($removed)
                    ->whereDoesntHave('orders')
                    ->get();

                foreach ($removedProducts as $removedProduct) {
                    $removedProduct->delete();
                }
            }

            $locked->fill([
                'employee_id' => (int) $data['employee_id'],
                'job_order_rating' => (int) $data['job_order_rating'],
                'updated_by' => $userId ?? Auth::id(),
            ])->save();

            return $locked->load([
                'order.client',
                'order.client_contact_persons',
                'order.job_order_agent',
                'order.products',
                'employee',
            ]);
        });
    }

    /**
     * Archive a job order that has no payment recorded against it.
     *
     * The invoice and its line items stay in the database, so the financial
     * history is not deleted. A job order with a cash receipt is refused,
     * because the payment has to be voided first.
     */
    public function archive(JobOrder $jobOrder): void
    {
        DB::transaction(function () use ($jobOrder): void {
            $locked = JobOrder::query()
                ->whereKey($jobOrder->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (CashReceipt::query()->where('job_order_id', $locked->getKey())->exists()) {
                throw ValidationException::withMessages([
                    'jobOrder' => __('This job order has a payment and cannot be archived. Void the cash receipt first.'),
                ]);
            }

            $locked->delete();
        });
    }

    /**
     * Read the line items from the form and calculate their exact amounts.
     *
     * @param  array<int|string, mixed>  $items
     * @return array<int, array{id: int|null, name: string, particulars: string|null, unit: string|null, quantity: int, unit_price: string, amount: string, product_class_id: int, subclass_id: int}>
     */
    protected function lines(array $items): array
    {
        $lines = [];

        foreach (array_values($items) as $index => $item) {
            $item = (array) $item;

            $quantity = (int) ($item['quantity'] ?? 0);
            $unitPrice = trim((string) ($item['unit_price'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));

            if ($quantity < 1) {
                throw ValidationException::withMessages([
                    "orderProducts.{$index}.quantity" => __('Every line item needs a quantity of at least one.'),
                ]);
            }

            if ($unitPrice === '' || $name === '') {
                throw ValidationException::withMessages([
                    "orderProducts.{$index}.name" => __('Every line item needs a product and a unit price.'),
                ]);
            }

            $lines[] = [
                'id' => isset($item['id']) && $item['id'] !== '' ? (int) $item['id'] : null,
                'name' => $name,
                'particulars' => $this->nullableString($item['particulars'] ?? null),
                'unit' => $this->nullableString($item['unit'] ?? null),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => MoneyContext::toStorage($this->pricing->lineAmount($unitPrice, $quantity)),
                'product_class_id' => (int) ($item['product_class_id'] ?? 0),
                'subclass_id' => (int) ($item['subclass_id'] ?? 0),
            ];
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'orderProducts' => __('A job order needs at least one line item.'),
            ]);
        }

        return $lines;
    }

    /**
     * Calculate the billed amount, the balance and the payment status.
     *
     * @param  array<int, array{unit_price: string, quantity: int}>  $lines
     * @return array{total: Money, down_payment: Money, balance: Money, payment_status: PaymentStatus}
     */
    protected function amounts(array $lines, string $downPaymentInput, Money $payments): array
    {
        $total = $this->pricing->total($lines);
        $downPayment = MoneyContext::of($downPaymentInput === '' ? '0' : $downPaymentInput);

        if ($downPayment->isGreaterThan($total)) {
            throw ValidationException::withMessages([
                'down_payment' => __('The down payment may not be more than the total amount.'),
            ]);
        }

        $balance = $this->pricing->balance($total, $downPayment, $payments);

        return [
            'total' => $total,
            'down_payment' => $downPayment,
            'balance' => $balance,
            'payment_status' => $this->pricing->paymentStatus($total, $balance),
        ];
    }

    /**
     * The invoice attributes that are shared by the create and the update.
     *
     * @param  array<string, mixed>  $data
     * @param  array{total: Money, down_payment: Money, balance: Money, payment_status: PaymentStatus}  $amounts
     * @return array<string, mixed>
     */
    protected function orderAttributes(array $data, array $amounts): array
    {
        return [
            'client_id' => (int) $data['client_id'],
            'contact_person_id' => (int) $data['contact_person_id'],
            'agent_id' => (int) $data['agent_id'],
            'due_date' => Carbon::parse((string) $data['due_date'])->toDateString(),
            'status' => (string) $data['status'],
            'total_amount' => MoneyContext::toStorage($amounts['total']),
            'down_payment' => MoneyContext::toStorage($amounts['down_payment']),
            'balance' => MoneyContext::toStorage($amounts['balance']),
            'payment_status' => $amounts['payment_status']->value,
            'overall_remarks' => $this->nullableString($data['overall_remarks'] ?? null),
            'purchase_order_id' => $this->nullableString($data['purchase_order_id'] ?? null),
            'delivery_receipt_id' => $this->nullableString($data['delivery_receipt_id'] ?? null),
            'billing_statement_id' => $this->nullableString($data['billing_statement_id'] ?? null),
        ];
    }

    /**
     * Write the line items of the invoice.
     *
     * @param  array<int, array{id: int|null, name: string, particulars: string|null, unit: string|null, quantity: int, unit_price: string, amount: string, product_class_id: int, subclass_id: int}>  $lines
     */
    protected function writeLines(Order $order, array $lines): void
    {
        foreach ($lines as $line) {
            $product = Product::create([
                'name' => $line['name'],
                'amount' => $line['amount'],
                'particulars' => $line['particulars'],
                'unit' => $line['unit'],
                'unit_price' => $line['unit_price'],
                'class_id' => $line['product_class_id'],
                'subclass_id' => $line['subclass_id'],
            ]);

            $order->products()->attach($product->getKey(), ['qty' => $line['quantity']]);
        }
    }

    /**
     * The payments that are already recorded against the job order.
     */
    protected function paymentsOf(JobOrder $jobOrder): Money
    {
        return $this->pricing->sum(
            CashReceipt::query()
                ->where('job_order_id', $jobOrder->getKey())
                ->pluck('amount')
                ->all(),
        );
    }

    /**
     * Trim a value that may be empty to `null`.
     */
    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
