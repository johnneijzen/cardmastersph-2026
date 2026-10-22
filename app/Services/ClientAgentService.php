<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientAgent;
use Illuminate\Validation\ValidationException;

/**
 * Writes the agents that acquired the clients.
 *
 * An agent that still points at a client is never removed, so the clients
 * keep the channel they were acquired through.
 */
class ClientAgentService
{
    /**
     * Create an agent.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ClientAgent
    {
        return ClientAgent::create([
            'name' => $data['name'],
        ]);
    }

    /**
     * Change the name of an agent.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ClientAgent $clientAgent, array $data): ClientAgent
    {
        $clientAgent->fill([
            'name' => $data['name'],
        ]);

        $clientAgent->save();

        return $clientAgent;
    }

    /**
     * Remove an agent.
     *
     * An agent that clients still point at cannot be removed, because the
     * clients would lose the channel they were acquired through.
     */
    public function delete(ClientAgent $clientAgent): void
    {
        if ($this->isUsedByAClient($clientAgent)) {
            throw ValidationException::withMessages([
                'clientAgent' => __('This agent is still linked to a client and cannot be removed. Move the client to another agent first.'),
            ]);
        }

        $clientAgent->delete();
    }

    /**
     * Whether a client still points at the agent.
     */
    protected function isUsedByAClient(ClientAgent $clientAgent): bool
    {
        return Client::query()
            ->where('agent_id', $clientAgent->getKey())
            ->exists();
    }
}
