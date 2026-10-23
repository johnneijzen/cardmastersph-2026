<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ProductSubclass;
use App\Models\User;

/**
 * The abilities on the job order subclasses.
 *
 * Every ability is driven by a permission of the application, so the roles
 * decide what an account holder may do with the subclasses.
 */
class ProductSubclassPolicy
{
    /**
     * Determine whether the user may list the subclasses.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewProductSubclasses);
    }

    /**
     * Determine whether the user may see the given subclass.
     */
    public function view(User $user, ProductSubclass $productSubclass): bool
    {
        return $user->can(Permission::ViewProductSubclasses);
    }

    /**
     * Determine whether the user may add a subclass.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::CreateProductSubclasses);
    }

    /**
     * Determine whether the user may change the given subclass.
     */
    public function update(User $user, ProductSubclass $productSubclass): bool
    {
        return $user->can(Permission::UpdateProductSubclasses);
    }

    /**
     * Determine whether the user may archive the given subclass.
     */
    public function delete(User $user, ProductSubclass $productSubclass): bool
    {
        return $user->can(Permission::ArchiveProductSubclasses);
    }

    /**
     * Determine whether the user may restore the given subclass.
     */
    public function restore(User $user, ProductSubclass $productSubclass): bool
    {
        return $user->can(Permission::RestoreProductSubclasses);
    }
}
