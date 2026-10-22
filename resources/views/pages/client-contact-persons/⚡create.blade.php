<?php

use App\Models\Client;
use App\Models\ClientContactPerson;
use App\Rules\ClientContactPersonRules;
use App\Services\ClientContactPersonService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add contract person')] class extends Component {
    public string $contact_person = '';

    public string $contact_number = '';

    public string $contact_email = '';

    public string $client_id = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('create', ClientContactPerson::class);

        $clientId = request()->integer('client');

        if ($clientId > 0 && Client::query()->whereKey($clientId)->exists()) {
            $this->client_id = (string) $clientId;
        }
    }

    /**
     * The clients a contract person can be attached to.
     *
     * @return Collection<int, Client>
     */
    #[Computed]
    public function clients(): Collection
    {
        return Client::query()->orderBy('name')->get();
    }

    /**
     * Store the contract person.
     */
    public function save(ClientContactPersonService $contactPersons): void
    {
        Gate::authorize('create', ClientContactPerson::class);

        $validated = $this->validate(ClientContactPersonRules::personal());

        $contactPerson = $contactPersons->create([
            ...$validated,
            'client_id' => (int) $validated['client_id'],
        ]);

        $this->redirect(route('client-contact-persons.show', $contactPerson), navigate: true);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header>
        <flux:heading level="1" size="xl">{{ __('Add contract person') }}</flux:heading>
        <flux:text class="mt-1">{{ __('The person a client can be reached at.') }}</flux:text>
    </header>

    <form wire:submit="save" class="max-w-3xl space-y-8">
        <flux:card>
            <flux:heading size="lg">{{ __('Contact information') }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="contact_person" :label="__('Contact person')" :error="$errors->first('contact_person')" />
                <flux:input wire:model="contact_number" :label="__('Contact number')" :error="$errors->first('contact_number')" />
                <flux:input wire:model="contact_email" type="email" :label="__('E-mail address')" :error="$errors->first('contact_email')" />

                <flux:select wire:model="client_id" :label="__('Client')" :error="$errors->first('client_id')">
                    <option value="">{{ __('Choose a client') }}</option>
                    @foreach ($this->clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        <div class="flex justify-end gap-3">
            <flux:button :href="route('client-contact-persons.index')" variant="ghost">
                {{ __('Cancel') }}
            </flux:button>

            <flux:button variant="primary" type="submit">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>
</div>
