<?php

namespace Database\Factories;

use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $invoice = Invoice::factory()->create();

        return [
            'user_id' => $invoice->user_id,
            'invoice_id' => $invoice->id,
            'payment_method_id' => null,
            'amount_in_cents' => $invoice->amount_in_cents,
            'currency' => 'BRL',
            'status' => PaymentStatus::Pending,
            'payment_method_type' => PaymentMethodType::CreditCard,
            'gateway' => 'stub',
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now()->subHours(fake()->numberBetween(1, 48)),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
            'failed_at' => now()->subHours(fake()->numberBetween(1, 24)),
            'failure_reason' => fake()->randomElement(['insufficient_funds', 'card_declined', 'expired_card']),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
        ]);
    }

    public function viaPix(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method_type' => PaymentMethodType::Pix,
            'pix_qr_code' => fake()->sha256(),
            'pix_expires_at' => now()->addHours(1),
        ]);
    }

    public function viaBoleto(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method_type' => PaymentMethodType::Boleto,
            'boleto_url' => 'https://boleto.example.com/'.fake()->uuid(),
            'boleto_barcode' => fake()->numerify('####.##### #####.###### #####.###### # ##############'),
            'boleto_expires_at' => now()->addDays(3),
        ]);
    }
}
