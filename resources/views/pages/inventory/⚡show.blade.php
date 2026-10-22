<?php

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Rules\InventoryRules;
use App\Services\InventoryService;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Inventory item')] class extends Component
{
    #[Locked]
    public Inventory $inventory;

    public string $new_quantity = '';

    public string $adjustment_notes = '';

    /**
     * Mount the component.
     */
    public function mount(Inventory $inventory): void
    {
        Gate::authorize('view', $inventory);

        $this->inventory = $inventory->loadMissing(['category', 'subcategory']);
    }

    /**
     * The stock movements that explain the quantity of the item.
     *
     * @return Collection<int, InventoryMovement>
     */
    #[Computed]
    public function movements(): Collection
    {
        return $this->inventory->movements()
            ->with('createdBy')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Record the counted quantity of the item.
     */
    public function adjustQuantity(InventoryService $inventory): void
    {
        Gate::authorize('update', $this->inventory);

        $validated = $this->validate(InventoryRules::adjustment());

        $inventory->adjustQuantity(
            $this->inventory,
            (int) $validated['new_quantity'],
            (string) $validated['adjustment_notes'],
            auth()->id(),
        );

        $this->inventory->refresh();
        unset($this->movements);

        $this->new_quantity = '';
        $this->adjustment_notes = '';

        Flux::toast(variant: 'success', text: __('The stock was adjusted.'));
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ $inventory->item_name }}</flux:heading>
            <flux:text class="mt-1">
                {{ $inventory->category?->name ?? __('Without a category') }}
                @if ($inventory->subcategory)
                    · {{ $inventory->subcategory->name }}
                @endif
            </flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('update', $inventory)
                <flux:button :href="route('inventory.edit', $inventory)" variant="primary" icon="pencil-square">
                    {{ __('Edit') }}
                </flux:button>
            @endcan

            <flux:button :href="route('inventory.index')" variant="ghost">
                {{ __('Back to the list') }}
            </flux:button>
        </div>
    </header>

    <div class="grid gap-4 lg:grid-cols-2">
        <flux:card>
            <flux:heading size="lg" class="mb-4">{{ __('Item information') }}</flux:heading>

            <dl class="grid gap-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Description') }}</dt>
                    <dd class="font-medium">{{ $inventory->description ?: '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Category') }}</dt>
                    <dd class="font-medium">{{ $inventory->category?->name ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Subcategory') }}</dt>
                    <dd class="font-medium">{{ $inventory->subcategory?->name ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Price') }}</dt>
                    <dd class="font-medium">
                        {{ $inventory->price === null || $inventory->price === '' ? '—' : number_format((float) $inventory->price, 2) }}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Size') }}</dt>
                    <dd class="font-medium">{{ $inventory->size ?: '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Color') }}</dt>
                    <dd class="font-medium">{{ $inventory->color ?: '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Type') }}</dt>
                    <dd class="font-medium">{{ $inventory->type ?: '—' }}</dd>
                </div>
            </dl>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-4">{{ __('Stock') }}</flux:heading>

            <div class="flex items-end justify-between gap-4">
                <div>
                    <flux:text>{{ __('Quantity on hand') }}</flux:text>
                    <p class="text-3xl font-semibold">{{ $inventory->current_quantity ?? '—' }}</p>
                </div>

                <flux:badge :color="$inventory->quantity() > 0 ? 'lime' : 'red'" size="lg">
                    {{ $inventory->quantity() > 0 ? __('In stock') : __('Out of stock') }}
                </flux:badge>
            </div>

            @unless ($inventory->quantityIsNumeric())
                <flux:callout color="amber" icon="exclamation-triangle" class="mt-4">
                    <flux:text>
                        {{ __('This quantity was carried over from the legacy application and is not a whole number. Set the counted quantity below to correct it.') }}
                    </flux:text>
                </flux:callout>
            @endunless

            @can('update', $inventory)
                <form wire:submit="adjustQuantity" class="mt-6 space-y-4 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <flux:heading size="sm">{{ __('Adjust the stock') }}</flux:heading>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:input
                            wire:model="new_quantity"
                            type="number"
                            step="1"
                            min="0"
                            :label="__('Counted quantity')"
                            :error="$errors->first('new_quantity')"
                        />

                        <flux:input
                            wire:model="adjustment_notes"
                            :label="__('Reason')"
                            :error="$errors->first('adjustment_notes')"
                        />
                    </div>

                    <div class="flex justify-end">
                        <flux:button variant="primary" type="submit"> {{ __('Record adjustment') }} </flux:button>
                    </div>
                </form>
            @endcan
        </flux:card>
    </div>

    <flux:card>
        <flux:heading size="lg" class="mb-4">{{ __('Stock history') }}</flux:heading>

        @if ($this->movements->isEmpty())
            <flux:text>{{ __('No stock movement has been recorded for this item yet.') }}</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Movement') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Change') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Quantity') }}</flux:table.column>
                    <flux:table.column>{{ __('Reason') }}</flux:table.column>
                    <flux:table.column>{{ __('Recorded by') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->movements as $movement)
                        <flux:table.row :key="$movement->id">
                            <flux:table.cell>
                                {{ $movement->created_at?->format('d M Y H:i') ?? '—' }}
                            </flux:table.cell>
                            <flux:table.cell>{{ $movement->type->label() }}</flux:table.cell>
                            <flux:table.cell align="end">
                                {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                {{ $movement->quantity_before }} → {{ $movement->quantity_after }}
                            </flux:table.cell>
                            <flux:table.cell>{{ $movement->notes ?: '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $movement->createdBy?->name ?? __('System') }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>
</div>
