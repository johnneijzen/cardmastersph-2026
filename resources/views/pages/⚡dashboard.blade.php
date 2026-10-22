<?php

use App\Services\DashboardService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component
{
    /**
     * The number of days the upcoming activities are listed for.
     */
    public int $horizon = 14;

    /**
     * The statistics that are visible to the signed in user.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function statistics(): array
    {
        return app(DashboardService::class)->statistics(auth()->user());
    }

    /**
     * The job orders that are due within the selected period.
     *
     * @return Collection<int, \App\Models\Order>
     */
    #[Computed]
    public function upcoming(): Collection
    {
        return app(DashboardService::class)->upcomingActivities(auth()->user(), $this->horizon);
    }

    /**
     * The periods the upcoming activities may be listed for.
     *
     * @return array<int, int>
     */
    #[Computed]
    public function horizons(): array
    {
        return [7, 14, 30];
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header>
        <flux:heading level="1" size="xl">{{ __('Dashboard') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}</flux:text>
    </header>

    @if ($this->statistics['jobOrders'] === []
        && $this->statistics['clients'] === []
        && $this->statistics['employees'] === []
        && $this->statistics['inventory'] === []
        && $this->statistics['cashReceipts'] === []
        && $this->statistics['tasks'] === [])
        <flux:callout color="zinc" icon="information-circle">
            <flux:heading size="sm">{{ __('Nothing to show yet') }}</flux:heading>
            <flux:text>{{ __('Your account does not have access to any of the modules of the application yet.') }}</flux:text>
        </flux:callout>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @if ($this->statistics['jobOrders'] !== [])
            <flux:card class="flex items-center justify-between">
                <div>
                    <flux:text size="sm">{{ __('Job orders') }}</flux:text>
                    <flux:heading size="xl">{{ number_format($this->statistics['jobOrders']['total']) }}</flux:heading>
                    <flux:text size="xs" class="text-zinc-500">
                        {{ __(':count still open', ['count' => number_format($this->statistics['jobOrders']['open'])]) }}
                    </flux:text>
                </div>
                <flux:icon.clipboard-document-list class="size-8 text-zinc-400" />
            </flux:card>

            <flux:card class="flex items-center justify-between">
                <div>
                    <flux:text size="sm">{{ __('Overdue') }}</flux:text>
                    <flux:heading size="xl">{{ number_format($this->statistics['jobOrders']['overdue']) }}</flux:heading>
                    <flux:text size="xs" class="text-zinc-500">
                        {{ __(':count due within seven days', ['count' => number_format($this->statistics['jobOrders']['dueSoon'])]) }}
                    </flux:text>
                </div>
                <flux:icon.clock class="size-8 text-amber-500" />
            </flux:card>

            <flux:card class="flex items-center justify-between">
                <div>
                    <flux:text size="sm">{{ __('Archived job orders') }}</flux:text>
                    <flux:heading size="xl">{{ number_format($this->statistics['jobOrders']['archived']) }}</flux:heading>
                    <flux:text size="xs" class="text-zinc-500"> {{ __('Excluded from the active lists') }} </flux:text>
                </div>
                <flux:icon.archive-box class="size-8 text-zinc-400" />
            </flux:card>
        @endif

        @if ($this->statistics['clients'] !== [])
            <flux:card class="flex items-center justify-between">
                <div>
                    <flux:text size="sm">{{ __('Clients') }}</flux:text>
                    <flux:heading size="xl">{{ number_format($this->statistics['clients']['total']) }}</flux:heading>
                    <flux:text size="xs" class="text-zinc-500">
                        {{ __(':count new this month', ['count' => number_format($this->statistics['clients']['newThisMonth'])]) }}
                    </flux:text>
                </div>
                <flux:icon.users class="size-8 text-zinc-400" />
            </flux:card>
        @endif

        @if (isset($this->statistics['cashReceipts']['outstanding']))
            <flux:card class="flex items-center justify-between">
                <div>
                    <flux:text size="sm">{{ __('Outstanding balance') }}</flux:text>
                    <flux:heading size="xl">{{ number_format($this->statistics['cashReceipts']['outstanding'], 2) }}</flux:heading>
                    <flux:text size="xs" class="text-zinc-500">
                        {{ __(':count clients with a balance', ['count' => number_format($this->statistics['clients']['withOutstandingBalance'] ?? 0)]) }}
                    </flux:text>
                </div>
                <flux:icon.banknotes class="size-8 text-zinc-400" />
            </flux:card>
        @endif
    </section>

    <div class="grid gap-4 lg:grid-cols-3">
        @if ($this->statistics['jobOrders'] !== [])
            <flux:card class="lg:col-span-2">
                <flux:heading size="lg" class="mb-4">{{ __('Job orders by status') }}</flux:heading>

                <dl class="grid gap-3 sm:grid-cols-2">
                    @forelse ($this->statistics['jobOrders']['byStatus'] as $status => $total)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                            <dt class="text-sm text-zinc-600 dark:text-zinc-400">{{ $status }}</dt>
                            <dd class="text-sm font-semibold">{{ number_format($total) }}</dd>
                        </div>
                    @empty
                        <div class="text-sm text-zinc-500">{{ __('No job orders yet.') }}</div>
                    @endforelse
                </dl>
            </flux:card>
        @endif

        @if ($this->statistics['tasks'] !== [])
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Pending tasks') }}</flux:heading>

                <dl class="grid gap-3">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Due within seven days') }}</dt>
                        <dd class="text-sm font-semibold">
                            {{ number_format($this->statistics['tasks']['dueSoon']) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Overdue') }}</dt>
                        <dd class="text-sm font-semibold text-red-600 dark:text-red-400">
                            {{ number_format($this->statistics['tasks']['overdue']) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Not fully paid') }}</dt>
                        <dd class="text-sm font-semibold">{{ number_format($this->statistics['tasks']['unpaid']) }}</dd>
                    </div>
                </dl>
            </flux:card>
        @endif
    </div>

    @if ($this->statistics['jobOrders'] !== [])
        <flux:card>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <flux:heading size="lg">{{ __('Upcoming activities') }}</flux:heading>

                <flux:select wire:model.live="horizon" :label="__('Period')" class="max-w-40">
                    @foreach ($this->horizons as $horizon)
                        <option value="{{ $horizon }}">{{ __(':days days', ['days' => $horizon]) }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs tracking-wide text-zinc-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">{{ __('Due date') }}</th>
                            <th class="px-4 py-3">{{ __('Job order') }}</th>
                            <th class="px-4 py-3">{{ __('Client') }}</th>
                            <th class="px-4 py-3">{{ __('Artist in charge') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($this->upcoming as $order)
                            <tr wire:key="upcoming-{{ $order->id }}">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="{{ $order->due_date?->isPast() ? 'font-semibold text-red-600 dark:text-red-400' : '' }}">
                                        {{ $order->due_date?->format('d M Y') ?? __('—') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium">#{{ $order->id }}</td>
                                <td class="px-4 py-3">{{ $order->client?->name ?? __('—') }}</td>
                                <td class="px-4 py-3">
                                    {{ $order->jobOrder?->employee?->getFullNameAttribute() ?? __('—') }}
                                </td>
                                <td class="px-4 py-3">{{ $order->status ?? __('—') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-zinc-500">
                                    {{ __('Nothing is due in this period.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        @if ($this->statistics['employees'] !== [])
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Employees') }}</flux:heading>

                <dl class="grid gap-3">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Total') }}</dt>
                        <dd class="text-sm font-semibold">
                            {{ number_format($this->statistics['employees']['total']) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Artists') }}</dt>
                        <dd class="text-sm font-semibold">
                            {{ number_format($this->statistics['employees']['artists']) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('With an account') }}</dt>
                        <dd class="text-sm font-semibold">
                            {{ number_format($this->statistics['employees']['withAccount']) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Assigned to job orders') }}</dt>
                        <dd class="text-sm font-semibold">
                            {{ number_format($this->statistics['employees']['assigned']) }}
                        </dd>
                    </div>
                </dl>
            </flux:card>
        @endif

        @if ($this->statistics['inventory'] !== [])
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Inventory') }}</flux:heading>

                <dl class="grid gap-3">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Items') }}</dt>
                        <dd class="text-sm font-semibold">
                            {{ number_format($this->statistics['inventory']['total']) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Out of stock') }}</dt>
                        <dd class="text-sm font-semibold text-amber-600 dark:text-amber-400">
                            {{ number_format($this->statistics['inventory']['outOfStock']) }}
                        </dd>
                    </div>
                </dl>
            </flux:card>
        @endif

        @if ($this->statistics['cashReceipts'] !== [])
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Cash receipts') }}</flux:heading>

                <dl class="grid gap-3">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Received this month') }}</dt>
                        <dd class="text-sm font-semibold">
                            {{ number_format($this->statistics['cashReceipts']['receivedThisMonth'], 2) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Receipts this month') }}</dt>
                        <dd class="text-sm font-semibold">
                            {{ number_format($this->statistics['cashReceipts']['receiptsThisMonth']) }}
                        </dd>
                    </div>
                    @isset($this->statistics['cashReceipts']['outstanding'])
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Outstanding balance') }}</dt>
                            <dd class="text-sm font-semibold">
                                {{ number_format($this->statistics['cashReceipts']['outstanding'], 2) }}
                            </dd>
                        </div>
                    @endisset
                </dl>
            </flux:card>
        @endif
    </div>
</div>
