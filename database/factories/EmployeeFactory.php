<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => Str::title(fake()->firstName()),
            'middle_name' => Str::title(fake()->firstName()),
            'last_name' => Str::title(fake()->lastName()),
            'position' => 'Artist',
            'birthday' => fake()->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
            'contact_number' => (string) fake()->unique()->numerify('09#########'),
            'emergency_contact_person_name' => Str::title(fake()->name()),
            'emergency_contact_person_number' => (string) fake()->unique()->numerify('09#########'),
            'address_id' => Address::factory(),
        ];
    }

    /**
     * Indicate that the employee is assigned to a user account.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }
}
