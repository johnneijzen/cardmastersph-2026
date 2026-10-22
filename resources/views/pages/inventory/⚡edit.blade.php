<?php

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Subcategory;
use App\Rules\InventoryRules;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit inventory item')] class extends Component
{
    #[Locked]
    public Inventory $inventory;

    public string $item_name = '';

    public string $description = '';

    public string $categories_id = '';

    public string $subcategories_id = '';

    public string $price = '';

    public string $size = '';

    public string $color = '';

    public string $type = '';

    /**
     * Mount the component.
     */
    public function mount(Inventory $inventory): void
    {
        Gate::authorize('update', $inventory);

        $this->inventory = $inventory;
        $this->item_name = (string) $inventory->item_name;
        $this->description = (string) $inventory->description;
        $this->categories_id = (string) ($inventory->categories_id ?? '');
        $this->subcategories_id = (string) ($inventory->subcategories_id ?? '');
        $this->price = (string) $inventory->price;
        $this->size = (string) $inventory->size;
        $this->color = (string) $inventory->color;
        $this->type = (string) $inventory->type;
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
     * Store the changes of the item.
     */
    public function save(InventoryService $inventory): void
    {
        Gate::authorize('update', $this->inventory);

        $validated = $this->validate(InventoryRules::item($this->categories_id));

        $inventory->update($this->inventory, $validated);

        $this->redirect(route('inventory.show', $this->inventory), navigate: true);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header>
        <flux:heading level="1" size="xl">{{ __('Edit inventory item') }}</flux:heading>
        <flux:text class="mt-1">{{ $inventory->item_name }}</flux:text>
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
        </flux:card>

        <flux:card>
            <flux:heading size="lg">{{ __('Stock') }}</flux:heading>
            <flux:text>
                {{ __('The quantity is changed with a stock adjustment, so every change carries a reason and is kept in the history.') }}
            </flux:text>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
                <flux:badge size="lg" color="zinc">
                    {{ __('Quantity on hand') }}: {{ $inventory->current_quantity ?? '—' }}
                </flux:badge>

                <flux:button :href="route('inventory.show', $inventory)" variant="ghost" icon="arrows-up-down">
                    {{ __('Adjust the stock') }}
                </flux:button>
            </div>
        </flux:card>

        <div class="flex justify-end gap-3">
            <flux:button :href="route('inventory.show', $inventory)" variant="ghost"> {{ __('Cancel') }} </flux:button>

            <flux:button variant="primary" type="submit"> {{ __('Save') }} </flux:button>
        </div>
    </form>
</div>
