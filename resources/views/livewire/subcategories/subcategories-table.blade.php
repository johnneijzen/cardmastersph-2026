<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ __('Subcategories') }}</flux:heading>
            <flux:text class="mt-1">{{ __('The finer groups the inventory items are classified in.') }}</flux:text>
        </div>

        @can('create', App\Models\Subcategory::class)
            <flux:button :href="route('subcategories.create')" variant="primary" icon="plus">
                {{ __('Add subcategory') }}
            </flux:button>
        @endcan
    </header>

    @include('livewire-tables::datatable')

    <flux:modal wire:model="showDeleteModal" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Remove the subcategory?') }}</flux:heading>
                <flux:text>
                    {{ __(':name leaves the list. A subcategory that still holds inventory items cannot be removed.', ['name' => $this->deletingSubcategory?->name ?? '']) }}
                </flux:text>
            </div>

            @error('subcategory')
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:text>{{ $message }}</flux:text>
                </flux:callout>
            @enderror

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteSubcategory" wire:loading.attr="disabled">
                    {{ __('Remove') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
