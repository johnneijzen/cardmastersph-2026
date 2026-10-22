<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ __('Categories') }}</flux:heading>
            <flux:text class="mt-1">{{ __('The groups the inventory items are classified in.') }}</flux:text>
        </div>

        @can('create', App\Models\Category::class)
            <flux:button :href="route('categories.create')" variant="primary" icon="plus">
                {{ __('Add category') }}
            </flux:button>
        @endcan
    </header>

    @include('livewire-tables::datatable')

    <flux:modal wire:model="showDeleteModal" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Remove the category?') }}</flux:heading>
                <flux:text>
                    {{ __(':name leaves the list. A category that still holds subcategories or inventory items cannot be removed.', ['name' => $this->deletingCategory?->name ?? '']) }}
                </flux:text>
            </div>

            @error('category')
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:text>{{ $message }}</flux:text>
                </flux:callout>
            @enderror

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteCategory" wire:loading.attr="disabled">
                    {{ __('Remove') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
