<?php

namespace App\Livewire\ProductSubclasses;

use App\Models\ProductClass;
use App\Models\ProductSubclass;
use App\Services\ProductSubclassService;
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
 * The list of the job order subclasses.
 *
 * The table only ever holds the records that the signed in user is allowed to
 * see, because the ability to list the subclasses is checked before the query
 * is built.
 */
class ProductSubclassesTable extends DataTableComponent
{
    /**
     * The subclass that is about to be archived.
     */
    #[Locked]
    public ?int $archivingProductSubclassId = null;

    /**
     * Whether the confirmation of the archive action is shown.
     */
    public bool $showArchiveModal = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', ProductSubclass::class);
    }

    /**
     * The configuration of the table.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setEmptyMessage(__('No subclasses match the current filters.'))
            ->setDefaultSort('name', 'asc')
            ->setDefaultPerPage(25)
            ->setPerPageAccepted([10, 25, 50, 100]);

        $this->setTableName('product-subclasses-table');
    }

    /**
     * The query behind the table.
     *
     * @return Builder<ProductSubclass>
     */
    public function builder(): Builder
    {
        return ProductSubclass::query()
            ->select('product_subclasses.*')
            ->with('productClass')
            ->withCount('products');
    }

    /**
     * The subclass that is about to be archived.
     */
    #[Computed]
    public function archivingProductSubclass(): ?ProductSubclass
    {
        if ($this->archivingProductSubclassId === null) {
            return null;
        }

        return ProductSubclass::query()
            ->with('productClass')
            ->withCount('products')
            ->find($this->archivingProductSubclassId);
    }

    /**
     * Ask for a confirmation before a subclass is archived.
     */
    public function confirmArchive(int $productSubclassId): void
    {
        $productSubclass = ProductSubclass::query()->findOrFail($productSubclassId);

        Gate::authorize('delete', $productSubclass);

        $this->archivingProductSubclassId = $productSubclassId;
        $this->showArchiveModal = true;
        $this->resetErrorBag();
    }

    /**
     * Archive the subclass that was confirmed for archiving.
     */
    public function archiveProductSubclass(ProductSubclassService $productSubclasses): void
    {
        $productSubclass = ProductSubclass::query()->findOrFail($this->archivingProductSubclassId);

        Gate::authorize('delete', $productSubclass);

        $productSubclasses->archive($productSubclass);

        $this->archivingProductSubclassId = null;
        $this->showArchiveModal = false;

        Flux::toast(variant: 'success', text: __('The subclass was archived.'));
    }

    /**
     * Render the list.
     */
    public function render(): View
    {
        return view('livewire.product-subclasses.product-subclasses-table');
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

            Column::make(__('Class'), 'productClass.name')
                ->searchable()
                ->sortable(),

            Column::make(__('Products'))
                ->label(fn (ProductSubclass $productSubclass): string => number_format((int) $productSubclass->products_count)),

            Column::make(__('Actions'))
                ->label(fn (ProductSubclass $productSubclass): string => view('livewire.product-subclasses.actions', [
                    'productSubclass' => $productSubclass,
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
            SelectFilter::make(__('Class'))
                ->options($this->classOptions())
                ->filter(function (Builder $builder, string $value): void {
                    if ($value === 'unclassified') {
                        $builder->whereNull('product_class_id');

                        return;
                    }

                    $builder->where('product_class_id', $value);
                }),
        ];
    }

    /**
     * The classes a subclass can belong to, including the archived ones so a
     * subclass that still points at an archived class can be found.
     *
     * @return array<string, string>
     */
    protected function classOptions(): array
    {
        $options = ProductClass::withTrashed()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->mapWithKeys(fn (string $name, int $id): array => [(string) $id => $name])
            ->all();

        return [
            'unclassified' => __('Unclassified'),
        ] + $options;
    }
}
