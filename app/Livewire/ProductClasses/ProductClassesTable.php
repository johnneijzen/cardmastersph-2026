<?php

namespace App\Livewire\ProductClasses;

use App\Models\ProductClass;
use App\Services\ProductClassService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

/**
 * The list of the job order classes.
 *
 * The table only ever holds the records that the signed in user is allowed to
 * see, because the ability to list the classes is checked before the query is
 * built.
 */
class ProductClassesTable extends DataTableComponent
{
    /**
     * The class that is about to be archived.
     */
    #[Locked]
    public ?int $archivingProductClassId = null;

    /**
     * Whether the confirmation of the archive action is shown.
     */
    public bool $showArchiveModal = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', ProductClass::class);
    }

    /**
     * The configuration of the table.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setEmptyMessage(__('No classes match the current filters.'))
            ->setDefaultSort('name', 'asc')
            ->setDefaultPerPage(25)
            ->setPerPageAccepted([10, 25, 50, 100]);

        $this->setTableName('product-classes-table');
    }

    /**
     * The query behind the table.
     *
     * @return Builder<ProductClass>
     */
    public function builder(): Builder
    {
        return ProductClass::query()
            ->select('product_classes.*')
            ->withCount(['subclasses', 'products']);
    }

    /**
     * The class that is about to be archived.
     */
    #[Computed]
    public function archivingProductClass(): ?ProductClass
    {
        if ($this->archivingProductClassId === null) {
            return null;
        }

        return ProductClass::query()
            ->withCount(['subclasses', 'products'])
            ->find($this->archivingProductClassId);
    }

    /**
     * Ask for a confirmation before a class is archived.
     */
    public function confirmArchive(int $productClassId): void
    {
        $productClass = ProductClass::query()->findOrFail($productClassId);

        Gate::authorize('delete', $productClass);

        $this->archivingProductClassId = $productClassId;
        $this->showArchiveModal = true;
        $this->resetErrorBag();
    }

    /**
     * Archive the class that was confirmed for archiving.
     */
    public function archiveProductClass(ProductClassService $productClasses): void
    {
        $productClass = ProductClass::query()->findOrFail($this->archivingProductClassId);

        Gate::authorize('delete', $productClass);

        $productClasses->archive($productClass);

        $this->archivingProductClassId = null;
        $this->showArchiveModal = false;

        Flux::toast(variant: 'success', text: __('The class was archived.'));
    }

    /**
     * Render the list.
     */
    public function render(): View
    {
        return view('livewire.product-classes.product-classes-table');
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

            Column::make(__('Subclasses'))
                ->label(fn (ProductClass $productClass): string => number_format((int) $productClass->subclasses_count)),

            Column::make(__('Products'))
                ->label(fn (ProductClass $productClass): string => number_format((int) $productClass->products_count)),

            Column::make(__('Actions'))
                ->label(fn (ProductClass $productClass): string => view('livewire.product-classes.actions', [
                    'productClass' => $productClass,
                ])->render())
                ->html(),
        ];
    }
}
