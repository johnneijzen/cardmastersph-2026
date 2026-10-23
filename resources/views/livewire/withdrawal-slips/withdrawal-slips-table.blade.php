<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ __('Withdrawal slips') }}</flux:heading>
            <flux:text class="mt-1">
                {{ __('The stock that was taken out of the inventory for a client and a job order.') }}
            </flux:text>
        </div>

        @can('create', App\Models\WithdrawalSlip::class)
            <flux:button :href="route('withdrawal-slips.create')" variant="primary" icon="plus">
                {{ __('Add withdrawal slip') }}
            </flux:button>
        @endcan
    </header>

    @include('livewire-tables::datatable')

    <flux:modal wire:model="showReverseModal" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Reverse the withdrawal slip?') }}</flux:heading>
                <flux:text>
                    {{ __(':number is archived and every withdrawn quantity is put back on the inventory. The slip and its stock movements stay in the history.', ['number' => $this->reversingSlip?->slip_number ?? '']) }}
                </flux:text>
            </div>

            @error('slip')
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:text>{{ $message }}</flux:text>
                </flux:callout>
            @enderror

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="reverseSlip" wire:loading.attr="disabled">
                    {{ __('Reverse') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
