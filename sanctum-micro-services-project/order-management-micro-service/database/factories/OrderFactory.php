<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "order_number" => 'ORD-' . strtoupper(uniqid()),
            "user_id" => $this->faker->numberBetween(1, 12),
            "total_amount" => $this->faker->randomFloat(2, 10, 1000),
            "tax_amount" => $this->faker->randomFloat(2, 1, 100),
            "shipping_amount" => $this->faker->randomFloat(2, 5, 50),
            "discount_amount" => $this->faker->randomFloat(2, 0, 20),
            "grand_total" => $this->faker->randomFloat(2, 10, 1000),
            "currency" => "CAD",
            "shipping_address" => $this->faker->address(),
            "billing_address" => $this->faker->address(),
            "payment_method" => $this->faker->randomElement(["credit_card", "debit_card", "paypal"]),
            "status" => $this->faker->randomElement(["pending", "processing", "completed", "cancelled"]),
        ];
    }
}
