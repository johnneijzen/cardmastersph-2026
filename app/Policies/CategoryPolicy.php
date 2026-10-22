<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Category;
use App\Models\User;

/**
 * The abilities on the inventory categories.
 *
 * Every ability is driven by a permission of the application, so the roles
 * decide what an account holder may do with the categories.
 */
class CategoryPolicy
{
    /**
     * Determine whether the user may list the categories.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewCategories);
    }

    /**
     * Determine whether the user may see the given category.
     */
    public function view(User $user, Category $category): bool
    {
        return $user->can(Permission::ViewCategories);
    }

    /**
     * Determine whether the user may add a category.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::CreateCategories);
    }

    /**
     * Determine whether the user may change the given category.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->can(Permission::UpdateCategories);
    }

    /**
     * Determine whether the user may remove the given category.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->can(Permission::DeleteCategories);
    }
}
