<?php

use App\Models\WithdrawalSlip;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Withdrawal slip')] class extends Component
{
    #[Locked]
    public WithdrawalSlip $withdrawalSlip;

    /**
     * Mount the component.
     */
    public function mount(WithdrawalSlip $withdrawalSlip): void
    {
        Gate::authorize('view', $withdrawalSlip);

        $this->withdrawalSlip = $withdrawalSlip->load([
            'client',
            'jobOrder',
            'employee',
            'createdBy',
            'updatedBy',
            'inventory',
        ]);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading level="1" size="xl">{{ $withdrawalSlip->slip_number }}</flux:heading>

                @if ($withdrawalSlip->isArchived())
                    <flux:badge color="red">{{ __('Reversed') }}</flux:badge>
                @endif
            </div>

            <flux:text class="mt-1">
                {{ $withdrawalSlip->date?->format('d M Y') }}
                @if ($withdrawalSlip->client)
                    · {{ $withdrawalSlip->client->name }}
                @endif
            </flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button
                :href="route('withdrawal-slips.print', $withdrawalSlip)"
                target="_blank"
                variant="ghost"
                icon="printer"
            >
                {{ __('Print') }}
            </flux:button>

            <flux:button :href="route('withdrawal-slips.index')" variant="ghost">
                {{ __('Back to the list') }}
            </flux:button>
        </div>
    </header>

    @if ($withdrawalSlip->isArchived())
        <flux:callout color="amber" icon="arrow-uturn-left">
            <flux:text>
                {{ __('This slip was reversed and the withdrawn quantity was put back on the inventory. The slip is kept for the withdrawal history.') }}
            </flux:text>
        </flux:callout>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <flux:card>
            <flux:heading size="lg" class="mb-4">{{ __('Slip information') }}</flux:heading>

            <dl class="grid gap-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Date') }}</dt>
                    <dd class="font-medium">{{ $withdrawalSlip->date?->format('d M Y') ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Client') }}</dt>
                    <dd class="font-medium">{{ $withdrawalSlip->client?->name ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Job order') }}</dt>
                    <dd class="font-medium">
                        {{ $withdrawalSlip->job_order_id === null ? '—' : '#'.$withdrawalSlip->job_order_id }}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Responsible employee') }}</dt>
                    <dd class="font-medium">{{ $withdrawalSlip->employee?->full_name ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Items') }}</dt>
                    <dd class="font-medium">{{ number_format($withdrawalSlip->itemCount()) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Total quantity') }}</dt>
                    <dd class="font-medium">{{ number_format($withdrawalSlip->totalQuantity()) }}</dd>
                </div>
            </dl>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-4">{{ __('Recorded') }}</flux:heading>

            <dl class="grid gap-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Prepared by') }}</dt>
                    <dd class="font-medium">{{ $withdrawalSlip->createdBy?->name ?? __('System') }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Created at') }}</dt>
                    <dd class="font-medium">
                        {{ $withdrawalSlip->created_at ? date('d M Y H:i', (int) $withdrawalSlip->created_at) : '—' }}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Last updated by') }}</dt>
                    <dd class="font-medium">{{ $withdrawalSlip->updatedBy?->name ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Updated at') }}</dt>
                    <dd class="font-medium">
                        {{ $withdrawalSlip->updated_at ? date('d M Y H:i', (int) $withdrawalSlip->updated_at) : '—' }}
                    </dd>
                </div>
            </dl>
        </flux:card>
    </div>

    <flux:card>
        <flux:heading size="lg" class="mb-4">{{ __('Withdrawn items') }}</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Item') }}</flux:table.column>
                <flux:table.column>{{ __('Particulars') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Quantity') }}</flux:table.column>
                <flux:table.column align="end">{{ __('On hand now') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($withdrawalSlip->inventory as $item)
                    <flux:table.row :key="$item->id">
                        <flux:table.cell>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">{{ $item->item_name }}</span>

                                @if ($item->trashed())
                                    <flux:badge color="zinc" size="sm">{{ __('Archived') }}</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $item->pivot->particulars ?: '—' }}</flux:table.cell>
                        <flux:table.cell align="end">{{ number_format((int) $item->pivot->qty) }}</flux:table.cell>
                        <flux:table.cell align="end">{{ $item->current_quantity ?? '—' }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4">{{ __('This slip does not name any item.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
