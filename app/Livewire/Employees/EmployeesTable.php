<?php

namespace App\Livewire\Employees;

use App\Models\Employee;
use App\Models\Province;
use App\Services\EmployeeService;
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
 * The list of the employees.
 *
 * The table only ever holds the records that the signed in user is allowed to
 * see, because the ability to list the employees is checked before the query
 * is built.
 */
class EmployeesTable extends DataTableComponent
{
    /**
     * The employee that is about to be removed.
     */
    #[Locked]
    public ?int $deletingEmployeeId = null;

    /**
     * Whether the confirmation of the removal is shown.
     */
    public bool $showDeleteModal = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', Employee::class);
    }

    /**
     * The configuration of the table.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setEmptyMessage(__('No employees match the current filters.'))
            ->setDefaultSort('last_name', 'asc')
            ->setDefaultPerPage(25)
            ->setPerPageAccepted([10, 25, 50, 100]);

        $this->setTableName('employees-table');
    }

    /**
     * The query behind the table.
     *
     * @return Builder<Employee>
     */
    public function builder(): Builder
    {
        return Employee::query()
            ->with(['address.city.province', 'user']);
    }

    /**
     * The employee that is about to be removed.
     */
    #[Computed]
    public function deletingEmployee(): ?Employee
    {
        if ($this->deletingEmployeeId === null) {
            return null;
        }

        return Employee::query()
            ->with(['address.city.province', 'user'])
            ->find($this->deletingEmployeeId);
    }

    /**
     * Ask for a confirmation before an employee is removed.
     */
    public function confirmDelete(int $employeeId): void
    {
        $employee = Employee::query()->findOrFail($employeeId);

        Gate::authorize('delete', $employee);

        $this->deletingEmployeeId = $employeeId;
        $this->showDeleteModal = true;
        $this->resetErrorBag();
    }

    /**
     * Remove the employee that was confirmed for removal.
     */
    public function deleteEmployee(EmployeeService $employees): void
    {
        $employee = Employee::query()->findOrFail($this->deletingEmployeeId);

        Gate::authorize('delete', $employee);

        $employees->delete($employee);

        $this->deletingEmployeeId = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('The employee was removed.'));
    }

    /**
     * Render the list.
     */
    public function render(): View
    {
        return view('livewire.employees.employees-table');
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

            Column::make(__('First name'), 'first_name')
                ->searchable()
                ->sortable(),

            Column::make(__('Middle name'), 'middle_name')
                ->searchable(),

            Column::make(__('Last name'), 'last_name')
                ->searchable()
                ->sortable(),

            Column::make(__('Position'), 'position')
                ->searchable()
                ->sortable(),

            Column::make(__('E-mail address'), 'user.email')
                ->searchable(),

            Column::make(__('City'), 'address.city.name'),

            Column::make(__('Province'), 'address.city.province.name'),

            Column::make(__('Actions'))
                ->label(fn (Employee $employee): string => view('livewire.employees.actions', [
                    'employee' => $employee,
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
            SelectFilter::make(__('Position'))
                ->options($this->positionOptions())
                ->filter(function (Builder $builder, string $value): void {
                    $builder->where('position', $value);
                }),

            SelectFilter::make(__('Province'))
                ->options($this->provinceOptions())
                ->filter(function (Builder $builder, string $value): void {
                    $builder->whereHas('address.city', function (Builder $cities) use ($value): void {
                        $cities->where('province_id', $value);
                    });
                }),

            SelectFilter::make(__('Account'))
                ->options([
                    'active' => __('Active'),
                    'inactive' => __('Deactivated'),
                    'without' => __('Without an account'),
                ])
                ->filter(function (Builder $builder, string $value): void {
                    match ($value) {
                        'active' => $builder->whereHas('user', fn (Builder $users) => $users->where('is_active', true)),
                        'inactive' => $builder->whereHas('user', fn (Builder $users) => $users->where('is_active', false)),
                        'without' => $builder->whereDoesntHave('user'),
                        default => null,
                    };
                }),
        ];
    }

    /**
     * The positions that the employees use.
     *
     * @return array<string, string>
     */
    protected function positionOptions(): array
    {
        return Employee::query()
            ->select('position')
            ->distinct()
            ->orderBy('position')
            ->pluck('position', 'position')
            ->all();
    }

    /**
     * The provinces that the employees are located in.
     *
     * @return array<string, string>
     */
    protected function provinceOptions(): array
    {
        return Province::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
