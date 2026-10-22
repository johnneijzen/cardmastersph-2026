<?php

use App\Models\City;
use App\Models\Client;
use App\Models\ClientAgent;
use App\Models\Province;
use App\Rules\ClientRules;
use App\Services\ClientService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add client')] class extends Component {
    public string $name = '';

    public string $client_number = '';

    public string $line_address = '';

    public string $province_id = '';

    public string $city_id = '';

    public string $agent_id = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('create', Client::class);
    }

    /**
     * The provinces the clients are located in.
     *
     * @return Collection<int, Province>
     */
    #[Computed]
    public function provinces(): Collection
    {
        return Province::query()->orderBy('name')->get();
    }

    /**
     * The cities of the selected province.
     *
     * @return Collection<int, City>
     */
    #[Computed]
    public function cities(): Collection
    {
        if ($this->province_id === '') {
            return new Collection;
        }

        return City::query()
            ->where('province_id', $this->province_id)
            ->orderBy('name')
            ->get();
    }

    /**
     * The agents a client can be acquired through.
     *
     * @return Collection<int, ClientAgent>
     */
    #[Computed]
    public function agents(): Collection
    {
        return ClientAgent::query()->orderBy('name')->get();
    }

    /**
     * Forget the city when another province is chosen.
     */
    public function updatedProvinceId(): void
    {
        $this->city_id = '';
    }

    /**
     * Store the client.
     */
    public function save(ClientService $clients): void
    {
        Gate::authorize('create', Client::class);

        $validated = $this->validate(ClientRules::personal(null, $this->province_id));

        $client = $clients->create([
            ...$validated,
            'city_id' => (int) $validated['city_id'],
            'agent_id' => $validated['agent_id'] === '' ? null : (int) $validated['agent_id'],
        ]);

        $this->redirect(route('clients.show', $client), navigate: true);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header>
        <flux:heading level="1" size="xl">{{ __('Add client') }}</flux:heading>
        <flux:text class="mt-1">{{ __('The details of the client the job orders are placed for.') }}</flux:text>
    </header>

    <form wire:submit="save" class="max-w-3xl space-y-8">
        <flux:card>
            <flux:heading size="lg">{{ __('Client information') }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" :label="__('Name')" :error="$errors->first('name')" />
                <flux:input wire:model="client_number" :label="__('Phone number')" :error="$errors->first('client_number')" />

                <flux:select wire:model="agent_id" :label="__('Agent')" :error="$errors->first('agent_id')">
                    <option value="">{{ __('No agent') }}</option>
                    @foreach ($this->agents as $agent)
                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">{{ __('Address') }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="line_address" :label="__('Line address')" :error="$errors->first('line_address')" />

                <flux:select wire:model.live="province_id" :label="__('Province')" :error="$errors->first('province_id')">
                    <option value="">{{ __('Choose a province') }}</option>
                    @foreach ($this->provinces as $province)
                        <option value="{{ $province->id }}">{{ $province->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="city_id" :label="__('City')" :error="$errors->first('city_id')">
                    <option value="">{{ __('Choose a city') }}</option>
                    @foreach ($this->cities as $city)
                        <option value="{{ $city->id }}">{{ $city->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        <div class="flex justify-end gap-3">
            <flux:button :href="route('clients.index')" variant="ghost">
                {{ __('Cancel') }}
            </flux:button>

            <flux:button variant="primary" type="submit">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>
</div>
