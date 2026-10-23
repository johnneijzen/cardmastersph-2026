<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ __('Job orders') }}</flux:heading>
            <flux:text class="mt-1">{{ __('The work that was ordered by a client, with its invoice and its due date.') }}</flux:text>
        </div>

        @can('create', App\Models\JobOrder::class)
            <flux:button :href="route('job-orders.create')" variant="primary" icon="plus">
                {{ __('Add job order') }}
            </flux:button>
        @endcan
    </header>

    @include('livewire-tables::datatable')

    <flux:modal wire:model="showArchiveModal" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Archive the job order?') }}</flux:heading>
                <flux:text>
                    {{ __(':number leaves the active list. The invoice, the line items and the financial history stay available. A job order with a payment cannot be archived.', ['number' => $this->archivingJobOrder?->job_order_number ?? '']) }}
                </flux:text>
            </div>

            @error('jobOrder')
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:text>{{ $message }}</flux:text>
                </flux:callout>
            @enderror

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="archiveJobOrder" wire:loading.attr="disabled">
                    {{ __('Archive') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
