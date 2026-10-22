<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\JobOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobOrder>
 */
class JobOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'order_id' => Order::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'job_order_rating' => fake()->numberBetween(1, 10),
        ];
    }
}
