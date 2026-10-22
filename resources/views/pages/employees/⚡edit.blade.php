<?php

use App\Enums\Role;
use App\Models\City;
use App\Models\Employee;
use App\Models\Province;
use App\Rules\EmployeeRules;
use App\Services\EmployeeService;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit employee')] class extends Component
{
    #[Locked]
    public Employee $employee;

    public string $first_name = '';

    public string $middle_name = '';

    public string $last_name = '';

    public string $position = '';

    public string $birthday = '';

    public string $contact_number = '';

    public string $emergency_contact_person_name = '';

    public string $emergency_contact_person_number = '';

    public string $line_address = '';

    public string $province_id = '';

    public string $city_id = '';

    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(Employee $employee): void
    {
        Gate::authorize('update', $employee);

        $this->employee = $employee;
        $this->first_name = (string) $employee->first_name;
        $this->middle_name = (string) $employee->middle_name;
        $this->last_name = (string) $employee->last_name;
        $this->position = (string) $employee->position;
        $this->birthday = $employee->birthday?->format('Y-m-d') ?? '';
        $this->contact_number = (string) $employee->contact_number;
        $this->emergency_contact_person_name = (string) $employee->emergency_contact_person_name;
        $this->emergency_contact_person_number = (string) $employee->emergency_contact_person_number;
        $this->line_address = (string) $employee->address?->line_address;
        $this->province_id = (string) ($employee->address?->city?->province_id ?? '');
        $this->city_id = (string) ($employee->address?->city_id ?? '');
        $this->email = (string) $employee->user?->email;
    }

    /**
     * Whether the signed in user may change the account of the employee.
     */
    public function canManageAccount(): bool
    {
        return auth()->user()?->can('manageAccount', $this->employee) ?? false;
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
     * Forget the city when another province is chosen.
     */
    public function updatedProvinceId(): void
    {
        $this->city_id = '';
    }

    /**
     * Store the changes.
     */
    public function save(EmployeeService $employees): void
    {
        Gate::authorize('update', $this->employee);

        $rules = EmployeeRules::personal();
        $manageAccount = $this->canManageAccount();

        if ($manageAccount && $this->employee->user) {
            $rules = [...$rules, ...EmployeeRules::account($this->employee->user)];
        }

        $validated = $this->validate($rules);

        $employees->update($this->employee, [
            ...$validated,
            'city_id' => (int) $validated['city_id'],
            'email' => $validated['email'] ?? $this->employee->user?->email,
        ], $manageAccount);

        Flux::toast(variant: 'success', text: __('The employee was saved.'));

        $this->redirect(route('employees.show', $this->employee), navigate: true);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header>
        <flux:heading level="1" size="xl">{{ __('Edit employee') }}</flux:heading>
        <flux:text class="mt-1">{{ $employee->getFullNameAttribute() }}</flux:text>
    </header>

    <form wire:submit="save" class="max-w-3xl space-y-8">
        <flux:card>
            <flux:heading size="lg">{{ __('Personal information') }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="first_name" :label="__('First name')" :error="$errors->first('first_name')" />
                <flux:input
                    wire:model="middle_name"
                    :label="__('Middle name')"
                    :error="$errors->first('middle_name')"
                />
                <flux:input wire:model="last_name" :label="__('Last name')" :error="$errors->first('last_name')" />
                <flux:input wire:model="position" :label="__('Position')" :error="$errors->first('position')" />
                <flux:input
                    wire:model="birthday"
                    type="date"
                    :label="__('Birthday')"
                    :error="$errors->first('birthday')"
                />
                <flux:input
                    wire:model="contact_number"
                    :label="__('Contact number')"
                    :error="$errors->first('contact_number')"
                />
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
                <flux:input
                    wire:model="line_address"
                    :label="__('Line address')"
                    :error="$errors->first('line_address')"
                />

                <flux:select
                    wire:model.live="province_id"
                    :label="__('Province')"
                    :error="$errors->first('province_id')"
                >
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

        @if ($this->canManageAccount() && $employee->user)
            <flux:card>
                <flux:heading size="lg">{{ __('Account') }}</flux:heading>

                <flux:input
                    wire:model="email"
                    type="email"
                    :label="__('E-mail address')"
                    :error="$errors->first('email')"
                />
            </flux:card>
        @endif

        <div class="flex justify-end gap-3">
            <flux:button :href="route('employees.show', $employee)" variant="ghost"> {{ __('Cancel') }} </flux:button>

            <flux:button variant="primary" type="submit"> {{ __('Save') }} </flux:button>
        </div>
    </form>
</div>
