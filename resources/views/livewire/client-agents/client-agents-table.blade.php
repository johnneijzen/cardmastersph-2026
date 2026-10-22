<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ __('Client agents') }}</flux:heading>
            <flux:text class="mt-1">{{ __('The channels the clients were acquired through.') }}</flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('create', App\Models\ClientAgent::class)
                <flux:button :href="route('client-agents.create')" variant="primary" icon="plus">
                    {{ __('Add agent') }}
                </flux:button>
            @endcan
        </div>
    </header>

    @include('livewire-tables::datatable')

    <flux:modal wire:model="showDeleteModal" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Remove the agent?') }}</flux:heading>
                <flux:text>
                    {{ __(':name is removed from the list. This cannot be undone.', ['name' => $this->deletingAgent?->name ?? '']) }}
                </flux:text>
            </div>

            @error('clientAgent')
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:text>{{ $message }}</flux:text>
                </flux:callout>
            @enderror

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteAgent" wire:loading.attr="disabled">
                    {{ __('Remove') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
