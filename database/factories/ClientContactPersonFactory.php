<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientContactPerson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClientContactPerson>
 */
class ClientContactPersonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_person' => Str::title(fake()->name()),
            'contact_number' => (string) fake()->unique()->numerify('09#########'),
            'contact_email' => fake()->unique()->safeEmail(),
            'client_id' => Client::factory(),
        ];
    }
}
