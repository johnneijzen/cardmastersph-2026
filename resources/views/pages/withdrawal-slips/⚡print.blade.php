<?php

use App\Models\WithdrawalSlip;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.print')] #[Title('Print withdrawal slip')] class extends Component
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
            'inventory',
        ]);
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-2 print:hidden">
        <div class="flex flex-wrap items-center gap-2">
            <flux:button :href="route('withdrawal-slips.show', $withdrawalSlip)" variant="ghost">
                {{ __('Back to the slip') }}
            </flux:button>
        </div>

        <flux:button variant="primary" icon="printer" onclick="window.print()">
            {{ __('Print this slip') }}
        </flux:button>
    </div>

    <style>
        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }
        }
    </style>

    <article class="bg-white p-8 text-zinc-900 shadow-sm print:p-0 print:shadow-none dark:bg-zinc-900 dark:text-zinc-100 print:dark:bg-white print:dark:text-zinc-900">
        <header class="border-b-2 border-zinc-900 pb-6 text-center dark:border-zinc-100 print:dark:border-zinc-900">
            <h1 class="text-xl font-bold uppercase">MARCOMMAX ENTERPRISES</h1>
            <p class="text-sm uppercase">(CARDMASTERS)</p>
            <p class="mt-1 text-sm">CAPISTRANO CRUZ TALL ST CAGAYAN DE ORO CITY</p>

            <h2 class="mt-6 text-lg font-bold uppercase tracking-widest">{{ __('Withdrawal Slip') }}</h2>
        </header>

        <div class="mt-6 grid gap-2 text-sm sm:grid-cols-2">
            <p><span class="font-semibold">{{ __('Slip number') }}:</span> {{ $withdrawalSlip->slip_number }}</p>
            <p class="sm:text-right"><span class="font-semibold">{{ __('Date') }}:</span> {{ $withdrawalSlip->date?->format('d/m/Y') }}</p>
            <p><span class="font-semibold">{{ __('Client') }}:</span> {{ $withdrawalSlip->client?->name ?? '—' }}</p>
            <p class="sm:text-right"><span class="font-semibold">{{ __('Job order') }}:</span> {{ $withdrawalSlip->job_order_id === null ? '—' : '#'.$withdrawalSlip->job_order_id }}</p>
            <p><span class="font-semibold">{{ __('Responsible employee') }}:</span> {{ $withdrawalSlip->employee?->full_name ?? '—' }}</p>
        </div>

        <table class="mt-8 w-full border-collapse text-sm">
            <thead>
                <tr>
                    <th class="border border-zinc-900 px-3 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">#</th>
                    <th class="border border-zinc-900 px-3 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Item') }}</th>
                    <th class="border border-zinc-900 px-3 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Particulars') }}</th>
                    <th class="border border-zinc-900 px-3 py-2 text-right font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Qty') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($withdrawalSlip->inventory as $item)
                    <tr>
                        <td class="border border-zinc-900 px-3 py-2 dark:border-zinc-100 print:dark:border-zinc-900">{{ $loop->iteration }}</td>
                        <td class="border border-zinc-900 px-3 py-2 dark:border-zinc-100 print:dark:border-zinc-900">{{ $item->item_name }}</td>
                        <td class="border border-zinc-900 px-3 py-2 dark:border-zinc-100 print:dark:border-zinc-900">{{ $item->pivot->particulars }}</td>
                        <td class="border border-zinc-900 px-3 py-2 text-right dark:border-zinc-100 print:dark:border-zinc-900">{{ (int) $item->pivot->qty }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="border border-zinc-900 px-3 py-2 text-center dark:border-zinc-100 print:dark:border-zinc-900">
                            {{ __('This slip does not name any item.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="border border-zinc-900 px-3 py-2 text-right font-semibold dark:border-zinc-100 print:dark:border-zinc-900">
                        {{ __('Total quantity') }}
                    </td>
                    <td class="border border-zinc-900 px-3 py-2 text-right font-semibold dark:border-zinc-100 print:dark:border-zinc-900">
                        {{ number_format($withdrawalSlip->totalQuantity()) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="mt-16 grid gap-12 text-sm sm:grid-cols-2">
            <div>
                <p>{{ __('Prepared by') }}:</p>
                <p class="mt-8 border-t border-zinc-400 pt-1">{{ $withdrawalSlip->createdBy?->name ?? '' }}</p>
            </div>

            <div>
                <p>{{ __('Received by') }}:</p>
                <p class="mt-8 border-t border-zinc-400 pt-1">&nbsp;</p>
            </div>
        </div>

        <div class="mt-12 text-sm sm:w-1/2 sm:pl-6">
            <p>{{ __('Date / time received') }}:</p>
            <p class="mt-8 border-t border-zinc-400 pt-1">&nbsp;</p>
        </div>
    </article>
</div>
