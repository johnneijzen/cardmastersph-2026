<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\WithdrawalSlip;

/**
 * The abilities on the inventory withdrawal slips.
 *
 * Every ability is driven by a permission of the application, so the roles
 * decide who may take stock out of the inventory and who may reverse a slip.
 */
class WithdrawalSlipPolicy
{
    /**
     * Determine whether the user may list the withdrawal slips.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewWithdrawalSlips);
    }

    /**
     * Determine whether the user may see the given slip.
     */
    public function view(User $user, WithdrawalSlip $withdrawalSlip): bool
    {
        return $user->can(Permission::ViewWithdrawalSlips);
    }

    /**
     * Determine whether the user may issue a withdrawal slip.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::CreateWithdrawalSlips);
    }

    /**
     * Determine whether the user may reverse the given slip.
     */
    public function delete(User $user, WithdrawalSlip $withdrawalSlip): bool
    {
        return $user->can(Permission::DeleteWithdrawalSlips);
    }
}
