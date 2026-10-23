<?php

namespace App\Livewire\JobOrders;

use App\Enums\JobOrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Employee;
use App\Models\JobOrder;
use App\Models\JobOrderAgent;
use App\Services\JobOrderService;
use App\Support\Money\MoneyContext;
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
 * The list of the job orders.
 *
 * The table only ever holds the job orders that the signed in user is allowed
 * to see, because the ability to list the job orders is checked before the
 * query is built. Every searchable, sortable and filterable column is named
 * explicitly, so no request value is ever used as a column name.
 */
class JobOrdersTable extends DataTableComponent
{
    /**
     * The job order that is about to be archived.
     */
    #[Locked]
    public ?int $archivingJobOrderId = null;

    /**
     * Whether the confirmation of the archive is shown.
     */
    public bool $showArchiveModal = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', JobOrder::class);
    }

    /**
     * The configuration of the table.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setEmptyMessage(__('No job orders match the current filters.'))
            ->setDefaultSort('id', 'desc')
            ->setDefaultPerPage(25)
            ->setPerPageAccepted([10, 25, 50, 100]);

        $this->setTableName('job-orders-table');
    }

    /**
     * The query behind the table.
     *
     * @return Builder<JobOrder>
     */
    public function builder(): Builder
    {
        return JobOrder::query()
            ->select('job_orders.*')
            ->with([
                'order.client',
                'order.job_order_agent',
                'employee',
            ]);
    }

    /**
     * The job order that is about to be archived.
     */
    #[Computed]
    public function archivingJobOrder(): ?JobOrder
    {
        if ($this->archivingJobOrderId === null) {
            return null;
        }

        return JobOrder::query()
            ->with(['order.client'])
            ->find($this->archivingJobOrderId);
    }

    /**
     * Ask for a confirmation before a job order is archived.
     */
    public function confirmArchive(int $jobOrderId): void
    {
        $jobOrder = JobOrder::query()->findOrFail($jobOrderId);

        Gate::authorize('archive', $jobOrder);

        $this->archivingJobOrderId = $jobOrderId;
        $this->showArchiveModal = true;
        $this->resetErrorBag();
    }

    /**
     * Archive the job order that was confirmed for archiving.
     */
    public function archiveJobOrder(JobOrderService $jobOrders): void
    {
        $jobOrder = JobOrder::query()->findOrFail($this->archivingJobOrderId);

        Gate::authorize('archive', $jobOrder);

        $jobOrders->archive($jobOrder);

        $this->archivingJobOrderId = null;
        $this->showArchiveModal = false;

        Flux::toast(variant: 'success', text: __('The job order was archived.'));
    }

    /**
     * Render the list.
     */
    public function render(): View
    {
        return view('livewire.job-orders.job-orders-table');
    }

    /**
     * The columns of the table.
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('Job order'), 'job_order_number')
                ->searchable()
                ->sortable(),

            Column::make(__('Client'), 'order.client.name')
                ->searchable()
                ->sortable(),

            Column::make(__('Agent'), 'order.job_order_agent.name')
                ->searchable()
                ->sortable(),

            Column::make(__('Artist'), 'employee.first_name')
                ->searchable()
                ->sortable()
                ->format(fn (mixed $value, JobOrder $jobOrder): string => $jobOrder->employee?->full_name ?? '—'),

            Column::make(__('Status'), 'order.status')
                ->sortable()
                ->format(fn (mixed $value): string => view('livewire.job-orders.status-badge', [
                    'label' => (string) $value,
                ])->render())
                ->html(),

            Column::make(__('Due date'), 'order.due_date')
                ->sortable()
                ->format(fn (mixed $value, JobOrder $jobOrder): string => view('livewire.job-orders.due-date', [
                    'order' => $jobOrder->order,
                ])->render())
                ->html(),

            Column::make(__('Total'), 'order.total_amount')
                ->sortable()
                ->format(fn (mixed $value): string => MoneyContext::formatStorage($value)),

            Column::make(__('Balance'), 'order.balance')
                ->sortable()
                ->format(fn (mixed $value): string => MoneyContext::formatStorage($value)),

            Column::make(__('Payment'), 'order.payment_status')
                ->sortable()
                ->format(fn (mixed $value): string => view('livewire.job-orders.payment-badge', [
                    'status' => (string) $value,
                ])->render())
                ->html(),

            Column::make(__('Actions'))
                ->label(fn (JobOrder $jobOrder): string => view('livewire.job-orders.actions', [
                    'jobOrder' => $jobOrder,
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
                ->options($this->statusOptions())
                ->filter(fn (Builder $builder, string $value) => $builder->whereHas(
                    'order',
                    fn (Builder $orders) => $orders->where('status', $value),
                )),

            SelectFilter::make(__('Payment'))
                ->options($this->paymentStatusOptions())
                ->filter(fn (Builder $builder, string $value) => $builder->whereHas(
                    'order',
                    fn (Builder $orders) => $orders->where('payment_status', $value),
                )),

            SelectFilter::make(__('Artist'))
                ->options($this->employeeOptions())
                ->filter(fn (Builder $builder, string $value) => $builder->where('employee_id', $value)),

            SelectFilter::make(__('Agent'))
                ->options($this->agentOptions())
                ->filter(fn (Builder $builder, string $value) => $builder->whereHas(
                    'order',
                    fn (Builder $orders) => $orders->where('agent_id', $value),
                )),

            SelectFilter::make(__('Due'))
                ->options([
                    'overdue' => __('Overdue'),
                    'open' => __('Not overdue'),
                ])
                ->filter(function (Builder $builder, string $value): void {
                    if ($value === 'overdue') {
                        $builder->whereHas('order', fn (Builder $orders) => $orders
                            ->whereDate('due_date', '<', now())
                            ->whereNotIn('status', JobOrderStatus::completedValues()));

                        return;
                    }

                    $builder->whereHas('order', fn (Builder $orders) => $orders
                        ->whereDate('due_date', '>=', now())
                        ->orWhereIn('status', JobOrderStatus::completedValues()));
                }),
        ];
    }

    /**
     * The statuses a job order can be filtered by.
     *
     * @return array<string, string>
     */
    protected function statusOptions(): array
    {
        return collect(JobOrderStatus::cases())
            ->mapWithKeys(fn (JobOrderStatus $status): array => [$status->value => $status->label()])
            ->all();
    }

    /**
     * The payment statuses a job order can be filtered by.
     *
     * @return array<string, string>
     */
    protected function paymentStatusOptions(): array
    {
        return collect(PaymentStatus::cases())
            ->mapWithKeys(fn (PaymentStatus $status): array => [$status->value => $status->label()])
            ->all();
    }

    /**
     * The artists a job order can be filtered by.
     *
     * @return array<int, string>
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

    /**
     * The agents a job order can be filtered by.
     *
     * @return array<int, string>
     */
    protected function agentOptions(): array
    {
        return JobOrderAgent::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
