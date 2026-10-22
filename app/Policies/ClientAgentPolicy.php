<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ClientAgent;
use App\Models\User;

/**
 * The abilities on the agents that acquired the clients.
 *
 * Every ability is driven by a permission of the application, so the roles
 * decide what an account holder may do with the agents.
 */
class ClientAgentPolicy
{
    /**
     * Determine whether the user may list the agents.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewClientAgents);
    }

    /**
     * Determine whether the user may see the given agent.
     */
    public function view(User $user, ClientAgent $clientAgent): bool
    {
        return $user->can(Permission::ViewClientAgents);
    }

    /**
     * Determine whether the user may add an agent.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::CreateClientAgents);
    }

    /**
     * Determine whether the user may change the given agent.
     */
    public function update(User $user, ClientAgent $clientAgent): bool
    {
        return $user->can(Permission::UpdateClientAgents);
    }

    /**
     * Determine whether the user may remove the given agent.
     */
    public function delete(User $user, ClientAgent $clientAgent): bool
    {
        return $user->can(Permission::DeleteClientAgents);
    }
}
