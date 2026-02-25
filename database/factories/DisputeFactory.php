<?php

namespace Database\Factories;

use App\Enums\DisputeReason;
use App\Enums\DisputeStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dispute>
 */
class DisputeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'payment_id' => Payment::factory(),
            'status' => DisputeStatus::Open,
            'reason' => fake()->randomElement(DisputeReason::cases()),
            'description' => fake()->optional()->sentence(),
            'gateway_dispute_id' => 'stub_dispute_'.fake()->lexify('????????????????'),
            'resolved_at' => null,
            'withdrawn_at' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DisputeStatus::Open,
        ]);
    }

    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DisputeStatus::UnderReview,
        ]);
    }

    public function won(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DisputeStatus::Won,
            'resolved_at' => now(),
        ]);
    }

    public function withdrawn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DisputeStatus::Withdrawn,
            'withdrawn_at' => now(),
        ]);
    }
}
