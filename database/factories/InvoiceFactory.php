<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    private static int $sequence = 1;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amountInCents = fake()->randomElement([2990, 4990, 9990, 19990]);

        return [
            'user_id' => User::factory(),
            'subscription_id' => null,
            'invoice_number' => 'INV-'.date('Y').'-'.str_pad((string) self::$sequence++, 5, '0', STR_PAD_LEFT),
            'status' => InvoiceStatus::Open,
            'amount_in_cents' => $amountInCents,
            'amount_paid_in_cents' => 0,
            'currency' => 'BRL',
            'description' => fake()->sentence(),
            'due_date' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Open,
            'amount_paid_in_cents' => 0,
            'paid_at' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Paid,
            'amount_paid_in_cents' => $attributes['amount_in_cents'],
            'paid_at' => now()->subDays(fake()->numberBetween(1, 10)),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Open,
            'amount_paid_in_cents' => 0,
            'due_date' => now()->subDays(fake()->numberBetween(1, 30))->toDateString(),
            'paid_at' => null,
        ]);
    }

    public function forSubscription(Subscription $subscription): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'amount_in_cents' => $subscription->plan->price_in_cents,
        ]);
    }
}
