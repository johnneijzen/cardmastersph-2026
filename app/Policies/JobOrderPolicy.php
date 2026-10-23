<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\JobOrder;
use App\Models\User;

/**
 * The abilities on the job orders.
 *
 * Every ability is driven by a permission of the application, so the roles
 * decide what an account holder may do with the job orders and their
 * invoices.
 */
class JobOrderPolicy
{
    /**
     * Determine whether the user may list the job orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewJobOrders);
    }

    /**
     * Determine whether the user may see the given job order.
     */
    public function view(User $user, JobOrder $jobOrder): bool
    {
        return $user->can(Permission::ViewJobOrders);
    }

    /**
     * Determine whether the user may add a job order.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::CreateJobOrders);
    }

    /**
     * Determine whether the user may change the given job order.
     */
    public function update(User $user, JobOrder $jobOrder): bool
    {
        return $user->can(Permission::UpdateJobOrders);
    }

    /**
     * Determine whether the user may archive the given job order.
     */
    public function archive(User $user, JobOrder $jobOrder): bool
    {
        return $user->can(Permission::ArchiveJobOrders);
    }

    /**
     * Determine whether the user may restore the given job order.
     */
    public function restore(User $user, JobOrder $jobOrder): bool
    {
        return $user->can(Permission::RestoreJobOrders);
    }

    /**
     * Determine whether the user may export the job orders.
     */
    public function export(User $user): bool
    {
        return $user->can(Permission::ExportJobOrders);
    }

    /**
     * Determine whether the user may import job orders.
     */
    public function import(User $user): bool
    {
        return $user->can(Permission::ImportJobOrders);
    }
}
