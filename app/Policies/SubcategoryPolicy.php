<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Subcategory;
use App\Models\User;

/**
 * The abilities on the inventory subcategories.
 *
 * Every ability is driven by a permission of the application, so the roles
 * decide what an account holder may do with the subcategories.
 */
class SubcategoryPolicy
{
    /**
     * Determine whether the user may list the subcategories.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewSubcategories);
    }

    /**
     * Determine whether the user may see the given subcategory.
     */
    public function view(User $user, Subcategory $subcategory): bool
    {
        return $user->can(Permission::ViewSubcategories);
    }

    /**
     * Determine whether the user may add a subcategory.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::CreateSubcategories);
    }

    /**
     * Determine whether the user may change the given subcategory.
     */
    public function update(User $user, Subcategory $subcategory): bool
    {
        return $user->can(Permission::UpdateSubcategories);
    }

    /**
     * Determine whether the user may remove the given subcategory.
     */
    public function delete(User $user, Subcategory $subcategory): bool
    {
        return $user->can(Permission::DeleteSubcategories);
    }
}
