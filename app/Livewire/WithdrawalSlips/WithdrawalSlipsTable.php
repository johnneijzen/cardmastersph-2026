<?php

namespace App\Livewire\WithdrawalSlips;

use App\Models\Client;
use App\Models\Employee;
use App\Models\InventoryWithdrawalSlip;
use App\Models\WithdrawalSlip;
use App\Services\WithdrawalSlipService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

/**
 * The list of the inventory withdrawal slips.
 *
 * The table only ever holds the slips that the signed in user is allowed to
 * see, because the ability to list the slips is checked before the query is
 * built. Every searchable and sortable column is named explicitly, so no
 * value of the request is ever used as a column name.
 */
class WithdrawalSlipsTable extends DataTableComponent
{
    /**
     * The slip that is about to be reversed.
     */
    #[Locked]
    public ?int $reversingSlipId = null;

    /**
     * Whether the confirmation of the reversal is shown.
     */
    public bool $showReverseModal = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', WithdrawalSlip::class);
    }

    /**
     * The configuration of the table.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setEmptyMessage(__('No withdrawal slips match the current filters.'))
            ->setDefaultSort('date', 'desc')
            ->setDefaultPerPage(25)
            ->setPerPageAccepted([10, 25, 50, 100]);

        $this->setTableName('withdrawal-slips-table');
    }

    /**
     * The query behind the table.
     *
     * @return Builder<WithdrawalSlip>
     */
    public function builder(): Builder
    {
        return WithdrawalSlip::query()
            ->with(['client', 'jobOrder', 'employee', 'createdBy'])
            ->withCount('inventory')
            ->addSelect([
                'withdrawn_quantity' => InventoryWithdrawalSlip::query()
                    ->selectRaw('COALESCE(SUM(qty), 0)')
                    ->whereColumn('inventory_withdrawal_slips.withdrawal_slips_id', 'withdrawal_slips.id'),
            ]);
    }

    /**
     * The slip that is about to be reversed.
     */
    #[Computed]
    public function reversingSlip(): ?WithdrawalSlip
    {
        if ($this->reversingSlipId === null) {
            return null;
        }

        return WithdrawalSlip::query()
            ->with(['client', 'jobOrder', 'employee'])
            ->find($this->reversingSlipId);
    }

    /**
     * Ask for a confirmation before a slip is reversed.
     */
    public function confirmReverse(int $slipId): void
    {
        $slip = WithdrawalSlip::query()->findOrFail($slipId);

        Gate::authorize('delete', $slip);

        $this->reversingSlipId = $slipId;
        $this->showReverseModal = true;
        $this->resetErrorBag();
    }

    /**
     * Reverse the slip that was confirmed for reversal.
     */
    public function reverseSlip(WithdrawalSlipService $slips): void
    {
        $slip = WithdrawalSlip::query()->findOrFail($this->reversingSlipId);

        Gate::authorize('delete', $slip);

        $actorId = auth()->id();

        $slips->reverse($slip, $actorId === null ? null : (int) $actorId);

        $this->reversingSlipId = null;
        $this->showReverseModal = false;

        Flux::toast(variant: 'success', text: __('The withdrawal slip was reversed and the stock was put back.'));
    }

    /**
     * Render the list.
     */
    public function render(): View
    {
        return view('livewire.withdrawal-slips.withdrawal-slips-table');
    }

    /**
     * The columns of the table.
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('Slip number'), 'slip_number')
                ->searchable()
                ->sortable(),

            Column::make(__('Date'), 'date')
                ->sortable(),

            Column::make(__('Client'), 'client.name')
                ->searchable()
                ->sortable(),

            Column::make(__('Job order'), 'job_order_id')
                ->label(fn (WithdrawalSlip $slip): string => $slip->job_order_id === null ? '—' : '#'.$slip->job_order_id)
                ->sortable(),

            Column::make(__('Employee'), 'employee.first_name')
                ->searchable()
                ->sortable()
                ->label(fn (WithdrawalSlip $slip): string => $slip->employeeName() ?? '—'),

            Column::make(__('Items'), 'inventory_count')
                ->label(fn (WithdrawalSlip $slip): string => number_format((int) $slip->inventory_count)),

            Column::make(__('Quantity'), 'withdrawn_quantity')
                ->label(fn (WithdrawalSlip $slip): string => number_format((int) $slip->withdrawn_quantity)),

            Column::make(__('Recorded by'), 'createdBy.name'),

            Column::make(__('Actions'))
                ->label(fn (WithdrawalSlip $slip): string => view('livewire.withdrawal-slips.actions', [
                    'withdrawalSlip' => $slip,
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
            SelectFilter::make(__('Status'))
                ->options([
                    'active' => __('Active'),
                    'archived' => __('Reversed'),
                    'all' => __('Active and reversed'),
                ])
                ->setFilterDefaultValue('active')
                ->filter(function (Builder $builder, string $value): void {
                    if ($value === 'active') {
                        return;
                    }

                    // The soft delete scope is removed, so the reversed slips
                    // can be listed. A reversed slip carries a deletion date.
                    $builder->withoutGlobalScope(SoftDeletingScope::class);

                    if ($value === 'archived') {
                        $builder->whereNotNull('withdrawal_slips.deleted_at');
                    }
                }),

            SelectFilter::make(__('Client'))
                ->options($this->clientOptions())
                ->filter(function (Builder $builder, string $value): void {
                    $builder->where('withdrawal_slips.client_id', $value);
                }),

            SelectFilter::make(__('Employee'))
                ->options($this->employeeOptions())
                ->filter(function (Builder $builder, string $value): void {
                    $builder->where('withdrawal_slips.employee_id', $value);
                }),

            DateFilter::make(__('From'))
                ->filter(function (Builder $builder, string $value): void {
                    $builder->whereDate('withdrawal_slips.date', '>=', $value);
                }),

            DateFilter::make(__('To'))
                ->filter(function (Builder $builder, string $value): void {
                    $builder->whereDate('withdrawal_slips.date', '<=', $value);
                }),
        ];
    }

    /**
     * The clients the slips were issued for.
     *
     * @return array<string, string>
     */
    protected function clientOptions(): array
    {
        return Client::withTrashed()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * The employees that may be responsible for a withdrawal.
     *
     * @return array<string, string>
     */
    protected function employeeOptions(): array
    {
        return Employee::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn (Employee $employee): array => [$employee->getKey() => $employee->full_name])
            ->all();
    }
}
