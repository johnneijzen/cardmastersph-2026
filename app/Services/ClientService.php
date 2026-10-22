<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

/**
 * Writes the client records and the addresses that belong to them.
 *
 * Every write that touches more than one table runs in a transaction, so a
 * failed write never leaves a client without an address behind.
 */
class ClientService
{
    /**
     * Create a client together with its address.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Client
    {
        return DB::transaction(function () use ($data): Client {
            $address = Address::create([
                'line_address' => $data['line_address'],
                'city_id' => $data['city_id'],
            ]);

            return Client::create([
                'name' => $data['name'],
                'client_number' => $data['client_number'],
                'address_id' => $address->getKey(),
                'agent_id' => $data['agent_id'] ?? null,
            ]);
        });
    }

    /**
     * Change the details of a client.
     *
     * A client without an address, which happens with the clients that were
     * created by an import, receives a new address instead of failing.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Client $client, array $data): Client
    {
        return DB::transaction(function () use ($client, $data): Client {
            $address = $client->address;

            if ($address === null) {
                $address = Address::create([
                    'line_address' => $data['line_address'],
                    'city_id' => $data['city_id'],
                ]);

                $client->address_id = $address->getKey();
            } else {
                $address->update([
                    'line_address' => $data['line_address'],
                    'city_id' => $data['city_id'],
                ]);
            }

            $client->fill([
                'name' => $data['name'],
                'client_number' => $data['client_number'],
                'agent_id' => $data['agent_id'] ?? null,
            ]);

            $client->save();

            return $client;
        });
    }

    /**
     * Remove the client from the active list.
     *
     * The client is archived and never deleted, so the job orders, the cash
     * receipts and the withdrawal slips that point at it keep their history
     * and their link to the client.
     */
    public function archive(Client $client): void
    {
        $client->delete();
    }

    /**
     * Put an archived client back on the active list.
     */
    public function restore(Client $client): void
    {
        $client->restore();
    }
}
