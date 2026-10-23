<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ProductClass;
use App\Models\User;

/**
 * The abilities on the job order classes.
 *
 * Every ability is driven by a permission of the application, so the roles
 * decide what an account holder may do with the classes.
 */
class ProductClassPolicy
{
    /**
     * Determine whether the user may list the classes.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewProductClasses);
    }

    /**
     * Determine whether the user may see the given class.
     */
    public function view(User $user, ProductClass $productClass): bool
    {
        return $user->can(Permission::ViewProductClasses);
    }

    /**
     * Determine whether the user may add a class.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::CreateProductClasses);
    }

    /**
     * Determine whether the user may change the given class.
     */
    public function update(User $user, ProductClass $productClass): bool
    {
        return $user->can(Permission::UpdateProductClasses);
    }

    /**
     * Determine whether the user may archive the given class.
     */
    public function delete(User $user, ProductClass $productClass): bool
    {
        return $user->can(Permission::ArchiveProductClasses);
    }

    /**
     * Determine whether the user may restore the given class.
     */
    public function restore(User $user, ProductClass $productClass): bool
    {
        return $user->can(Permission::RestoreProductClasses);
    }
}
