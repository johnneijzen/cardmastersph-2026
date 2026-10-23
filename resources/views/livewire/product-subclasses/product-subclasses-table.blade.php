<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ __('Subclasses') }}</flux:heading>
            <flux:text class="mt-1">{{ __('The finer classifications inside a job order class.') }}</flux:text>
        </div>

        @can('create', App\Models\ProductSubclass::class)
            <flux:button :href="route('product-subclasses.create')" variant="primary" icon="plus">
                {{ __('Add subclass') }}
            </flux:button>
        @endcan
    </header>

    @include('livewire-tables::datatable')

    <flux:modal wire:model="showArchiveModal" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Archive the subclass?') }}</flux:heading>
                <flux:text>
                    {{ __(':name leaves the active list. The job orders that already use the subclass keep their classification.', ['name' => $this->archivingProductSubclass?->name ?? '']) }}
                </flux:text>
            </div>

            @error('productSubclass')
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:text>{{ $message }}</flux:text>
                </flux:callout>
            @enderror

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="archiveProductSubclass" wire:loading.attr="disabled">
                    {{ __('Archive') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
