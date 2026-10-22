<?php

namespace App\Livewire\Inventory;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Subcategory;
use App\Services\InventoryService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

/**
 * The list of the inventory items.
 *
 * The table only ever holds the records that the signed in user is allowed to
 * see, because the ability to list the inventory is checked before the query
 * is built. Every searchable and sortable column is named explicitly, so no
 * value of the request is ever used as a column name.
 */
class InventoryTable extends DataTableComponent
{
    /**
     * The item that is about to be removed.
     */
    #[Locked]
    public ?int $deletingInventoryId = null;

    /**
     * Whether the confirmation of the removal is shown.
     */
    public bool $showDeleteModal = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', Inventory::class);
    }

    /**
     * The configuration of the table.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setEmptyMessage(__('No inventory items match the current filters.'))
            ->setDefaultSort('item_name', 'asc')
            ->setDefaultPerPage(25)
            ->setPerPageAccepted([10, 25, 50, 100]);

        $this->setTableName('inventory-table');
    }

    /**
     * The query behind the table.
     *
     * @return Builder<Inventory>
     */
    public function builder(): Builder
    {
        return Inventory::query()
            ->with(['category', 'subcategory']);
    }

    /**
     * The item that is about to be removed.
     */
    #[Computed]
    public function deletingInventory(): ?Inventory
    {
        if ($this->deletingInventoryId === null) {
            return null;
        }

        return Inventory::query()
            ->with(['category', 'subcategory'])
            ->find($this->deletingInventoryId);
    }

    /**
     * Ask for a confirmation before an item is removed.
     */
    public function confirmDelete(int $inventoryId): void
    {
        $inventory = Inventory::query()->findOrFail($inventoryId);

        Gate::authorize('delete', $inventory);

        $this->deletingInventoryId = $inventoryId;
        $this->showDeleteModal = true;
        $this->resetErrorBag();
    }

    /**
     * Remove the item that was confirmed for removal.
     */
    public function deleteInventory(InventoryService $inventory): void
    {
        $item = Inventory::query()->findOrFail($this->deletingInventoryId);

        Gate::authorize('delete', $item);

        $inventory->delete($item);

        $this->deletingInventoryId = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('The inventory item was removed.'));
    }

    /**
     * Render the list.
     */
    public function render(): View
    {
        return view('livewire.inventory.inventory-table');
    }

    /**
     * The columns of the table.
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('ID'), 'id')
                ->sortable(),

            Column::make(__('Item name'), 'item_name')
                ->searchable()
                ->sortable(),

            Column::make(__('Category'), 'category.name')
                ->searchable()
                ->sortable(),

            Column::make(__('Subcategory'), 'subcategory.name')
                ->searchable()
                ->sortable(),

            Column::make(__('Size'), 'size')
                ->searchable(),

            Column::make(__('Color'), 'color')
                ->searchable(),

            Column::make(__('Type'), 'type')
                ->searchable(),

            Column::make(__('Price'), 'price')
                ->label(fn (Inventory $inventory): string => $this->formatPrice($inventory))
                ->sortable(fn (Builder $builder, string $direction): Builder => $builder
                    ->orderByRaw($direction === 'desc'
                        ? 'CAST(inventory.price AS DECIMAL(13, 2)) DESC'
                        : 'CAST(inventory.price AS DECIMAL(13, 2)) ASC')),

            Column::make(__('Quantity'), 'current_quantity')
                ->label(fn (Inventory $inventory): string => $inventory->current_quantity === null
                    ? '—'
                    : (string) $inventory->current_quantity)
                ->sortable(fn (Builder $builder, string $direction): Builder => $builder
                    ->orderByRaw($direction === 'desc'
                        ? 'CAST(inventory.current_quantity AS SIGNED) DESC'
                        : 'CAST(inventory.current_quantity AS SIGNED) ASC')),

            Column::make(__('Actions'))
                ->label(fn (Inventory $inventory): string => view('livewire.inventory.actions', [
                    'inventory' => $inventory,
                ])->render())
                ->html(),
        ];
    }

    /**
     * The filters of the table.
     *
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        return [
            SelectFilter::make(__('Category'))
                ->options($this->categoryOptions())
                ->filter(function (Builder $builder, string $value): void {
                    $builder->where('inventory.categories_id', $value);
                }),

            SelectFilter::make(__('Subcategory'))
                ->options($this->subcategoryOptions())
                ->filter(function (Builder $builder, string $value): void {
                    $builder->where('inventory.subcategories_id', $value);
                }),

            SelectFilter::make(__('Stock'))
                ->options([
                    'in' => __('In stock'),
                    'out' => __('Out of stock'),
                ])
                ->filter(function (Builder $builder, string $value): void {
                    match ($value) {
                        'in' => $builder
                            ->whereNotNull('inventory.current_quantity')
                            ->whereRaw('CAST(inventory.current_quantity AS SIGNED) > 0'),
                        'out' => $builder->where(function (Builder $items): void {
                            $items->whereNull('inventory.current_quantity')
                                ->orWhereRaw('CAST(inventory.current_quantity AS SIGNED) <= 0');
                        }),
                        default => null,
                    };
                }),

            SelectFilter::make(__('Type'))
                ->options($this->typeOptions())
                ->filter(function (Builder $builder, string $value): void {
                    $builder->where('inventory.type', $value);
                }),
        ];
    }

    /**
     * The categories the inventory is grouped in.
     *
     * @return array<string, string>
     */
    protected function categoryOptions(): array
    {
        return Category::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * The subcategories the inventory is grouped in.
     *
     * @return array<string, string>
     */
    protected function subcategoryOptions(): array
    {
        return Subcategory::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * The types the items are stored under.
     *
     * @return array<string, string>
     */
    protected function typeOptions(): array
    {
        return Inventory::query()
            ->select('type')
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type', 'type')
            ->all();
    }

    /**
     * The price of an item, written with two decimals.
     */
    protected function formatPrice(Inventory $inventory): string
    {
        if ($inventory->price === null || $inventory->price === '') {
            return '—';
        }

        return number_format((float) $inventory->price, 2);
    }
}
