<?php

namespace App\Services;

use App\Enums\JobOrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Models\CashReceipt;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\JobOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Builds the figures shown on the dashboard.
 *
 * Every section is only calculated when the signed in user is allowed to see
 * it, so a user without financial permissions never triggers the financial
 * queries.
 */
class DashboardService
{
    /**
     * The statistics for the signed in user.
     *
     * @return array<string, mixed>
     */
    public function statistics(User $user): array
    {
        return [
            'jobOrders' => $this->jobOrderStatistics($user),
            'clients' => $this->clientStatistics($user),
            'employees' => $this->employeeStatistics($user),
            'inventory' => $this->inventoryStatistics($user),
            'cashReceipts' => $this->cashReceiptStatistics($user),
            'tasks' => $this->taskStatistics($user),
        ];
    }

    /**
     * The job orders that are due within the given number of days.
     *
     * These are the upcoming activities of the business. The legacy system
     * had no task entity, so the due date of a job order is the deadline.
     *
     * @return Collection<int, Order>
     */
    public function upcomingActivities(User $user, int $days = 14): Collection
    {
        if (! $user->can(Permission::ViewJobOrders)) {
            return new Collection;
        }

        return Order::query()
            ->whereHas('jobOrder')
            ->with(['client', 'jobOrder.employee'])
            ->whereDate('due_date', '<=', now()->addDays($days))
            ->whereNotIn('status', JobOrderStatus::completedValues())
            ->orderBy('due_date')
            ->limit(25)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function jobOrderStatistics(User $user): array
    {
        if (! $user->can(Permission::ViewJobOrders)) {
            return [];
        }

        $orders = Order::query()->whereHas('jobOrder');

        return [
            'total' => (clone $orders)->count(),
            'open' => (clone $orders)
                ->whereNotIn('status', JobOrderStatus::completedValues())
                ->count(),
            'byStatus' => (clone $orders)
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status')
                ->all(),
            'overdue' => (clone $orders)
                ->whereDate('due_date', '<', now())
                ->whereNotIn('status', JobOrderStatus::completedValues())
                ->count(),
            'dueSoon' => (clone $orders)
                ->whereDate('due_date', '>=', now())
                ->whereDate('due_date', '<=', now()->addDays(7))
                ->whereNotIn('status', JobOrderStatus::completedValues())
                ->count(),
            'archived' => JobOrder::onlyTrashed()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function clientStatistics(User $user): array
    {
        if (! $user->can(Permission::ViewClients)) {
            return [];
        }

        $statistics = [
            'total' => Client::count(),
            'newThisMonth' => Client::whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ])->count(),
        ];

        if ($user->can(Permission::ViewAccountsReceivable)) {
            $statistics['withOutstandingBalance'] = Order::query()
                ->where('balance', '>', 0)
                ->distinct()
                ->count('client_id');
        }

        return $statistics;
    }

    /**
     * @return array<string, mixed>
     */
    protected function employeeStatistics(User $user): array
    {
        if (! $user->can(Permission::ViewEmployees)) {
            return [];
        }

        return [
            'total' => Employee::count(),
            'artists' => Employee::where('position', 'Artist')->count(),
            'withAccount' => Employee::whereNotNull('user_id')->count(),
            'assigned' => Employee::query()
                ->whereHas('jobOrders')
                ->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function inventoryStatistics(User $user): array
    {
        if (! $user->can(Permission::ViewInventory)) {
            return [];
        }

        return [
            'total' => Inventory::count(),
            'outOfStock' => Inventory::where(function ($query): void {
                $query->whereNull('current_quantity')
                    ->orWhere('current_quantity', '=', 0);
            })->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function cashReceiptStatistics(User $user): array
    {
        if (! $user->can(Permission::ViewCashReceipts)) {
            return [];
        }

        $receivedThisMonth = (float) CashReceipt::whereBetween('date', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ])->sum('amount');

        $statistics = [
            'receivedThisMonth' => $receivedThisMonth,
            'receiptsThisMonth' => CashReceipt::whereBetween('date', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ])->count(),
        ];

        if ($user->can(Permission::ViewAccountsReceivable)) {
            $statistics['outstanding'] = (float) Order::sum('balance');
            $statistics['byPaymentStatus'] = Order::query()
                ->selectRaw('payment_status, count(*) as aggregate')
                ->groupBy('payment_status')
                ->pluck('aggregate', 'payment_status')
                ->all();
        }

        return $statistics;
    }

    /**
     * The tasks the user still has to deal with.
     *
     * @return array<string, mixed>
     */
    protected function taskStatistics(User $user): array
    {
        if (! $user->can(Permission::ViewAllTasks)
            && ! $user->can(Permission::ViewOwnTasks)) {
            return [];
        }

        $query = $this->taskScope($user);

        return [
            'dueSoon' => (clone $query)
                ->whereDate('due_date', '>=', now())
                ->whereDate('due_date', '<=', now()->addDays(7))
                ->count(),
            'overdue' => (clone $query)
                ->whereDate('due_date', '<', now())
                ->count(),
            'unpaid' => (clone $query)
                ->where('payment_status', '!=', PaymentStatus::FullyPaid->value)
                ->count(),
        ];
    }

    /**
     * The job orders the user is allowed to see as tasks.
     *
     * A user that may only see their own tasks gets the job orders of the
     * employee record that is linked to their account.
     *
     * @return Builder<Order>
     */
    protected function taskScope(User $user): Builder
    {
        $query = Order::query()
            ->whereHas('jobOrder')
            ->whereNotIn('status', JobOrderStatus::completedValues());

        if ($user->can(Permission::ViewAllTasks)) {
            return $query;
        }

        return $query->whereHas('jobOrder.employee', function ($employees) use ($user): void {
            $employees->where('user_id', $user->getKey());
        });
    }
}
