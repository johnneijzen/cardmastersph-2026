<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ __('Clients') }}</flux:heading>
            <flux:text class="mt-1">{{ __('The people and the companies the job orders are placed for.') }}</flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('export', App\Models\Client::class)
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="export" wire:loading.attr="disabled">
                    {{ __('Export') }}
                </flux:button>
            @endcan

            @can('create', App\Models\Client::class)
                <flux:button :href="route('clients.create')" variant="primary" icon="plus">
                    {{ __('Add client') }}
                </flux:button>
            @endcan
        </div>
    </header>

    @include('livewire-tables::datatable')

    <flux:modal wire:model="showArchiveModal" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Archive the client?') }}</flux:heading>
                <flux:text>
                    {{ __(':name is removed from the active list. The client is kept, so the job orders and the cash receipts keep their history.', ['name' => $this->archivingClient?->name ?? '']) }}
                </flux:text>
            </div>

            @error('client')
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:text>{{ $message }}</flux:text>
                </flux:callout>
            @enderror

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="archiveClient" wire:loading.attr="disabled">
                    {{ __('Archive') }}
                </flux:button>
            </div>        </div>
    </flux:modal>
</div>
