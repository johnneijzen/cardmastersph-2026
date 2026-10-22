<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Employee;
use App\Models\User;

/**
 * The abilities on the employee records.
 *
 * Every ability is driven by a permission of the application, so the roles
 * decide what an account holder may do with the personnel records.
 */
class EmployeePolicy
{
    /**
     * Determine whether the user may list the employees.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewEmployees);
    }

    /**
     * Determine whether the user may see the given employee.
     */
    public function view(User $user, Employee $employee): bool
    {
        return $user->can(Permission::ViewEmployees);
    }

    /**
     * Determine whether the user may add an employee.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::CreateEmployees);
    }

    /**
     * Determine whether the user may change the given employee.
     */
    public function update(User $user, Employee $employee): bool
    {
        return $user->can(Permission::UpdateEmployees);
    }

    /**
     * Determine whether the user may remove the given employee.
     */
    public function delete(User $user, Employee $employee): bool
    {
        return $user->can(Permission::DeleteEmployees);
    }

    /**
     * Determine whether the user may manage the account of the employee.
     *
     * The account status and the role of an employee are part of the
     * administration of the personnel records, so they need their own
     * permission.
     */
    public function manageAccount(User $user, Employee $employee): bool
    {
        return $user->can(Permission::ManageEmployeeAccounts);
    }
}
