<?php

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Subcategory;
use App\Rules\InventoryRules;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add inventory item')] class extends Component
{
    public string $item_name = '';

    public string $description = '';

    public string $categories_id = '';

    public string $subcategories_id = '';

    public string $price = '';

    public string $size = '';

    public string $color = '';

    public string $type = '';

    public string $current_quantity = '0';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('create', Inventory::class);
    }

    /**
     * The categories an item can be classified in.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    /**
     * The subcategories of the selected category.
     *
     * The subcategories that were migrated from the legacy application do not
     * belong to a category yet, so they are listed for every category until
     * they are classified.
     *
     * @return Collection<int, Subcategory>
     */
    #[Computed]
    public function subcategories(): Collection
    {
        if ($this->categories_id === '') {
            return new Collection;
        }

        return Subcategory::query()
            ->where(function ($query): void {
                $query->where('categories_id', $this->categories_id)
                    ->orWhereNull('categories_id');
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Forget the subcategory when another category is chosen.
     */
    public function updatedCategoriesId(): void
    {
        $this->subcategories_id = '';
    }

    /**
     * Store the item and its opening quantity.
     */
    public function save(InventoryService $inventory): void
    {
        Gate::authorize('create', Inventory::class);

        $validated = $this->validate([
            ...InventoryRules::item($this->categories_id),
            ...InventoryRules::openingQuantity(),
        ]);

        $item = $inventory->create([
            ...$validated,
            'current_quantity' => (int) $validated['current_quantity'],
        ]);

        $this->redirect(route('inventory.show', $item), navigate: true);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header>
        <flux:heading level="1" size="xl">{{ __('Add inventory item') }}</flux:heading>
        <flux:text class="mt-1">{{ __('The details of the item and the quantity that is on hand.') }}</flux:text>
    </header>

    <form wire:submit="save" class="max-w-3xl space-y-8">
        <flux:card>
            <flux:heading size="lg">{{ __('Item information') }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="item_name" :label="__('Item name')" :error="$errors->first('item_name')" />
                <flux:input wire:model="description" :label="__('Description')" :error="$errors->first('description')" />
                <flux:input wire:model="price" type="number" step="0.01" min="0" :label="__('Price')" :error="$errors->first('price')" />
                <flux:input wire:model="size" :label="__('Size')" :error="$errors->first('size')" />
                <flux:input wire:model="color" :label="__('Color')" :error="$errors->first('color')" />
                <flux:input wire:model="type" :label="__('Type')" :error="$errors->first('type')" />
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">{{ __('Classification') }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select
                    wire:model.live="categories_id"
                    :label="__('Category')"
                    :error="$errors->first('categories_id')"
                >
                    <option value="">{{ __('Choose a category') }}</option>
                    @foreach ($this->categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select
                    wire:model="subcategories_id"
                    :label="__('Subcategory')"
                    :error="$errors->first('subcategories_id')"
                    :disabled="$categories_id === ''"
                >
                    <option value="">{{ __('Choose a subcategory') }}</option>
                    @foreach ($this->subcategories as $subcategory)
                        <option value="{{ $subcategory->id }}">{{ $subcategory->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            @if ($categories_id !== '' && $this->subcategories->isEmpty())
                <flux:callout color="amber" icon="exclamation-triangle" class="mt-4">
                    <flux:text>
                        {{ __('This category has no subcategory yet. Add one under Subcategories first.') }}
                    </flux:text>
                </flux:callout>
            @endif
        </flux:card>

        <flux:card>
            <flux:heading size="lg">{{ __('Stock') }}</flux:heading>
            <flux:text>{{ __('The quantity that is on hand. Every later change is recorded as a stock adjustment with a reason.') }}</flux:text>

            <div class="mt-4 max-w-xs">
                <flux:input
                    wire:model="current_quantity"
                    type="number"
                    step="1"
                    min="0"
                    :label="__('Quantity on hand')"
                    :error="$errors->first('current_quantity')"
                />
            </div>
        </flux:card>

        <div class="flex justify-end gap-3">
            <flux:button :href="route('inventory.index')" variant="ghost"> {{ __('Cancel') }} </flux:button>

            <flux:button variant="primary" type="submit"> {{ __('Save') }} </flux:button>
        </div>
    </form>
</div>
