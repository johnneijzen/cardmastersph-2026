<?php

use App\Models\Order;
use App\Models\ProductSubclass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Job order subclass')] class extends Component
{
    #[Locked]
    public ProductSubclass $productSubclass;

    /**
     * Mount the component.
     */
    public function mount(ProductSubclass $productSubclass): void
    {
        Gate::authorize('view', $productSubclass);

        $this->productSubclass = $productSubclass->load('productClass')->loadCount('products');
    }

    /**
     * The job orders that hold a line item of the subclass.
     */
    #[Computed]
    public function jobOrderCount(): int
    {
        return Order::query()
            ->whereHas('products', fn (Builder $query) => $query->where('subclass_id', $this->productSubclass->getKey()))
            ->count();
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ $productSubclass->name }}</flux:heading>
            <flux:text class="mt-1">{{ __('Job order subclass') }}</flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('update', $productSubclass)
                <flux:button :href="route('product-subclasses.edit', $productSubclass)" variant="primary" icon="pencil-square">
                    {{ __('Edit') }}
                </flux:button>
            @endcan

            <flux:button :href="route('product-subclasses.index')" variant="ghost">
                {{ __('Back to the list') }}
            </flux:button>
        </div>
    </header>

    <flux:card>
        <flux:heading size="lg" class="mb-4">{{ __('Subclass information') }}</flux:heading>

        <dl class="grid gap-3 text-sm">
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Name') }}</dt>
                <dd class="font-medium">{{ $productSubclass->name }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Class') }}</dt>
                <dd class="font-medium">
                    @if ($productSubclass->productClass !== null)
                        <a href="{{ route('product-classes.show', $productSubclass->productClass) }}" class="hover:underline">
                            {{ $productSubclass->productClass->name }}
                        </a>
                    @else
                        {{ __('Unclassified') }}
                    @endif
                </dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Products') }}</dt>
                <dd class="font-medium">{{ number_format((int) $productSubclass->products_count) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Job orders') }}</dt>
                <dd class="font-medium">{{ number_format($this->jobOrderCount()) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Added on') }}</dt>
                <dd class="font-medium">
                    {{ $productSubclass->created_at ? date('d M Y', (int) $productSubclass->created_at) : '—' }}
                </dd>
            </div>
        </dl>
    </flux:card>
</div>
