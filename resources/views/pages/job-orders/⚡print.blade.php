<?php

use App\Models\JobOrder;
use App\Services\JobOrderPricingService;
use App\Support\Money\MoneyContext;
use Brick\Money\Money;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.print')] #[Title('Print job order')] class extends Component
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
            'order.client.address.city.province',
            'order.client_contact_persons',
            'order.job_order_agent',
            'order.products.product_class',
            'order.products.product_subclass',
            'employee',
            'createdBy',
            'updatedBy',
            'cash_receipt.payment_option',
        ]);
    }

    /**
     * The payments that were recorded against the job order.
     */
    #[Computed]
    public function payments(): Money
    {
        return app(JobOrderPricingService::class)->sum(
            $this->jobOrder->cash_receipt->pluck('amount')->all(),
        );
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-2 print:hidden">
        <div class="flex flex-wrap items-center gap-2">
            <flux:button :href="route('job-orders.show', $jobOrder)" variant="ghost">
                {{ __('Back to the job order') }}
            </flux:button>

            <flux:button :href="route('job-orders.routing-slip', $jobOrder)" target="_blank" variant="ghost" icon="document-text">
                {{ __('Routing slip') }}
            </flux:button>
        </div>

        <flux:button variant="primary" icon="printer" onclick="window.print()">
            {{ __('Print this invoice') }}
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
        <header class="border-b-2 border-zinc-900 pb-6 text-center dark:border-zinc-100 print:dark:border-zinc-900">
            <h1 class="text-xl font-bold uppercase">MARCOMMAX ENTERPRISES</h1>
            <p class="text-sm uppercase">(CARDMASTERS)</p>
            <p class="mt-1 text-sm">CAPISTRANO CRUZ TALL ST CAGAYAN DE ORO CITY</p>

            <h2 class="mt-6 text-lg font-bold uppercase tracking-widest">{{ __('Job Order / Invoice') }}</h2>
        </header>

        <div class="mt-6 grid gap-6 text-sm sm:grid-cols-2">
            <div class="space-y-1">
                <p><span class="font-semibold">{{ __('Client') }}:</span> {{ $jobOrder->order?->client?->name ?? '—' }}</p>
                <p><span class="font-semibold">{{ __('Contract person') }}:</span> {{ $jobOrder->order?->client_contact_persons?->contact_person ?? '—' }}</p>
                <p><span class="font-semibold">{{ __('Contact number') }}:</span> {{ $jobOrder->order?->client_contact_persons?->contact_number ?? '—' }}</p>
                <p><span class="font-semibold">{{ __('Contact e-mail') }}:</span> {{ $jobOrder->order?->client_contact_persons?->contact_email ?? '—' }}</p>
                <p>
                    <span class="font-semibold">{{ __('Address') }}:</span>
                    {{ $jobOrder->order?->client?->address?->line_address ?? '—' }}
                    @if ($jobOrder->order?->client?->address?->city?->name)
                        , {{ $jobOrder->order->client->address->city->name }}
                    @endif
                    @if ($jobOrder->order?->client?->address?->city?->province?->name)
                        , {{ $jobOrder->order->client->address->city->province->name }}
                    @endif
                </p>
            </div>

            <div class="space-y-1">
                <p><span class="font-semibold">{{ __('Job order number') }}:</span> {{ $jobOrder->job_order_number ?? '#'.$jobOrder->id }}</p>
                <p><span class="font-semibold">{{ __('Order date') }}:</span> {{ $jobOrder->order?->order_date?->format('d/m/Y') ?? '—' }}</p>
                <p><span class="font-semibold">{{ __('Due date') }}:</span> {{ $jobOrder->order?->due_date?->format('d/m/Y') ?? '—' }}</p>
                <p><span class="font-semibold">{{ __('Agent') }}:</span> {{ $jobOrder->order?->job_order_agent?->name ?? '—' }}</p>
                <p><span class="font-semibold">{{ __('Artist in charge') }}:</span> {{ $jobOrder->employee?->full_name ?? '—' }}</p>
                <p><span class="font-semibold">{{ __('Status') }}:</span> {{ $jobOrder->order?->status ?? '—' }}</p>
            </div>
        </div>

        <table class="mt-8 w-full border-collapse text-sm">
            <thead>
                <tr>
                    <th class="border border-zinc-900 px-2 py-2 text-right font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Qty') }}</th>
                    <th class="border border-zinc-900 px-2 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Unit') }}</th>
                    <th class="border border-zinc-900 px-2 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Product') }}</th>
                    <th class="border border-zinc-900 px-2 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Class') }}</th>
                    <th class="border border-zinc-900 px-2 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Subclass') }}</th>
                    <th class="border border-zinc-900 px-2 py-2 text-left font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Particulars') }}</th>
                    <th class="border border-zinc-900 px-2 py-2 text-right font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Unit price') }}</th>
                    <th class="border border-zinc-900 px-2 py-2 text-right font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jobOrder->order?->products ?? [] as $product)
                    <tr>
                        <td class="border border-zinc-900 px-2 py-2 text-right dark:border-zinc-100 print:dark:border-zinc-900">{{ (int) $product->pivot->qty }}</td>
                        <td class="border border-zinc-900 px-2 py-2 dark:border-zinc-100 print:dark:border-zinc-900">{{ $product->unit ?: '—' }}</td>
                        <td class="border border-zinc-900 px-2 py-2 dark:border-zinc-100 print:dark:border-zinc-900">{{ $product->name }}</td>
                        <td class="border border-zinc-900 px-2 py-2 dark:border-zinc-100 print:dark:border-zinc-900">{{ $product->product_class?->name ?? '—' }}</td>
                        <td class="border border-zinc-900 px-2 py-2 dark:border-zinc-100 print:dark:border-zinc-900">{{ $product->product_subclass?->name ?? '—' }}</td>
                        <td class="border border-zinc-900 px-2 py-2 dark:border-zinc-100 print:dark:border-zinc-900">{{ $product->particulars ?: '—' }}</td>
                        <td class="border border-zinc-900 px-2 py-2 text-right dark:border-zinc-100 print:dark:border-zinc-900">{{ \App\Support\Money\MoneyContext::formatStorage($product->unit_price) }}</td>
                        <td class="border border-zinc-900 px-2 py-2 text-right dark:border-zinc-100 print:dark:border-zinc-900">{{ \App\Support\Money\MoneyContext::formatStorage($product->amount) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="border border-zinc-900 px-2 py-2 text-center dark:border-zinc-100 print:dark:border-zinc-900">
                            {{ __('This job order has no line item.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" class="border border-zinc-900 px-2 py-2 text-right font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Total amount') }}</td>
                    <td class="border border-zinc-900 px-2 py-2 text-right font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ \App\Support\Money\MoneyContext::formatStorage($jobOrder->order?->total_amount) }}</td>
                </tr>
                <tr>
                    <td colspan="7" class="border border-zinc-900 px-2 py-2 text-right font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Down payment') }}</td>
                    <td class="border border-zinc-900 px-2 py-2 text-right dark:border-zinc-100 print:dark:border-zinc-900">{{ \App\Support\Money\MoneyContext::formatStorage($jobOrder->order?->down_payment) }}</td>
                </tr>
                @foreach ($jobOrder->cash_receipt as $receipt)
                    <tr>
                        <td colspan="7" class="border border-zinc-900 px-2 py-2 text-right dark:border-zinc-100 print:dark:border-zinc-900">
                            {{ __('Payment received :date', ['date' => $receipt->date?->format('d/m/Y') ?? '']) }}
                            @if ($receipt->or_number)
                                ({{ __('OR') }} {{ $receipt->or_number }})
                            @endif
                        </td>
                        <td class="border border-zinc-900 px-2 py-2 text-right dark:border-zinc-100 print:dark:border-zinc-900">{{ \App\Support\Money\MoneyContext::formatStorage($receipt->amount) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="7" class="border border-zinc-900 px-2 py-2 text-right font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Balance') }}</td>
                    <td class="border border-zinc-900 px-2 py-2 text-right font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ \App\Support\Money\MoneyContext::formatStorage($jobOrder->order?->balance) }}</td>
                </tr>
                <tr>
                    <td colspan="7" class="border border-zinc-900 px-2 py-2 text-right font-semibold dark:border-zinc-100 print:dark:border-zinc-900">{{ __('Payment status') }}</td>
                    <td class="border border-zinc-900 px-2 py-2 text-right dark:border-zinc-100 print:dark:border-zinc-900">{{ $jobOrder->order?->payment_status ?? '—' }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
            <div>
                <p class="font-semibold">{{ __('Overall remarks') }}</p>
                <p class="mt-1">{{ $jobOrder->order?->overall_remarks ?: '—' }}</p>
            </div>

            <div class="space-y-1">
                <p><span class="font-semibold">{{ __('Purchase order') }}:</span> {{ $jobOrder->order?->purchase_order_id ?? '—' }}</p>
                <p><span class="font-semibold">{{ __('Delivery receipt') }}:</span> {{ $jobOrder->order?->delivery_receipt_id ?? '—' }}</p>
                <p><span class="font-semibold">{{ __('Billing statement') }}:</span> {{ $jobOrder->order?->billing_statement_id ?? '—' }}</p>
            </div>
        </div>

        <div class="mt-16 grid gap-12 text-sm sm:grid-cols-2">
            <div>
                <p>{{ __('Prepared by') }}:</p>
                <p class="mt-8 border-t border-zinc-400 pt-1">
                    {{ $jobOrder->createdBy?->name ?? $jobOrder->updatedBy?->name ?? '' }}
                </p>
            </div>

            <div>
                <p>{{ __('Received by') }}:</p>
                <p class="mt-8 border-t border-zinc-400 pt-1">&nbsp;</p>
            </div>
        </div>
    </article>
</div>
