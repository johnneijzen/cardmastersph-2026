<?php

use App\Enums\Role;
use App\Models\Employee;
use App\Services\EmployeeService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Employee')] class extends Component
{
    #[Locked]
    public Employee $employee;

    /**
     * The role that the account of the employee carries.
     */
    public string $role = '';

    /**
     * Mount the component.
     */
    public function mount(Employee $employee): void
    {
        Gate::authorize('view', $employee);

        $this->employee = $employee->loadMissing(['address.city.province', 'user']);
        $this->role = $employee->user?->getRoleNames()->first() ?? Role::Staff->value;
    }

    /**
     * The job orders that the employee is the artist in charge of.
     */
    #[Computed]
    public function jobOrderCount(): int
    {
        return $this->employee->jobOrders()->count();
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
     * Whether the signed in user may change the account of the employee.
     */
    public function canManageAccount(): bool
    {
        return auth()->user()?->can('manageAccount', $this->employee) ?? false;
    }

    /**
     * Activate or deactivate the account of the employee.
     */
    public function toggleAccountStatus(EmployeeService $employees): void
    {
        Gate::authorize('manageAccount', $this->employee);

        $active = ! $this->employee->user?->isActive();

        $employees->setAccountStatus($this->employee, $active);

        $this->employee->refresh();

        Flux::toast(
            variant: 'success',
            text: $active ? __('The account was activated.') : __('The account was deactivated.'),
        );
    }

    /**
     * Give the account of the employee another role.
     */
    public function updateRole(EmployeeService $employees): void
    {
        Gate::authorize('manageAccount', $this->employee);

        $validated = $this->validate([
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        $employees->assignRole($this->employee, Role::from($validated['role']));

        $this->employee->refresh();

        Flux::toast(variant: 'success', text: __('The role was saved.'));
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ $employee->getFullNameAttribute() }}</flux:heading>
            <flux:text class="mt-1">{{ $employee->position }}</flux:text>
        </div>

        <div class="flex gap-3">
            @can('update', $employee)
                <flux:button :href="route('employees.edit', $employee)" variant="primary" icon="pencil-square">
                    {{ __('Edit') }}
                </flux:button>
            @endcan

            <flux:button :href="route('employees.index')" variant="ghost"> {{ __('Back to the list') }} </flux:button>
        </div>
    </header>

    <div class="grid gap-4 lg:grid-cols-2">
        <flux:card>
            <flux:heading size="lg" class="mb-4">{{ __('Personal information') }}</flux:heading>

            <dl class="grid gap-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('First name') }}</dt>
                    <dd class="font-medium">{{ $employee->first_name }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Middle name') }}</dt>
                    <dd class="font-medium">{{ $employee->middle_name ?: '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Last name') }}</dt>
                    <dd class="font-medium">{{ $employee->last_name }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Birthday') }}</dt>
                    <dd class="font-medium">{{ $employee->birthday?->format('d M Y') ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Contact number') }}</dt>
                    <dd class="font-medium">{{ $employee->contact_number ?: '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Emergency contact person') }}</dt>
                    <dd class="font-medium">{{ $employee->emergency_contact_person_name ?: '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Emergency contact number') }}</dt>
                    <dd class="font-medium">{{ $employee->emergency_contact_person_number ?: '—' }}</dd>
                </div>
            </dl>
        </flux:card>

        <div class="space-y-4">
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Address') }}</flux:heading>

                <dl class="grid gap-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Line address') }}</dt>
                        <dd class="font-medium">{{ $employee->address?->line_address ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-zinc-600 dark:text-zinc-400">{{ __('City') }}</dt>
                        <dd class="font-medium">{{ $employee->address?->city?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Province') }}</dt>
                        <dd class="font-medium">{{ $employee->address?->city?->province?->name ?? '—' }}</dd>
                    </div>
                </dl>
            </flux:card>

            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Account') }}</flux:heading>

                @if (! $employee->user)
                    <flux:text>{{ __('This employee does not have an account.') }}</flux:text>
                @else
                    <dl class="grid gap-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-zinc-600 dark:text-zinc-400">{{ __('E-mail address') }}</dt>
                            <dd class="font-medium">{{ $employee->user->email }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Role') }}</dt>
                            <dd class="font-medium">{{ $employee->user->getRoleNames()->implode(', ') ?: '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Status') }}</dt>
                            <dd>
                                @if ($employee->user->isActive())
                                    <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                                @else
                                    <flux:badge color="amber" size="sm">{{ __('Deactivated') }}</flux:badge>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if ($this->canManageAccount())
                        <div class="mt-4 flex flex-wrap items-end gap-3">
                            <flux:select wire:model="role" :label="__('Role')" class="max-w-48">
                                @foreach ($this->roles as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </flux:select>

                            <flux:button variant="primary" wire:click="updateRole">
                                {{ __('Save the role') }}
                            </flux:button>

                            <flux:button variant="ghost" wire:click="toggleAccountStatus">
                                {{ $employee->user->isActive() ? __('Deactivate the account') : __('Activate the account') }}
                            </flux:button>
                        </div>
                    @endif
                @endif
            </flux:card>
        </div>
    </div>

    <flux:card>
        <flux:heading size="lg" class="mb-4">{{ __('Job orders') }}</flux:heading>

        @if ($this->jobOrderCount > 0)
            <flux:text>
                {{ __('This employee is the artist in charge of :count job orders.', ['count' => number_format($this->jobOrderCount)]) }}
            </flux:text>
            <flux:text class="mt-1 text-zinc-500">
                {{ __('Because of that the employee cannot be removed. Deactivate the account instead.') }}
            </flux:text>
        @else
            <flux:text>{{ __('This employee is not the artist in charge of a job order.') }}</flux:text>
        @endif
    </flux:card>
</div>
