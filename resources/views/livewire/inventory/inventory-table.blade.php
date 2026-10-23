<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ __('Inventory') }}</flux:heading>
            <flux:text class="mt-1">{{ __('The materials and the stock the job orders are produced from.') }}</flux:text>
        </div>

        @can('create', App\Models\Inventory::class)
            <flux:button :href="route('inventory.create')" variant="primary" icon="plus">
                {{ __('Add item') }}
            </flux:button>
        @endcan

        @can('import', App\Models\Inventory::class)
            <flux:button :href="route('inventory.import')" variant="ghost" icon="arrow-up-tray">
                {{ __('Import') }}
            </flux:button>
        @endcan
    </header>

    @include('livewire-tables::datatable')

    <flux:modal wire:model="showDeleteModal" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Remove the inventory item?') }}</flux:heading>
                <flux:text>
                    {{ __(':name leaves the inventory. The stock movements and the withdrawal history are kept.', ['name' => $this->deletingInventory?->item_name ?? '']) }}
                </flux:text>
            </div>

            @error('inventory')
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:text>{{ $message }}</flux:text>
                </flux:callout>
            @enderror

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteInventory" wire:loading.attr="disabled">
                    {{ __('Remove') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
