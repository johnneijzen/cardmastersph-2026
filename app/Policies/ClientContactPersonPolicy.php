<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ClientContactPerson;
use App\Models\User;

/**
 * The abilities on the contract persons of the clients.
 *
 * Every ability is driven by a permission of the application, so the roles
 * decide what an account holder may do with the contract persons.
 */
class ClientContactPersonPolicy
{
    /**
     * Determine whether the user may list the contract persons.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewClientContactPersons);
    }

    /**
     * Determine whether the user may see the given contract person.
     */
    public function view(User $user, ClientContactPerson $clientContactPerson): bool
    {
        return $user->can(Permission::ViewClientContactPersons);
    }

    /**
     * Determine whether the user may add a contract person.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::CreateClientContactPersons);
    }

    /**
     * Determine whether the user may change the given contract person.
     */
    public function update(User $user, ClientContactPerson $clientContactPerson): bool
    {
        return $user->can(Permission::UpdateClientContactPersons);
    }

    /**
     * Determine whether the user may remove the given contract person.
     */
    public function delete(User $user, ClientContactPerson $clientContactPerson): bool
    {
        return $user->can(Permission::DeleteClientContactPersons);
    }
}
