<?php

use App\Models\ClientAgent;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Client agent')] class extends Component
{
    #[Locked]
    public ClientAgent $clientAgent;

    /**
     * Mount the component.
     */
    public function mount(ClientAgent $clientAgent): void
    {
        Gate::authorize('view', $clientAgent);

        $this->clientAgent = $clientAgent->loadCount('clients');
    }

    /**
     * The clients that were acquired through this agent.
     */
    #[Computed]
    public function clientCount(): int
    {
        return (int) $this->clientAgent->clients_count;
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ $clientAgent->name }}</flux:heading>
            <flux:text class="mt-1">{{ __('Client agent') }}</flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('update', $clientAgent)
                <flux:button :href="route('client-agents.edit', $clientAgent)" variant="primary" icon="pencil-square">
                    {{ __('Edit') }}
                </flux:button>
            @endcan

            <flux:button :href="route('client-agents.index')" variant="ghost">
                {{ __('Back to the list') }}
            </flux:button>
        </div>
    </header>

    <flux:card>
        <flux:heading size="lg" class="mb-4">{{ __('Agent information') }}</flux:heading>

        <dl class="grid gap-3 text-sm">
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Name') }}</dt>
                <dd class="font-medium">{{ $clientAgent->name }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Clients') }}</dt>
                <dd class="font-medium">{{ number_format($this->clientCount()) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Added on') }}</dt>
                <dd class="font-medium">
                    {{ $clientAgent->created_at ? date('d M Y', (int) $clientAgent->created_at) : '—' }}
                </dd>
            </div>
        </dl>
    </flux:card>
</div>
