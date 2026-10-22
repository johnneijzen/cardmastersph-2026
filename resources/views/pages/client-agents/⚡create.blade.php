<?php

use App\Models\ClientAgent;
use App\Rules\ClientAgentRules;
use App\Services\ClientAgentService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add agent')] class extends Component
{
    public string $name = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('create', ClientAgent::class);
    }

    /**
     * Store the agent.
     */
    public function save(ClientAgentService $agents): void
    {
        Gate::authorize('create', ClientAgent::class);

        $validated = $this->validate(ClientAgentRules::personal());

        $agent = $agents->create($validated);

        $this->redirect(route('client-agents.show', $agent), navigate: true);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header>
        <flux:heading level="1" size="xl">{{ __('Add agent') }}</flux:heading>
        <flux:text class="mt-1">{{ __('The channel a client was acquired through.') }}</flux:text>
    </header>

    <form wire:submit="save" class="max-w-3xl space-y-8">
        <flux:card>
            <flux:heading size="lg">{{ __('Agent information') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" :error="$errors->first('name')" />
        </flux:card>

        <div class="flex justify-end gap-3">
            <flux:button :href="route('client-agents.index')" variant="ghost"> {{ __('Cancel') }} </flux:button>

            <flux:button variant="primary" type="submit"> {{ __('Save') }} </flux:button>
        </div>
    </form>
</div>
