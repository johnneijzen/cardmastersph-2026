<?php

use App\Models\ProductClass;
use App\Models\ProductSubclass;
use App\Rules\ProductSubclassRules;
use App\Services\ProductSubclassService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit subclass')] class extends Component
{
    #[Locked]
    public ProductSubclass $productSubclass;

    public string $name = '';

    public string $product_class_id = '';

    /**
     * Mount the component.
     */
    public function mount(ProductSubclass $productSubclass): void
    {
        Gate::authorize('update', $productSubclass);

        $this->productSubclass = $productSubclass;
        $this->name = (string) $productSubclass->name;
        $this->product_class_id = (string) ($productSubclass->product_class_id ?? '');
    }

    /**
     * The classes a subclass can belong to.
     *
     * The class the subclass already belongs to stays available when it was
     * archived, so the record can be saved without changing its history.
     *
     * @return Collection<int, ProductClass>
     */
    #[Computed]
    public function classes(): Collection
    {
        $currentClassId = $this->productSubclass->product_class_id;

        return ProductClass::withTrashed()
            ->where(fn (Builder $query) => $query
                ->whereNull('deleted_at')
                ->when(
                    $currentClassId,
                    fn (Builder $query, int $currentClassId) => $query->orWhere('id', $currentClassId),
                ))
            ->orderBy('name')
            ->get();
    }

    /**
     * Store the changes.
     */
    public function save(ProductSubclassService $productSubclasses): void
    {
        Gate::authorize('update', $this->productSubclass);

        $validated = $this->validate(ProductSubclassRules::details($this->product_class_id, $this->productSubclass));

        $productSubclasses->update($this->productSubclass, $validated);

        $this->redirect(route('product-subclasses.index'), navigate: true);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header>
        <flux:heading level="1" size="xl">{{ __('Edit subclass') }}</flux:heading>
        <flux:text class="mt-1">{{ $productSubclass->name }}</flux:text>
    </header>

    <form wire:submit="save" class="max-w-3xl space-y-8">
        <flux:card>
            <flux:heading size="lg">{{ __('Subclass information') }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" :label="__('Name')" :error="$errors->first('name')" />

                <flux:select wire:model="product_class_id" :label="__('Class')" :error="$errors->first('product_class_id')">
                    <option value="">{{ __('Choose a class') }}</option>
                    @foreach ($this->classes as $productClass)
                        <option value="{{ $productClass->id }}">
                            {{ $productClass->trashed() ? $productClass->name.' '.__('(archived)') : $productClass->name }}
                        </option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        <div class="flex justify-end gap-3">
            <flux:button :href="route('product-subclasses.index')" variant="ghost"> {{ __('Cancel') }} </flux:button>

            <flux:button variant="primary" type="submit"> {{ __('Save') }} </flux:button>
        </div>
    </form>
</div>
