<?php

use App\Models\Order;
use App\Models\ProductClass;
use App\Models\ProductSubclass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Job order class')] class extends Component
{
    #[Locked]
    public ProductClass $productClass;

    /**
     * Mount the component.
     */
    public function mount(ProductClass $productClass): void
    {
        Gate::authorize('view', $productClass);

        $this->productClass = $productClass->loadCount(['subclasses', 'products']);
    }

    /**
     * The subclasses that belong to the class.
     *
     * @return Collection<int, ProductSubclass>
     */
    #[Computed]
    public function subclasses(): Collection
    {
        return $this->productClass->subclasses()
            ->withCount('products')
            ->orderBy('name')
            ->get();
    }

    /**
     * The job orders that hold a line item of the class.
     */
    #[Computed]
    public function jobOrderCount(): int
    {
        return Order::query()
            ->whereHas('products', fn (Builder $query) => $query->where('class_id', $this->productClass->getKey()))
            ->count();
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading level="1" size="xl">{{ $productClass->name }}</flux:heading>
            <flux:text class="mt-1">{{ __('Job order class') }}</flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('update', $productClass)
                <flux:button :href="route('product-classes.edit', $productClass)" variant="primary" icon="pencil-square">
                    {{ __('Edit') }}
                </flux:button>
            @endcan

            <flux:button :href="route('product-classes.index')" variant="ghost">
                {{ __('Back to the list') }}
            </flux:button>
        </div>
    </header>

    <flux:card>
        <flux:heading size="lg" class="mb-4">{{ __('Class information') }}</flux:heading>

        <dl class="grid gap-3 text-sm">
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Name') }}</dt>
                <dd class="font-medium">{{ $productClass->name }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Subclasses') }}</dt>
                <dd class="font-medium">{{ number_format((int) $productClass->subclasses_count) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Products') }}</dt>
                <dd class="font-medium">{{ number_format((int) $productClass->products_count) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Job orders') }}</dt>
                <dd class="font-medium">{{ number_format($this->jobOrderCount()) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Added on') }}</dt>
                <dd class="font-medium">
                    {{ $productClass->created_at ? date('d M Y', (int) $productClass->created_at) : '—' }}
                </dd>
            </div>
        </dl>
    </flux:card>

    <flux:card>
        <flux:heading size="lg" class="mb-4">{{ __('Subclasses of this class') }}</flux:heading>

        @if ($this->subclasses->isEmpty())
            <flux:text>{{ __('No subclass belongs to this class yet.') }}</flux:text>
        @else
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($this->subclasses as $subclass)
                    <li class="flex items-center justify-between gap-4 py-2 text-sm">
                        <a href="{{ route('product-subclasses.show', $subclass) }}" class="font-medium hover:underline">
                            {{ $subclass->name }}
                        </a>
                        <span class="text-zinc-600 dark:text-zinc-400">
                            {{ trans_choice('{0} No products|{1} :count product|[2,*] :count products', (int) $subclass->products_count, ['count' => (int) $subclass->products_count]) }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </flux:card>
</div>
