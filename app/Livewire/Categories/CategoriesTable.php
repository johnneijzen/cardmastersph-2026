<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use App\Services\CategoryService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

/**
 * The list of the inventory categories.
 *
 * The table only ever holds the records that the signed in user is allowed to
 * see, because the ability to list the categories is checked before the query
 * is built.
 */
class CategoriesTable extends DataTableComponent
{
    /**
     * The category that is about to be removed.
     */
    #[Locked]
    public ?int $deletingCategoryId = null;

    /**
     * Whether the confirmation of the removal is shown.
     */
    public bool $showDeleteModal = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', Category::class);
    }

    /**
     * The configuration of the table.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setEmptyMessage(__('No categories match the current filters.'))
            ->setDefaultSort('name', 'asc')
            ->setDefaultPerPage(25)
            ->setPerPageAccepted([10, 25, 50, 100]);

        $this->setTableName('categories-table');
    }

    /**
     * The query behind the table.
     *
     * @return Builder<Category>
     */
    public function builder(): Builder
    {
        return Category::query()
            ->withCount(['subcategories', 'inventory']);
    }

    /**
     * The category that is about to be removed.
     */
    #[Computed]
    public function deletingCategory(): ?Category
    {
        if ($this->deletingCategoryId === null) {
            return null;
        }

        return Category::query()
            ->withCount(['subcategories', 'inventory'])
            ->find($this->deletingCategoryId);
    }

    /**
     * Ask for a confirmation before a category is removed.
     */
    public function confirmDelete(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);

        Gate::authorize('delete', $category);

        $this->deletingCategoryId = $categoryId;
        $this->showDeleteModal = true;
        $this->resetErrorBag();
    }

    /**
     * Remove the category that was confirmed for removal.
     */
    public function deleteCategory(CategoryService $categories): void
    {
        $category = Category::query()->findOrFail($this->deletingCategoryId);

        Gate::authorize('delete', $category);

        $categories->delete($category);

        $this->deletingCategoryId = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('The category was removed.'));
    }

    /**
     * Render the list.
     */
    public function render(): View
    {
        return view('livewire.categories.categories-table');
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

            Column::make(__('Subcategories'))
                ->label(fn (Category $category): string => number_format((int) $category->subcategories_count)),

            Column::make(__('Inventory items'))
                ->label(fn (Category $category): string => number_format((int) $category->inventory_count)),

            Column::make(__('Actions'))
                ->label(fn (Category $category): string => view('livewire.categories.actions', [
                    'category' => $category,
                ])->render())
                ->html(),
        ];
    }
}
