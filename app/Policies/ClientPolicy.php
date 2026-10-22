<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Client;
use App\Models\User;

/**
 * The abilities on the client records.
 *
 * Every ability is driven by a permission of the application, so the roles
 * decide what an account holder may do with the client records.
 */
class ClientPolicy
{
    /**
     * Determine whether the user may list the clients.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewClients);
    }

    /**
     * Determine whether the user may see the given client.
     */
    public function view(User $user, Client $client): bool
    {
        return $user->can(Permission::ViewClients);
    }

    /**
     * Determine whether the user may add a client.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::CreateClients);
    }

    /**
     * Determine whether the user may change the given client.
     */
    public function update(User $user, Client $client): bool
    {
        return $user->can(Permission::UpdateClients);
    }

    /**
     * Determine whether the user may remove the client from the active list.
     *
     * A client is archived and never deleted, so the job orders, the cash
     * receipts and the withdrawal slips that point at it keep their history.
     */
    public function archive(User $user, Client $client): bool
    {
        return $user->can(Permission::DeleteClients);
    }

    /**
     * Determine whether the user may restore an archived client.
     */
    public function restore(User $user, Client $client): bool
    {
        return $user->can(Permission::RestoreClients);
    }

    /**
     * Determine whether the user may export the clients.
     */
    public function export(User $user): bool
    {
        return $user->can(Permission::ExportClients);
    }
}
