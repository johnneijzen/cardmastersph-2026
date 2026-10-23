<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Inventory;
use App\Models\User;

/**
 * The abilities on the inventory items.
 *
 * Every ability is driven by a permission of the application, so the roles
 * decide what an account holder may do with the inventory.
 */
class InventoryPolicy
{
    /**
     * Determine whether the user may list the inventory.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewInventory);
    }

    /**
     * Determine whether the user may see the given item.
     */
    public function view(User $user, Inventory $inventory): bool
    {
        return $user->can(Permission::ViewInventory);
    }

    /**
     * Determine whether the user may add an item.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::CreateInventory);
    }

    /**
     * Determine whether the user may import inventory records.
     */
    public function import(User $user): bool
    {
        return $user->can(Permission::ImportInventory);
    }

    /**
     * Determine whether the user may change the given item.
     *
     * The same ability covers the stock adjustments, because a quantity may
     * only be changed by somebody who is allowed to change the item.
     */
    public function update(User $user, Inventory $inventory): bool
    {
        return $user->can(Permission::UpdateInventory);
    }

    /**
     * Determine whether the user may remove the given item from the active list.
     */
    public function delete(User $user, Inventory $inventory): bool
    {
        return $user->can(Permission::DeleteInventory);
    }
}
