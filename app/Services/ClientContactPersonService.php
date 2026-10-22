<?php

namespace App\Services;

use App\Models\ClientContactPerson;
use App\Models\Order;
use Illuminate\Validation\ValidationException;

/**
 * Writes the contract persons of the clients.
 *
 * A contract person that a job order points at is never removed, because the
 * job order would lose the person it was placed with.
 */
class ClientContactPersonService
{
    /**
     * Create a contract person.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ClientContactPerson
    {
        return ClientContactPerson::create([
            'contact_person' => $data['contact_person'],
            'contact_number' => $data['contact_number'],
            'contact_email' => $data['contact_email'],
            'client_id' => $data['client_id'],
        ]);
    }

    /**
     * Change the details of a contract person.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ClientContactPerson $clientContactPerson, array $data): ClientContactPerson
    {
        $clientContactPerson->fill([
            'contact_person' => $data['contact_person'],
            'contact_number' => $data['contact_number'],
            'contact_email' => $data['contact_email'],
            'client_id' => $data['client_id'],
        ]);

        $clientContactPerson->save();

        return $clientContactPerson;
    }

    /**
     * Remove a contract person.
     *
     * A contract person that is named on a job order cannot be removed, so
     * the job orders keep the person they were placed with.
     */
    public function delete(ClientContactPerson $clientContactPerson): void
    {
        if ($this->isReferencedByAnOrder($clientContactPerson)) {
            throw ValidationException::withMessages([
                'clientContactPerson' => __('This contract person is named on a job order and cannot be removed.'),
            ]);
        }

        $clientContactPerson->delete();
    }

    /**
     * Whether a job order still points at the contract person.
     */
    protected function isReferencedByAnOrder(ClientContactPerson $clientContactPerson): bool
    {
        return Order::query()
            ->where('contact_person_id', $clientContactPerson->getKey())
            ->exists();
    }
}
