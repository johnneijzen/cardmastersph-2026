<?php

use App\Models\Client;
use App\Services\ClientService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Client')] class extends Component
{
    #[Locked]
    public Client $client;

    /**
     * Mount the component.
     */
    public function mount(Client $client): void
    {
        Gate::authorize('view', $client);

        $this->client = $client->loadMissing(['address.city.province', 'clientAgent', 'clientContactPersons']);
    }

    /**
     * The number of job orders that were placed for this client.
     */
    #[Computed]
    public function jobOrderCount(): int
    {
        return $this->client->orders()->count();
    }

    /**
     * Put the archived client back on the active list.
     */
    public function restore(ClientService $clients): void
    {
        Gate::authorize('restore', $this->client);

        $clients->restore($this->client);

        $this->client->refresh();

        Flux::toast(variant: 'success', text: __('The client was restored.'));
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ $client->name }}</flux:heading>
            <flux:text class="mt-1">{{ $client->client_number }}</flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($client->trashed())
                <flux:badge color="amber" size="sm">{{ __('Archived') }}</flux:badge>

                @can('restore', $client)
                    <flux:button variant="primary" icon="arrow-path" wire:click="restore">
                        {{ __('Restore') }}
                    </flux:button>
                @endcan
            @else
                @can('update', $client)
                    <flux:button :href="route('clients.edit', $client)" variant="primary" icon="pencil-square">
                        {{ __('Edit') }}
                    </flux:button>
                @endcan
            @endif

            <flux:button :href="route('clients.index')" variant="ghost"> {{ __('Back to the list') }} </flux:button>
        </div>
    </header>

    <div class="grid gap-4 lg:grid-cols-2">
        <flux:card>
            <flux:heading size="lg" class="mb-4">{{ __('Client information') }}</flux:heading>

            <dl class="grid gap-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Name') }}</dt>
                    <dd class="font-medium">{{ $client->name }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Phone number') }}</dt>
                    <dd class="font-medium">{{ $client->client_number ?: '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Agent') }}</dt>
                    <dd class="font-medium">{{ $client->clientAgent?->name ?: '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Added on') }}</dt>
                    <dd class="font-medium">
                        {{ $client->created_at ? date('d M Y', (int) $client->created_at) : '—' }}
                    </dd>
                </div>
            </dl>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-4">{{ __('Address') }}</flux:heading>

            <dl class="grid gap-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Line address') }}</dt>
                    <dd class="font-medium">{{ $client->address?->line_address ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('City') }}</dt>
                    <dd class="font-medium">{{ $client->address?->city?->name ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Province') }}</dt>
                    <dd class="font-medium">{{ $client->address?->city?->province?->name ?? '—' }}</dd>
                </div>
            </dl>
        </flux:card>
    </div>

    <flux:card>
        <flux:heading size="lg" class="mb-4">{{ __('Contract persons') }}</flux:heading>

        @if ($client->clientContactPersons->isEmpty())
            <flux:text>{{ __('This client has no contract person yet.') }}</flux:text>
        @else
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($client->clientContactPersons as $contactPerson)
                    <li class="flex flex-wrap items-center justify-between gap-4 py-3">
                        <div>
                            <p class="font-medium">{{ $contactPerson->contact_person }}</p>
                            <p class="text-sm text-zinc-500">
                                {{ $contactPerson->contact_number }} · {{ $contactPerson->contact_email }}
                            </p>
                        </div>

                        @can('update', $contactPerson)
                            <flux:button
                                size="xs"
                                variant="ghost"
                                icon="pencil-square"
                                :href="route('client-contact-persons.edit', $contactPerson)"
                                :aria-label="__('Edit :name', ['name' => $contactPerson->contact_person])"
                            />
                        @endcan
                    </li>
                @endforeach
            </ul>
        @endif

        @can('create', App\Models\ClientContactPerson::class)
            <div class="mt-4">
                <flux:button
                    :href="route('client-contact-persons.create', ['client' => $client->id])"
                    variant="ghost"
                    icon="plus"
                >
                    {{ __('Add contract person') }}
                </flux:button>
            </div>
        @endcan
    </flux:card>

    <flux:card>
        <flux:heading size="lg" class="mb-4">{{ __('Job orders') }}</flux:heading>

        <flux:text>
            {{ trans_choice(':count job order was placed for this client.|:count job orders were placed for this client.', $this->jobOrderCount(), ['count' => number_format($this->jobOrderCount())]) }}
        </flux:text>
    </flux:card>
</div>
