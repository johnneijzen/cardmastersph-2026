<?php

namespace App\Livewire\Subcategories;

use App\Models\Category;
use App\Models\Subcategory;
use App\Services\SubcategoryService;
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
 * The list of the inventory subcategories.
 *
 * The table only ever holds the records that the signed in user is allowed to
 * see, because the ability to list the subcategories is checked before the
 * query is built.
 */
class SubcategoriesTable extends DataTableComponent
{
    /**
     * The subcategory that is about to be removed.
     */
    #[Locked]
    public ?int $deletingSubcategoryId = null;

    /**
     * Whether the confirmation of the removal is shown.
     */
    public bool $showDeleteModal = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', Subcategory::class);
    }

    /**
     * The configuration of the table.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setEmptyMessage(__('No subcategories match the current filters.'))
            ->setDefaultSort('name', 'asc')
            ->setDefaultPerPage(25)
            ->setPerPageAccepted([10, 25, 50, 100]);

        $this->setTableName('subcategories-table');
    }

    /**
     * The query behind the table.
     *
     * @return Builder<Subcategory>
     */
    public function builder(): Builder
    {
        return Subcategory::query()
            ->with('category')
            ->withCount('inventory');
    }

    /**
     * The subcategory that is about to be removed.
     */
    #[Computed]
    public function deletingSubcategory(): ?Subcategory
    {
        if ($this->deletingSubcategoryId === null) {
            return null;
        }

        return Subcategory::query()
            ->with('category')
            ->withCount('inventory')
            ->find($this->deletingSubcategoryId);
    }

    /**
     * Ask for a confirmation before a subcategory is removed.
     */
    public function confirmDelete(int $subcategoryId): void
    {
        $subcategory = Subcategory::query()->findOrFail($subcategoryId);

        Gate::authorize('delete', $subcategory);

        $this->deletingSubcategoryId = $subcategoryId;
        $this->showDeleteModal = true;
        $this->resetErrorBag();
    }

    /**
     * Remove the subcategory that was confirmed for removal.
     */
    public function deleteSubcategory(SubcategoryService $subcategories): void
    {
        $subcategory = Subcategory::query()->findOrFail($this->deletingSubcategoryId);

        Gate::authorize('delete', $subcategory);

        $subcategories->delete($subcategory);

        $this->deletingSubcategoryId = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('The subcategory was removed.'));
    }

    /**
     * Render the list.
     */
    public function render(): View
    {
        return view('livewire.subcategories.subcategories-table');
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

            Column::make(__('Name'), 'name')
                ->searchable()
                ->sortable(),

            Column::make(__('Category'), 'category.name')
                ->searchable()
                ->sortable(),

            Column::make(__('Inventory items'))
                ->label(fn (Subcategory $subcategory): string => number_format((int) $subcategory->inventory_count)),

            Column::make(__('Actions'))
                ->label(fn (Subcategory $subcategory): string => view('livewire.subcategories.actions', [
                    'subcategory' => $subcategory,
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
                    $builder->where('categories_id', $value);
                }),
        ];
    }

    /**
     * The categories a subcategory can belong to.
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
}
