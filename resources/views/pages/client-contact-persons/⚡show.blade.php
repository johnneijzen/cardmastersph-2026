<?php

use App\Models\ClientContactPerson;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Contract person')] class extends Component {
    #[Locked]
    public ClientContactPerson $clientContactPerson;

    /**
     * Mount the component.
     */
    public function mount(ClientContactPerson $clientContactPerson): void
    {
        Gate::authorize('view', $clientContactPerson);

        $this->clientContactPerson = $clientContactPerson->loadMissing('client');
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ $clientContactPerson->contact_person }}</flux:heading>
            <flux:text class="mt-1">{{ $clientContactPerson->client?->name }}</flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('update', $clientContactPerson)
                <flux:button :href="route('client-contact-persons.edit', $clientContactPerson)" variant="primary" icon="pencil-square">
                    {{ __('Edit') }}
                </flux:button>
            @endcan

            <flux:button :href="route('client-contact-persons.index')" variant="ghost">
                {{ __('Back to the list') }}
            </flux:button>
        </div>
    </header>

    <flux:card>
        <flux:heading size="lg" class="mb-4">{{ __('Contact information') }}</flux:heading>

        <dl class="grid gap-3 text-sm">
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Contact person') }}</dt>
                <dd class="font-medium">{{ $clientContactPerson->contact_person }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Contact number') }}</dt>
                <dd class="font-medium">{{ $clientContactPerson->contact_number ?: '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('E-mail address') }}</dt>
                <dd class="font-medium">{{ $clientContactPerson->contact_email ?: '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Client') }}</dt>
                <dd class="font-medium">
                    @if ($clientContactPerson->client)
                        <flux:link :href="route('clients.show', $clientContactPerson->client)">
                            {{ $clientContactPerson->client->name }}
                        </flux:link>
                    @else
                        —
                    @endif
                </dd>
            </div>
        </dl>
    </flux:card>
</div>
