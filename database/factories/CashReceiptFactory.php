<?php

namespace Database\Factories;

use App\Models\CashReceipt;
use App\Models\Client;
use App\Models\JobOrder;
use App\Models\PaymentOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashReceipt>
 */
class CashReceiptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 100, 5000);

        return [
            'date' => now()->toDateString(),
            'or_number' => 'OR-'.fake()->unique()->numerify('######'),
            'amount' => $amount,
            'balance' => 0.0,
            'client_id' => Client::factory(),
            'job_order_id' => JobOrder::factory(),
            'payment_option_id' => PaymentOption::factory(),
        ];
    }
}
