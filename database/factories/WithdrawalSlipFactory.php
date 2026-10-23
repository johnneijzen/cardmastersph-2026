<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Employee;
use App\Models\JobOrder;
use App\Models\User;
use App\Models\WithdrawalSlip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WithdrawalSlip>
 */
class WithdrawalSlipFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slip_number' => 'WS-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'date' => now()->toDateString(),
            'client_id' => Client::factory(),
            'job_order_id' => JobOrder::factory(),
            'employee_id' => Employee::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the slip was reversed.
     */
    public function reversed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'deleted_at' => now(),
        ]);
    }
}
