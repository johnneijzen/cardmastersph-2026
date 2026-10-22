<?php

use App\Models\ClientAgent;
use App\Rules\ClientAgentRules;
use App\Services\ClientAgentService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit agent')] class extends Component {
    #[Locked]
    public ClientAgent $clientAgent;

    public string $name = '';

    /**
     * Mount the component.
     */
    public function mount(ClientAgent $clientAgent): void
    {
        Gate::authorize('update', $clientAgent);

        $this->clientAgent = $clientAgent;
        $this->name = (string) $clientAgent->name;
    }

    /**
     * Store the changes.
     */
    public function save(ClientAgentService $agents): void
    {
        Gate::authorize('update', $this->clientAgent);

        $validated = $this->validate(ClientAgentRules::personal($this->clientAgent));

        $agents->update($this->clientAgent, $validated);

        $this->redirect(route('client-agents.show', $this->clientAgent), navigate: true);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header>
        <flux:heading level="1" size="xl">{{ __('Edit agent') }}</flux:heading>
        <flux:text class="mt-1">{{ $clientAgent->name }}</flux:text>
    </header>

    <form wire:submit="save" class="max-w-3xl space-y-8">
        <flux:card>
            <flux:heading size="lg">{{ __('Agent information') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" :error="$errors->first('name')" />
        </flux:card>

        <div class="flex justify-end gap-3">
            <flux:button :href="route('client-agents.show', $this->clientAgent)" variant="ghost">
                {{ __('Cancel') }}
            </flux:button>

            <flux:button variant="primary" type="submit">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>
</div>
