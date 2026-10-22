<?php

namespace Database\Factories;

use App\Enums\JobOrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\ClientContactPerson;
use App\Models\JobOrderAgent;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalAmount = fake()->randomFloat(2, 500, 50000);
        $downPayment = 0.0;

        return [
            'order_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => JobOrderStatus::InProduction->value,
            'total_amount' => $totalAmount,
            'down_payment' => $downPayment,
            'balance' => $totalAmount - $downPayment,
            'payment_status' => PaymentStatus::Unpaid->value,
            'overall_remarks' => fake()->sentence(),
            'client_id' => Client::factory(),
            'contact_person_id' => ClientContactPerson::factory(),
            'agent_id' => JobOrderAgent::factory(),
        ];
    }

    /**
     * Indicate that the order is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'due_date' => now()->subDays(7)->toDateString(),
        ]);
    }

    /**
     * Indicate that the order is already completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => JobOrderStatus::CompletedClaimed->value,
        ]);
    }
}
