<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Client;
use App\Models\ClientAgent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Str::title(fake()->unique()->company()),
            'client_number' => (string) fake()->unique()->numerify('09#########'),
            'address_id' => Address::factory(),
            'agent_id' => ClientAgent::factory(),
        ];
    }
}
