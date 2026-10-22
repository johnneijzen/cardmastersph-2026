<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ __('Employees') }}</flux:heading>
            <flux:text class="mt-1">{{ __('The people who work on the job orders of the company.') }}</flux:text>
        </div>

        @can('create', App\Models\Employee::class)
            <flux:button :href="route('employees.create')" variant="primary" icon="plus">
                {{ __('Add employee') }}
            </flux:button>
        @endcan
    </header>

    @include('livewire-tables::datatable')

    <flux:modal wire:model="showDeleteModal" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Remove the employee?') }}</flux:heading>
                <flux:text>
                    {{ __(':name and the records that belong to it are removed. This cannot be undone.', ['name' => $this->deletingEmployee?->getFullNameAttribute() ?? '']) }}
                </flux:text>
            </div>

            @error('employee')
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:text>{{ $message }}</flux:text>
                </flux:callout>
            @enderror

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteEmployee" wire:loading.attr="disabled">
                    {{ __('Remove') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
