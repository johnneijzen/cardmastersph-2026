<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\JobOrder;
use App\Models\WithdrawalSlip;
use App\Rules\WithdrawalSlipRules;
use App\Services\WithdrawalSlipService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add withdrawal slip')] class extends Component
{
    public string $date = '';

    public string $client_id = '';

    public string $job_order_id = '';

    public string $employee_id = '';

    /**
     * The items that are withdrawn on the slip.
     *
     * @var array<int, array{inventory_id: string, quantity: string, particulars: string}>
     */
    public array $items = [
        ['inventory_id' => '', 'quantity' => '1', 'particulars' => ''],
    ];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('create', WithdrawalSlip::class);

        $this->date = now()->toDateString();
        $this->employee_id = (string) (auth()->user()?->employee?->getKey() ?? '');
    }

    /**
     * The clients a slip can be issued for.
     *
     * @return Collection<int, Client>
     */
    #[Computed]
    public function clients(): Collection
    {
        return Client::query()->orderBy('name')->get();
    }

    /**
     * The job orders of the chosen client.
     *
     * @return Collection<int, JobOrder>
     */
    #[Computed]
    public function jobOrders(): Collection
    {
        if ($this->client_id === '') {
            return new Collection;
        }

        return JobOrder::query()
            ->with('order')
            ->whereHas('order', function ($orders): void {
                $orders->where('client_id', $this->client_id);
            })
            ->orderByDesc('id')
            ->get();
    }

    /**
     * The items that can be withdrawn.
     *
     * @return Collection<int, Inventory>
     */
    #[Computed]
    public function inventoryItems(): Collection
    {
        return Inventory::query()
            ->orderBy('item_name')
            ->get();
    }

    /**
     * The employees that may be responsible for the withdrawal.
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
     * Forget the job order when another client is chosen.
     */
    public function updatedClientId(): void
    {
        $this->job_order_id = '';
    }

    /**
     * Add a withdrawn item to the slip.
     */
    public function addItem(): void
    {
        $this->items[] = ['inventory_id' => '', 'quantity' => '1', 'particulars' => ''];
    }

    /**
     * Remove a withdrawn item from the slip.
     */
    public function removeItem(int $index): void
    {
        unset($this->items[$index]);

        $this->items = array_values($this->items);
    }

    /**
     * The quantity of an item that is still on hand.
     */
    public function availableQuantity(int|string $inventoryId): ?int
    {
        if ($inventoryId === '' || $inventoryId === null) {
            return null;
        }

        return $this->inventoryItems->firstWhere('id', (int) $inventoryId)?->quantity();
    }

    /**
     * The account that is recorded as the author of the slip.
     */
    protected function actorId(): ?int
    {
        $actorId = auth()->id();

        return $actorId === null ? null : (int) $actorId;
    }

    /**
     * Store the withdrawal slip and take the stock out of the inventory.
     */
    public function save(WithdrawalSlipService $slips): void
    {
        Gate::authorize('create', WithdrawalSlip::class);

        $validated = $this->validate(
            [
                ...WithdrawalSlipRules::details($this->client_id),
                ...WithdrawalSlipRules::items(),
            ],
            WithdrawalSlipRules::messages(),
            WithdrawalSlipRules::attributes(),
        );

        $slip = $slips->create($validated, $this->actorId());

        $this->redirect(route('withdrawal-slips.show', $slip), navigate: true);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header>
        <flux:heading level="1" size="xl">{{ __('Add withdrawal slip') }}</flux:heading>
        <flux:text class="mt-1">
            {{ __('The stock is taken out of the inventory as soon as the slip is saved.') }}
        </flux:text>
    </header>

    <form wire:submit="save" class="space-y-8">
        <flux:card>
            <flux:heading size="lg">{{ __('Withdrawal slip') }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model="date"
                    type="date"
                    :max="now()->toDateString()"
                    :label="__('Date')"
                    :error="$errors->first('date')"
                />

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
                    wire:model="job_order_id"
                    :label="__('Job order')"
                    :error="$errors->first('job_order_id')"
                    :disabled="$client_id === ''"
                >
                    <option value="">{{ $client_id === '' ? __('Choose a client first') : __('Choose a job order') }}</option>
                    @foreach ($this->jobOrders as $jobOrder)
                        <option value="{{ $jobOrder->id }}">#{{ $jobOrder->id }}</option>
                    @endforeach
                </flux:select>

                <flux:select
                    wire:model="employee_id"
                    :label="__('Responsible employee')"
                    :error="$errors->first('employee_id')"
                >
                    <option value="">{{ __('Choose an employee') }}</option>
                    @foreach ($this->employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                    @endforeach
                </flux:select>
            </div>

            @if ($client_id !== '' && $this->jobOrders->isEmpty())
                <flux:callout color="amber" icon="exclamation-triangle" class="mt-4">
                    <flux:text>{{ __('This client has no active job order to withdraw for.') }}</flux:text>
                </flux:callout>
            @endif
        </flux:card>

        <flux:card>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg">{{ __('Withdrawn items') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Every item may only be named once on the same slip.') }}</flux:text>
                </div>

                <flux:button type="button" variant="ghost" icon="plus" wire:click="addItem">
                    {{ __('Add item') }}
                </flux:button>
            </div>

            @error('items')
                <flux:callout variant="danger" icon="exclamation-triangle" class="mt-4">
                    <flux:text>{{ $message }}</flux:text>
                </flux:callout>
            @enderror

            <div class="mt-6 space-y-4">
                @foreach ($items as $index => $item)
                    <div
                        class="grid gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700 sm:grid-cols-12"
                        wire:key="withdrawn-item-{{ $index }}"
                    >
                        <div class="sm:col-span-5">
                            <flux:select
                                wire:model.live="items.{{ $index }}.inventory_id"
                                :label="__('Item')"
                                :error="$errors->first('items.'.$index.'.inventory_id')"
                            >
                                <option value="">{{ __('Choose an item') }}</option>
                                @foreach ($this->inventoryItems as $inventory)
                                    <option value="{{ $inventory->id }}">
                                        {{ $inventory->item_name }} ({{ $inventory->current_quantity ?? '0' }})
                                    </option>
                                @endforeach
                            </flux:select>

                            @if ($this->availableQuantity($item['inventory_id']) !== null)
                                <flux:text class="mt-1">
                                    {{ __('On hand: :quantity', ['quantity' => $this->availableQuantity($item['inventory_id'])]) }}
                                </flux:text>
                            @endif
                        </div>

                        <div class="sm:col-span-2">
                            <flux:input
                                wire:model="items.{{ $index }}.quantity"
                                type="number"
                                min="1"
                                step="1"
                                :label="__('Quantity')"
                                :error="$errors->first('items.'.$index.'.quantity')"
                            />
                        </div>

                        <div class="sm:col-span-4">
                            <flux:input
                                wire:model="items.{{ $index }}.particulars"
                                :label="__('Particulars')"
                                :error="$errors->first('items.'.$index.'.particulars')"
                            />
                        </div>

                        <div class="flex items-end sm:col-span-1">
                            <flux:button
                                type="button"
                                variant="ghost"
                                icon="trash"
                                color="red"
                                wire:click="removeItem({{ $index }})"
                                :disabled="count($items) === 1"
                                :aria-label="__('Remove the item')"
                            />
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <flux:button :href="route('withdrawal-slips.index')" variant="ghost"> {{ __('Cancel') }} </flux:button>

            <flux:button variant="primary" type="submit" icon="check"> {{ __('Save the slip') }} </flux:button>
        </div>
    </form>
</div>
