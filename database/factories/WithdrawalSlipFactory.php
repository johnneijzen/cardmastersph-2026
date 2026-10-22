<?php

namespace Database\Factories;

use App\Models\Client;
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
            'date' => now()->toDateString(),
            'client_id' => Client::factory(),
            'job_order_id' => JobOrder::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
