<?php

namespace Database\Factories;

use App\Enums\PaymentMethodType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => PaymentMethodType::CreditCard,
            'is_default' => false,
            'gateway' => 'stub',
            'gateway_token' => fake()->uuid(),
            'last_four' => fake()->numerify('####'),
            'brand' => fake()->randomElement(['visa', 'mastercard', 'amex', 'elo']),
            'expiry_month' => fake()->numberBetween(1, 12),
            'expiry_year' => fake()->numberBetween(date('Y') + 1, date('Y') + 5),
            'holder_name' => fake()->name(),
        ];
    }

    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentMethodType::CreditCard,
            'last_four' => fake()->numerify('####'),
            'brand' => fake()->randomElement(['visa', 'mastercard', 'elo']),
        ]);
    }

    public function pix(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentMethodType::Pix,
            'last_four' => null,
            'brand' => null,
            'expiry_month' => null,
            'expiry_year' => null,
            'pix_key' => fake()->email(),
        ]);
    }

    public function boleto(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentMethodType::Boleto,
            'last_four' => null,
            'brand' => null,
            'expiry_month' => null,
            'expiry_year' => null,
        ]);
    }

    public function asDefault(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
