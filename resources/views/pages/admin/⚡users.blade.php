<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('User accounts')] class extends Component {
    use WithPagination;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'role', except: '')]
    public string $roleFilter = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorizeAccountManagement();
    }

    /**
     * Only an administrator may manage user accounts.
     */
    public function authorizeAccountManagement(): void
    {
        abort_unless(
            auth()->user()?->can(\App\Enums\Permission::ManageUserAccounts) ?? false,
            403,
        );
    }

    /**
     * The accounts that match the current filters.
     */
    #[Computed]
    public function users()
    {
        return User::query()
            ->with('roles', 'employee')
            ->when($this->search !== '', function ($query): void {
                $term = '%'.str_replace('%', '\%', $this->search).'%';

                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($this->roleFilter !== '', fn ($query) => $query->whereHas(
                'roles',
                fn ($query) => $query->where('name', $this->roleFilter),
            ))
            ->orderBy('name')
            ->paginate(15);
    }

    /**
     * The roles an account can be filtered by.
     */
    #[Computed]
    public function roles(): array
    {
        return Role::cases();
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header>
        <flux:heading level="1" size="xl">{{ __('User accounts') }}</flux:heading>
        <flux:text class="mt-1">
            {{ __('The deployment cannot send mail, so a password is reset here and handed over directly. The account holder has to choose their own password at the next sign in.') }}
        </flux:text>
    </header>

    <div class="flex flex-wrap items-end gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            :label="__('Search')"
            type="search"
            placeholder="{{ __('Name or e-mail address') }}"
            class="max-w-xs"
        />

        <flux:select wire:model.live="roleFilter" :label="__('Role')" class="max-w-xs">
            <option value="">{{ __('All roles') }}</option>
            @foreach ($this->roles as $role)
                <option value="{{ $role->value }}">{{ __($role->label()) }}</option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3">{{ __('Name') }}</th>
                    <th class="px-4 py-3">{{ __('E-mail address') }}</th>
                    <th class="px-4 py-3">{{ __('Role') }}</th>
                    <th class="px-4 py-3">{{ __('Employee') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($this->users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            {{ $user->getRoleNames()->implode(', ') ?: __('—') }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $user->employee?->getFullNameAttribute() ?? __('—') }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($user->must_change_password)
                                <flux:badge color="amber" size="sm">{{ __('Password change required') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Active') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <flux:button size="sm" variant="ghost" :href="route('admin.users.password', $user)">
                                {{ __('Reset password') }}
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-500">
                            {{ __('No accounts match the current filters.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $this->users->links() }}</div>
</div>
