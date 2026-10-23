<?php

use App\Models\JobOrder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.print')] #[Title('Print routing slip')] class extends Component
{
    #[Locked]
    public JobOrder $jobOrder;

    /**
     * Mount the component.
     */
    public function mount(JobOrder $jobOrder): void
    {
        Gate::authorize('view', $jobOrder);

        $this->jobOrder = $jobOrder->load([
            'order.client',
            'order.client_contact_persons',
            'order.products.product_class',
            'order.products.product_subclass',
            'employee',
        ]);
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-2 print:hidden">
        <div class="flex flex-wrap items-center gap-2">
            <flux:button :href="route('job-orders.show', $jobOrder)" variant="ghost">
                {{ __('Back to the job order') }}
            </flux:button>

            <flux:button :href="route('job-orders.print', $jobOrder)" target="_blank" variant="ghost" icon="printer">
                {{ __('Invoice') }}
            </flux:button>
        </div>

        <flux:button variant="primary" icon="printer" onclick="window.print()">
            {{ __('Print this routing slip') }}
        </flux:button>
    </div>

    <style>
        @media print {
            @page {
                size: A4;
                margin: 12mm;
            }
        }
    </style>

    <article class="bg-white p-8 text-zinc-900 shadow-sm print:p-0 print:shadow-none dark:bg-zinc-900 dark:text-zinc-100 print:dark:bg-white print:dark:text-zinc-900">
        <header class="border-b-2 border-zinc-900 pb-4 dark:border-zinc-100 print:dark:border-zinc-900">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold uppercase">MARCOMMAX ENTERPRISES</h1>
                    <p class="text-sm uppercase">(CARDMASTERS)</p>
                    <p class="mt-1 text-sm">CAPISTRANO CRUZ TALL ST CAGAYAN DE ORO CITY</p>
                </div>

                <div class="text-sm">
                    <p class="font-semibold">{{ __('RS#') }}: {{ $jobOrder->job_order_number ?? $jobOrder->id }}</p>
                    <p>{{ __('Date') }}: {{ $jobOrder->order?->order_date?->format('d/m/Y') ?? '—' }}</p>
                    <p>{{ __('Due date') }}: {{ $jobOrder->order?->due_date?->format('d/m/Y') ?? '—' }}</p>
                </div>
            </div>

            <h2 class="mt-4 text-center text-lg font-bold uppercase tracking-widest">{{ __('Routing Slip') }}</h2>
        </header>

        <div class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
            <p><span class="font-semibold">{{ __('Client') }}:</span> {{ $jobOrder->order?->client?->name ?? '—' }}</p>
            <p><span class="font-semibold">{{ __('Artist in charge') }}:</span> {{ $jobOrder->employee?->full_name ?? '—' }}</p>
            <p><span class="font-semibold">{{ __('Contract person') }}:</span> {{ $jobOrder->order?->client_contact_persons?->contact_person ?? '—' }}</p>
            <p><span class="font-semibold">{{ __('Co-signed') }}:</span> ____________________</p>
        </div>

        <table class="mt-6 w-full border-collapse text-sm">
            <thead>
                <tr>
                    <th class="border border-zinc-900 px-2 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Subclass') }}</th>
                    <th class="border border-zinc-900 px-2 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Product') }}</th>
                    <th class="border border-zinc-900 px-2 py-2 text-right font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Quantity') }}</th>
                    <th class="border border-zinc-900 px-2 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Item') }}</th>
                    <th class="border border-zinc-900 px-2 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Particulars') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jobOrder->order?->products ?? [] as $product)
                    <tr>
                        <td class="border border-zinc-900 px-2 py-2 dark:border-zinc-100 print:dark:border-zinc-900">{{ $product->product_subclass?->name ?? '—' }}</td>
                        <td class="border border-zinc-900 px-2 py-2 dark:border-zinc-100 print:dark:border-zinc-900">{{ $product->name }}</td>
                        <td class="border border-zinc-900 px-2 py-2 text-right dark:border-zinc-100 print:dark:border-zinc-900">{{ (int) $product->pivot->qty }}</td>
                        <td class="border border-zinc-900 px-2 py-2 dark:border-zinc-100 print:dark:border-zinc-900">{{ $loop->iteration }}</td>
                        <td class="border border-zinc-900 px-2 py-2 dark:border-zinc-100 print:dark:border-zinc-900">{{ $product->particulars ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="border border-zinc-900 px-2 py-2 text-center dark:border-zinc-100 print:dark:border-zinc-900">
                            {{ __('This job order has no line item.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="mt-8 w-full border-collapse text-sm">
            <thead>
                <tr>
                    <th class="w-1/5 border border-zinc-900 px-2 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Department') }}</th>
                    <th class="border border-zinc-900 px-2 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Name / signature') }}</th>
                    <th class="border border-zinc-900 px-2 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Received date / time') }}</th>
                    <th class="w-1/6 border border-zinc-900 px-2 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('QA') }}</th>
                    <th class="border border-zinc-900 px-2 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Finished date / time') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ([__('Artist'), __('Printer'), __('Heatpress'), __('Fabrication'), __('Stickering'), __('Packing'), __('Embroidery'), __('Completed')] as $department)
                    <tr>
                        <td class="border border-zinc-900 px-2 py-3 font-medium dark:border-zinc-100 print:dark:border-zinc-900">{{ $department }}</td>
                        <td class="border border-zinc-900 px-2 py-3 dark:border-zinc-100 print:dark:border-zinc-900">&nbsp;</td>
                        <td class="border border-zinc-900 px-2 py-3 dark:border-zinc-100 print:dark:border-zinc-900">&nbsp;</td>
                        <td class="border border-zinc-900 px-2 py-3 dark:border-zinc-100 print:dark:border-zinc-900">&nbsp;</td>
                        <td class="border border-zinc-900 px-2 py-3 dark:border-zinc-100 print:dark:border-zinc-900">&nbsp;</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-8 text-sm">
            <p class="font-semibold">{{ __('Note') }}:</p>
            <p class="mt-1">&nbsp;</p>
        </div>
    </article>
</div>
