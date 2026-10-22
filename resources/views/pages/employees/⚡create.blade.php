<?php

use App\Enums\Role;
use App\Models\City;
use App\Models\Employee;
use App\Models\Province;
use App\Rules\EmployeeRules;
use App\Services\EmployeeService;
use App\Support\TemporaryPassword;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add employee')] class extends Component {
    public string $first_name = '';

    public string $middle_name = '';

    public string $last_name = '';

    public string $position = 'Artist';

    public string $birthday = '';

    public string $contact_number = '';

    public string $emergency_contact_person_name = '';

    public string $emergency_contact_person_number = '';

    public string $line_address = '';

    public string $province_id = '';

    public string $city_id = '';

    public bool $create_account = false;

    public string $email = '';

    public string $role = 'staff';

    /**
     * The temporary password of the account that was created.
     */
    public ?string $issuedPassword = null;

    /**
     * The employee that was created.
     */
    #[Locked]
    public ?int $createdEmployeeId = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('create', Employee::class);
    }

    /**
     * The provinces the employees are located in.
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
     * The roles an account may be given.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function roles(): array
    {
        return collect(Role::cases())
            ->mapWithKeys(fn (Role $role): array => [$role->value => __($role->label())])
            ->all();
    }

    /**
     * Forget the city when another province is chosen.
     */
    public function updatedProvinceId(): void
    {
        $this->city_id = '';
    }

    /**
     * Store the employee.
     */
    public function save(EmployeeService $employees): void
    {
        Gate::authorize('create', Employee::class);

        $rules = EmployeeRules::personal();

        if ($this->create_account) {
            $rules = [...$rules, ...EmployeeRules::account()];
        }

        $validated = $this->validate($rules);

        $password = $this->create_account ? TemporaryPassword::generate() : '';

        $employee = $employees->create([
            ...$validated,
            'city_id' => (int) $validated['city_id'],
            'create_account' => $this->create_account,
            'password' => $password,
        ]);

        if (! $this->create_account) {
            $this->redirect(route('employees.show', $employee), navigate: true);

            return;
        }

        $this->createdEmployeeId = $employee->id;
        $this->issuedPassword = $password;
    }

    /**
     * Leave the form after the account was created.
     */
    public function finish(): void
    {
        $this->redirect(route('employees.show', $this->createdEmployeeId), navigate: true);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header>
        <flux:heading level="1" size="xl">{{ __('Add employee') }}</flux:heading>
        <flux:text class="mt-1">{{ __('The personal details of the employee and, when wanted, the account that belongs to it.') }}</flux:text>
    </header>

    @if ($issuedPassword)
        <flux:callout color="lime" icon="check-circle" class="max-w-2xl">
            <flux:heading size="sm">{{ __('The employee was added.') }}</flux:heading>
            <flux:text>
                {{ __('Hand this password over to the account holder directly. It is shown only once.') }}
            </flux:text>
            <flux:text class="font-mono text-lg font-semibold">{{ $issuedPassword }}</flux:text>
        </flux:callout>

        <div class="flex gap-3">
            <flux:button :href="route('employees.index')" variant="ghost">
                {{ __('Back to the list') }}
            </flux:button>

            <flux:button variant="primary" wire:click="finish">
                {{ __('Continue') }}
            </flux:button>
        </div>
    @else
        <form wire:submit="save" class="max-w-3xl space-y-8">
            <flux:card>
                <flux:heading size="lg">{{ __('Personal information') }}</flux:heading>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="first_name" :label="__('First name')" :error="$errors->first('first_name')" />
                    <flux:input wire:model="middle_name" :label="__('Middle name')" :error="$errors->first('middle_name')" />
                    <flux:input wire:model="last_name" :label="__('Last name')" :error="$errors->first('last_name')" />
                    <flux:input wire:model="position" :label="__('Position')" :error="$errors->first('position')" />
                    <flux:input wire:model="birthday" type="date" :label="__('Birthday')" :error="$errors->first('birthday')" />
                    <flux:input wire:model="contact_number" :label="__('Contact number')" :error="$errors->first('contact_number')" />
                    <flux:input
                        wire:model="emergency_contact_person_name"
                        :label="__('Emergency contact person')"
                        :error="$errors->first('emergency_contact_person_name')"
                    />
                    <flux:input
                        wire:model="emergency_contact_person_number"
                        :label="__('Emergency contact number')"
                        :error="$errors->first('emergency_contact_person_number')"
                    />
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

            <flux:card>
                <flux:heading size="lg">{{ __('Account') }}</flux:heading>
                <flux:text>{{ __('An employee may sign in to the application with an account of their own.') }}</flux:text>

                <div class="mt-4 space-y-4">
                    <flux:checkbox wire:model="create_account" :label="__('Create an account for this employee')" />

                    @if ($create_account)
                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:input wire:model="email" type="email" :label="__('E-mail address')" :error="$errors->first('email')" />

                            <flux:select wire:model="role" :label="__('Role')" :error="$errors->first('role')">
                                @foreach ($this->roles as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </flux:select>
                        </div>

                        <flux:callout color="amber" icon="exclamation-triangle">
                            <flux:text>{{ __('The deployment cannot send mail. The account starts with a temporary password that is shown once the employee is saved.') }}</flux:text>
                        </flux:callout>
                    @endif
                </div>
            </flux:card>

            <div class="flex justify-end gap-3">
                <flux:button :href="route('employees.index')" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>

                <flux:button variant="primary" type="submit">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    @endif
</div>
