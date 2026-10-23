<?php

use App\Models\JobOrder;
use App\Services\JobOrderPricingService;
use App\Support\Money\MoneyContext;
use Brick\Money\Money;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Job order')] class extends Component
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

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading level="1" size="xl">
                    {{ $jobOrder->job_order_number ?? '#'.$jobOrder->id }}
                </flux:heading>

                @if ($jobOrder->trashed())
                    <flux:badge color="zinc">{{ __('Archived') }}</flux:badge>
                @endif

                @if ($jobOrder->order !== null)
                    <flux:badge :color="$jobOrder->order->isOverdue() ? 'red' : 'zinc'">
                        {{ $jobOrder->order->isOverdue() ? __('Overdue') : $jobOrder->order->status }}
                    </flux:badge>
                @endif
            </div>

            <flux:text class="mt-1">
                {{ $jobOrder->order?->client?->name ?? '—' }}
                @if ($jobOrder->order?->due_date)
                    · {{ __('due :date', ['date' => $jobOrder->order->due_date->format('d M Y')]) }}
                @endif
            </flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button :href="route('job-orders.print', $jobOrder)" target="_blank" variant="ghost" icon="printer">
                {{ __('Print invoice') }}
            </flux:button>

            <flux:button :href="route('job-orders.routing-slip', $jobOrder)" target="_blank" variant="ghost" icon="document-text">
                {{ __('Routing slip') }}
            </flux:button>

            @can('update', $jobOrder)
                @if (! $jobOrder->trashed())
                    <flux:button :href="route('job-orders.edit', $jobOrder)" variant="primary" icon="pencil-square">
                        {{ __('Edit') }}
                    </flux:button>
                @endif
            @endcan

            <flux:button :href="route('job-orders.index')" variant="ghost">
                {{ __('Back to the list') }}
            </flux:button>
        </div>
    </header>

    @if ($jobOrder->trashed())
        <flux:callout color="amber" icon="archive-box">
            <flux:text>
                {{ __('This job order was archived. Its invoice, its line items and its payments are kept for the financial history.') }}
            </flux:text>
        </flux:callout>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <flux:card class="lg:col-span-2">
            <flux:heading size="lg" class="mb-4">{{ __('Client and contract person') }}</flux:heading>

            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Client') }}</dt>
                    <dd class="font-medium">{{ $jobOrder->order?->client?->name ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Contract person') }}</dt>
                    <dd class="font-medium">{{ $jobOrder->order?->client_contact_persons?->contact_person ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Contact number') }}</dt>
                    <dd class="font-medium">{{ $jobOrder->order?->client_contact_persons?->contact_number ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Contact e-mail') }}</dt>
                    <dd class="font-medium">{{ $jobOrder->order?->client_contact_persons?->contact_email ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Address') }}</dt>
                    <dd class="font-medium">{{ $jobOrder->order?->client?->address?->line_address ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('City') }}</dt>
                    <dd class="font-medium">
                        {{ $jobOrder->order?->client?->address?->city?->name ?? '—' }}
                        @if ($jobOrder->order?->client?->address?->city?->province?->name)
                            , {{ $jobOrder->order->client->address->city->province->name }}
                        @endif
                    </dd>
                </div>
            </dl>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-4">{{ __('Order information') }}</flux:heading>

            <dl class="grid gap-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Order date') }}</dt>
                    <dd class="font-medium">{{ $jobOrder->order?->order_date?->format('d M Y') ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Due date') }}</dt>
                    <dd class="font-medium">{{ $jobOrder->order?->due_date?->format('d M Y') ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Status') }}</dt>
                    <dd class="font-medium">{{ $jobOrder->order?->status ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Agent') }}</dt>
                    <dd class="font-medium">{{ $jobOrder->order?->job_order_agent?->name ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Artist in charge') }}</dt>
                    <dd class="font-medium">{{ $jobOrder->employee?->full_name ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Rating') }}</dt>
                    <dd class="font-medium">{{ $jobOrder->job_order_rating }}</dd>
                </div>
            </dl>
        </flux:card>
    </div>

    <flux:card>
        <flux:heading size="lg" class="mb-4">{{ __('Line items') }}</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column align="end">{{ __('Qty') }}</flux:table.column>
                <flux:table.column>{{ __('Product') }}</flux:table.column>
                <flux:table.column>{{ __('Class') }}</flux:table.column>
                <flux:table.column>{{ __('Subclass') }}</flux:table.column>
                <flux:table.column>{{ __('Particulars') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Unit price') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Amount') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($jobOrder->order?->products ?? [] as $product)
                    <flux:table.row :key="$product->id">
                        <flux:table.cell align="end">{{ number_format((int) $product->pivot->qty) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">{{ $product->name }}</span>
                                @if ($product->unit)
                                    <span class="text-zinc-500">({{ $product->unit }})</span>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $product->product_class?->name ?? '—' }}
                            @if ($product->product_class?->trashed())
                                <flux:badge color="zinc" size="sm">{{ __('Archived') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $product->product_subclass?->name ?? '—' }}
                            @if ($product->product_subclass?->trashed())
                                <flux:badge color="zinc" size="sm">{{ __('Archived') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $product->particulars ?: '—' }}</flux:table.cell>
                        <flux:table.cell align="end">{{ \App\Support\Money\MoneyContext::formatStorage($product->unit_price) }}</flux:table.cell>
                        <flux:table.cell align="end">{{ \App\Support\Money\MoneyContext::formatStorage($product->amount) }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">{{ __('This job order has no line item.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <dl class="mt-6 grid gap-3 text-sm sm:ml-auto sm:max-w-sm">
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Total amount') }}</dt>
                <dd class="font-semibold">{{ \App\Support\Money\MoneyContext::formatStorage($jobOrder->order?->total_amount) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Down payment') }}</dt>
                <dd class="font-semibold">{{ \App\Support\Money\MoneyContext::formatStorage($jobOrder->order?->down_payment) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Payments received') }}</dt>
                <dd class="font-semibold">{{ \App\Support\Money\MoneyContext::format($this->payments()) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Balance') }}</dt>
                <dd class="font-semibold">{{ \App\Support\Money\MoneyContext::formatStorage($jobOrder->order?->balance) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Payment status') }}</dt>
                <dd class="font-semibold">{{ $jobOrder->order?->payment_status ?? '—' }}</dd>
            </div>
        </dl>
    </flux:card>

    <div class="grid gap-4 lg:grid-cols-2">
        <flux:card>
            <flux:heading size="lg" class="mb-4">{{ __('Payments') }}</flux:heading>

            @if ($jobOrder->cash_receipt->isEmpty())
                <flux:text>{{ __('No payment was recorded against this job order yet.') }}</flux:text>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Date') }}</flux:table.column>
                        <flux:table.column>{{ __('OR number') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Amount') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($jobOrder->cash_receipt as $receipt)
                            <flux:table.row :key="$receipt->id">
                                <flux:table.cell>{{ $receipt->date?->format('d M Y') ?? '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $receipt->or_number }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    {{ \App\Support\Money\MoneyContext::formatStorage($receipt->amount) }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-4">{{ __('References and record') }}</flux:heading>

            <dl class="grid gap-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Purchase order') }}</dt>
                    <dd class="font-medium">{{ $jobOrder->order?->purchase_order_id ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Delivery receipt') }}</dt>
                    <dd class="font-medium">{{ $jobOrder->order?->delivery_receipt_id ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Billing statement') }}</dt>
                    <dd class="font-medium">{{ $jobOrder->order?->billing_statement_id ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Created by') }}</dt>
                    <dd class="font-medium">{{ $jobOrder->createdBy?->name ?? __('System') }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Last updated by') }}</dt>
                    <dd class="font-medium">{{ $jobOrder->updatedBy?->name ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Updated at') }}</dt>
                    <dd class="font-medium">
                        {{ $jobOrder->updated_at ? date('d M Y H:i', (int) $jobOrder->updated_at) : '—' }}
                    </dd>
                </div>
            </dl>
        </flux:card>
    </div>

    @if ($jobOrder->order?->overall_remarks)
        <flux:card>
            <flux:heading size="lg" class="mb-4">{{ __('Overall remarks') }}</flux:heading>
            <flux:text>{{ $jobOrder->order->overall_remarks }}</flux:text>
        </flux:card>
    @endif
</div>
