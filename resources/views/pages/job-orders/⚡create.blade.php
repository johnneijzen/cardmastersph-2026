<?php

use App\Enums\JobOrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\ClientContactPerson;
use App\Models\Employee;
use App\Models\JobOrder;
use App\Models\JobOrderAgent;
use App\Models\ProductClass;
use App\Models\ProductSubclass;
use App\Rules\JobOrderRules;
use App\Services\JobOrderPricingService;
use App\Services\JobOrderService;
use App\Support\Money\MoneyContext;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add job order')] class extends Component
{
    public string $client_id = '';

    public string $contact_person_id = '';

    public string $agent_id = '';

    public string $employee_id = '';

    public string $order_date = '';

    public string $due_date = '';

    public string $status = '';

    public string $job_order_rating = '1';

    public string $down_payment = '0';

    public string $purchase_order_id = '';

    public string $delivery_receipt_id = '';

    public string $billing_statement_id = '';

    public string $overall_remarks = '';

    /**
     * The line items of the job order.
     *
     * @var array<int, array{name: string, particulars: string, unit: string, quantity: string, unit_price: string, product_class_id: string, subclass_id: string}>
     */
    public array $orderProducts = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('create', JobOrder::class);

        $this->order_date = now()->toDateString();
        $this->due_date = now()->addDays(7)->toDateString();
        $this->status = JobOrderStatus::InProduction->value;
        $this->orderProducts = [$this->newLine()];
    }

    /**
     * The active clients a job order can be created for.
     *
     * @return Collection<int, Client>
     */
    #[Computed]
    public function clients(): Collection
    {
        return Client::query()->orderBy('name')->get();
    }

    /**
     * The contract persons of the chosen client.
     *
     * @return Collection<int, ClientContactPerson>
     */
    #[Computed]
    public function contactPersons(): Collection
    {
        if ($this->client_id === '') {
            return new Collection;
        }

        return ClientContactPerson::query()
            ->where('client_id', $this->client_id)
            ->orderBy('contact_person')
            ->get();
    }

    /**
     * The agents a job order can come through.
     *
     * @return Collection<int, JobOrderAgent>
     */
    #[Computed]
    public function agents(): Collection
    {
        return JobOrderAgent::query()->orderBy('name')->get();
    }

    /**
     * The employees that can be the artist in charge.
     *
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function employees(): Collection
    {
        return Employee::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * The active classes a line item can be classified in.
     *
     * @return Collection<int, ProductClass>
     */
    #[Computed]
    public function classes(): Collection
    {
        return ProductClass::query()->orderBy('name')->get();
    }

    /**
     * The active subclasses, grouped by the class they belong to.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, ProductSubclass>>
     */
    #[Computed]
    public function subclasses(): \Illuminate\Support\Collection
    {
        return ProductSubclass::query()
            ->whereNotNull('product_class_id')
            ->orderBy('name')
            ->get()
            ->groupBy('product_class_id');
    }

    /**
     * The statuses a job order can have.
     *
     * @return array<int, JobOrderStatus>
     */
    #[Computed]
    public function statuses(): array
    {
        return JobOrderStatus::cases();
    }

    /**
     * Forget the contract person when another client is chosen.
     */
    public function updatedClientId(): void
    {
        $this->contact_person_id = '';
    }

    /**
     * Forget the subclass when the class of a line item changes.
     */
    public function classChanged(int $index): void
    {
        $this->orderProducts[$index]['subclass_id'] = '';
    }

    /**
     * Add a line item.
     */
    public function addLineItem(): void
    {
        $this->orderProducts[] = $this->newLine();
    }

    /**
     * Remove a line item.
     */
    public function removeLineItem(int $index): void
    {
        unset($this->orderProducts[$index]);

        $this->orderProducts = array_values($this->orderProducts);
    }

    /**
     * The amount of a line item, formatted for display.
     */
    public function lineAmount(int $index): string
    {
        return MoneyContext::format($this->lineTotal($index));
    }

    /**
     * The billed amount of all line items.
     */
    #[Computed]
    public function totalAmount(): Money
    {
        $total = MoneyContext::zero();

        foreach (array_keys($this->orderProducts) as $index) {
            $total = $total->plus($this->lineTotal($index));
        }

        return $total;
    }

    /**
     * The outstanding balance that the job order will have.
     */
    #[Computed]
    public function balanceAmount(): Money
    {
        return $this->totalAmount()->minus($this->downPaymentAmount());
    }

    /**
     * The payment status that follows from the balance.
     */
    #[Computed]
    public function paymentStatus(): PaymentStatus
    {
        return app(JobOrderPricingService::class)->paymentStatus(
            $this->totalAmount(),
            $this->balanceAmount(),
        );
    }

    /**
     * Store the job order.
     */
    public function save(JobOrderService $jobOrders): void
    {
        Gate::authorize('create', JobOrder::class);

        $validated = $this->validate(
            [
                ...JobOrderRules::details($this->client_id),
                ...JobOrderRules::items(),
            ],
            JobOrderRules::messages(),
            JobOrderRules::attributes(),
        );

        $jobOrder = $jobOrders->create($validated, auth()->id() === null ? null : (int) auth()->id());

        $this->redirect(route('job-orders.show', $jobOrder), navigate: true);
    }

    /**
     * A blank line item.
     *
     * @return array{name: string, particulars: string, unit: string, quantity: string, unit_price: string, product_class_id: string, subclass_id: string}
     */
    protected function newLine(): array
    {
        return [
            'name' => '',
            'particulars' => '',
            'unit' => '',
            'quantity' => '1',
            'unit_price' => '',
            'product_class_id' => '',
            'subclass_id' => '',
        ];
    }

    /**
     * The exact amount of a line item.
     */
    protected function lineTotal(int $index): Money
    {
        $line = $this->orderProducts[$index] ?? [];
        $quantity = (int) ($line['quantity'] ?? 0);
        $unitPrice = trim((string) ($line['unit_price'] ?? ''));

        if ($quantity < 1 || preg_match('/^\d{1,11}(\.\d{1,2})?$/', $unitPrice) !== 1) {
            return MoneyContext::zero();
        }

        return app(JobOrderPricingService::class)->lineAmount($unitPrice, $quantity);
    }

    /**
     * The down payment that was entered.
     */
    protected function downPaymentAmount(): Money
    {
        $downPayment = trim($this->down_payment);

        if (preg_match('/^\d{1,11}(\.\d{1,2})?$/', $downPayment) !== 1) {
            return MoneyContext::zero();
        }

        return MoneyContext::of($downPayment);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header>
        <flux:heading level="1" size="xl">{{ __('Add job order') }}</flux:heading>
        <flux:text class="mt-1">{{ __('The client order, its invoice and the work that has to be done.') }}</flux:text>
    </header>

    <form wire:submit="save" class="space-y-8">
        <flux:card>
            <flux:heading size="lg">{{ __('Client information') }}</flux:heading>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <flux:select
                    wire:model.live="client_id"
                    :label="__('Client')"
                    :error="$errors->first('client_id')"
                >
                    <option value="">{{ __('Choose a client') }}</option>
                    @foreach ($this->clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select
                    wire:model="contact_person_id"
                    :label="__('Contract person')"
                    :error="$errors->first('contact_person_id')"
                    :disabled="$client_id === ''"
                >
                    <option value="">{{ $client_id === '' ? __('Choose a client first') : __('Choose a contract person') }}</option>
                    @foreach ($this->contactPersons as $contactPerson)
                        <option value="{{ $contactPerson->id }}">{{ $contactPerson->contact_person }}</option>
                    @endforeach
                </flux:select>

                <flux:select
                    wire:model="agent_id"
                    :label="__('Agent')"
                    :error="$errors->first('agent_id')"
                >
                    <option value="">{{ __('Choose an agent') }}</option>
                    @foreach ($this->agents as $agent)
                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select
                    wire:model="employee_id"
                    :label="__('Artist in charge')"
                    :error="$errors->first('employee_id')"
                >
                    <option value="">{{ __('Choose an employee') }}</option>
                    @foreach ($this->employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                    @endforeach
                </flux:select>

                <flux:select
                    wire:model="job_order_rating"
                    :label="__('Rating')"
                    :error="$errors->first('job_order_rating')"
                >
                    @for ($rating = 1; $rating <= 10; $rating++)
                        <option value="{{ $rating }}">{{ $rating }}</option>
                    @endfor
                </flux:select>

                <flux:select
                    wire:model="status"
                    :label="__('Status')"
                    :error="$errors->first('status')"
                >
                    @foreach ($this->statuses as $jobOrderStatus)
                        <option value="{{ $jobOrderStatus->value }}">{{ $jobOrderStatus->label() }}</option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">{{ __('Schedule and references') }}</flux:heading>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model="order_date"
                    type="date"
                    :label="__('Order date')"
                    :error="$errors->first('order_date')"
                />

                <flux:input
                    wire:model="due_date"
                    type="date"
                    :label="__('Due date')"
                    :error="$errors->first('due_date')"
                />

                <flux:input
                    wire:model="purchase_order_id"
                    :label="__('Purchase order number')"
                    :error="$errors->first('purchase_order_id')"
                />

                <flux:input
                    wire:model="delivery_receipt_id"
                    :label="__('Delivery receipt number')"
                    :error="$errors->first('delivery_receipt_id')"
                />

                <flux:input
                    wire:model="billing_statement_id"
                    :label="__('Billing statement number')"
                    :error="$errors->first('billing_statement_id')"
                />
            </div>
        </flux:card>

        <flux:card>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg">{{ __('Line items') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Every line item names its quantity, its exact unit price and its classification.') }}</flux:text>
                </div>

                <flux:button type="button" variant="ghost" icon="plus" wire:click="addLineItem">
                    {{ __('Add line item') }}
                </flux:button>
            </div>

            @error('orderProducts')
                <flux:callout variant="danger" icon="exclamation-triangle" class="mt-4">
                    <flux:text>{{ $message }}</flux:text>
                </flux:callout>
            @enderror

            <div class="mt-6 space-y-4">
                @foreach ($orderProducts as $index => $item)
                    <div
                        class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700"
                        wire:key="job-order-line-{{ $index }}"
                    >
                        <div class="grid gap-3 sm:grid-cols-12">
                            <div class="sm:col-span-5">
                                <flux:input
                                    wire:model="orderProducts.{{ $index }}.name"
                                    :label="__('Product')"
                                    :error="$errors->first('orderProducts.'.$index.'.name')"
                                />
                            </div>

                            <div class="sm:col-span-5">
                                <flux:input
                                    wire:model="orderProducts.{{ $index }}.particulars"
                                    :label="__('Particulars')"
                                    :error="$errors->first('orderProducts.'.$index.'.particulars')"
                                />
                            </div>

                            <div class="sm:col-span-1">
                                <flux:input
                                    wire:model="orderProducts.{{ $index }}.unit"
                                    :label="__('Unit')"
                                    :error="$errors->first('orderProducts.'.$index.'.unit')"
                                />
                            </div>

                            <div class="flex items-end sm:col-span-1">
                                <flux:button
                                    type="button"
                                    variant="ghost"
                                    icon="trash"
                                    color="red"
                                    wire:click="removeLineItem({{ $index }})"
                                    :disabled="count($orderProducts) === 1"
                                    :aria-label="__('Remove the line item')"
                                />
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-12">
                            <div class="sm:col-span-4">
                                <flux:select
                                    wire:model.live="orderProducts.{{ $index }}.product_class_id"
                                    wire:change="classChanged({{ $index }})"
                                    :label="__('Class')"
                                    :error="$errors->first('orderProducts.'.$index.'.product_class_id')"
                                >
                                    <option value="">{{ __('Choose a class') }}</option>
                                    @foreach ($this->classes as $productClass)
                                        <option value="{{ $productClass->id }}">{{ $productClass->name }}</option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <div class="sm:col-span-4">
                                <flux:select
                                    wire:model="orderProducts.{{ $index }}.subclass_id"
                                    :label="__('Subclass')"
                                    :error="$errors->first('orderProducts.'.$index.'.subclass_id')"
                                    :disabled="($item['product_class_id'] ?? '') === ''"
                                >
                                    <option value="">{{ __('Choose a subclass') }}</option>
                                    @foreach ($this->subclasses->get((int) ($item['product_class_id'] ?? 0), collect()) as $subclass)
                                        <option value="{{ $subclass->id }}">{{ $subclass->name }}</option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <div class="sm:col-span-1">
                                <flux:input
                                    wire:model.live="orderProducts.{{ $index }}.quantity"
                                    type="number"
                                    min="1"
                                    step="1"
                                    :label="__('Quantity')"
                                    :error="$errors->first('orderProducts.'.$index.'.quantity')"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <flux:input
                                    wire:model.live="orderProducts.{{ $index }}.unit_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :label="__('Unit price')"
                                    :error="$errors->first('orderProducts.'.$index.'.unit_price')"
                                />
                            </div>

                            <div class="flex flex-col justify-end sm:col-span-1">
                                <flux:text size="xs" class="text-zinc-500">{{ __('Amount') }}</flux:text>
                                <span class="text-sm font-medium">{{ $this->lineAmount($index) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">{{ __('Invoice summary') }}</flux:heading>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model.live="down_payment"
                    type="number"
                    min="0"
                    step="0.01"
                    :label="__('Down payment')"
                    :error="$errors->first('down_payment')"
                />

                <flux:textarea
                    wire:model="overall_remarks"
                    :label="__('Overall remarks')"
                    :error="$errors->first('overall_remarks')"
                    rows="3"
                />
            </div>

            <dl class="mt-6 grid gap-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Total amount') }}</dt>
                    <dd class="font-semibold">{{ \App\Support\Money\MoneyContext::format($this->totalAmount()) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Balance') }}</dt>
                    <dd class="font-semibold">{{ \App\Support\Money\MoneyContext::format($this->balanceAmount()) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Payment status') }}</dt>
                    <dd class="font-semibold">{{ $this->paymentStatus()->label() }}</dd>
                </div>
            </dl>
        </flux:card>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <flux:button :href="route('job-orders.index')" variant="ghost"> {{ __('Cancel') }} </flux:button>

            <flux:button variant="primary" type="submit" icon="check"> {{ __('Save the job order') }} </flux:button>
        </div>
    </form>
</div>
