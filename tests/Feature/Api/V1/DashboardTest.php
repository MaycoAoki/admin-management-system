<?php

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

describe('GET /api/v1/dashboard', function () {
    it('returns dashboard data for user with active subscription and open invoices', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price_in_cents' => 9990]);
        Subscription::factory()->active()->create(['user_id' => $user->id, 'plan_id' => $plan->id]);
        Invoice::factory()->open()->count(2)->create(['user_id' => $user->id]);
        Invoice::factory()->overdue()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/dashboard')
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'balance' => ['outstanding_in_cents', 'outstanding_formatted', 'open_invoices_count', 'overdue_invoices_count'],
                    'next_due',
                    'subscription' => ['status', 'plan_name', 'billing_cycle', 'price_in_cents', 'price_formatted'],
                ],
            ])
            ->assertJsonPath('data.balance.open_invoices_count', 3)
            ->assertJsonPath('data.balance.overdue_invoices_count', 1)
            ->assertJsonPath('data.subscription.plan_name', $plan->name);
    });

    it('returns null subscription when user has no active subscription', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/dashboard')
            ->assertSuccessful()
            ->assertJsonPath('data.subscription', null)
            ->assertJsonPath('data.balance.outstanding_in_cents', 0)
            ->assertJsonPath('data.next_due', null);
    });

    it('returns null next_due when all open invoices are overdue', function () {
        $user = User::factory()->create();
        Invoice::factory()->overdue()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/dashboard')
            ->assertSuccessful()
            ->assertJsonPath('data.next_due', null)
            ->assertJsonPath('data.balance.overdue_invoices_count', 1);
    });

    it('calculates outstanding balance correctly', function () {
        $user = User::factory()->create();
        Invoice::factory()->open()->create(['user_id' => $user->id, 'amount_in_cents' => 9990, 'amount_paid_in_cents' => 0]);
        Invoice::factory()->open()->create(['user_id' => $user->id, 'amount_in_cents' => 4990, 'amount_paid_in_cents' => 2000]);

        $this->actingAs($user)
            ->getJson('/api/v1/dashboard')
            ->assertSuccessful()
            ->assertJsonPath('data.balance.outstanding_in_cents', 12980);
    });

    it('does not include paid invoices in balance', function () {
        $user = User::factory()->create();
        Invoice::factory()->paid()->create(['user_id' => $user->id, 'amount_in_cents' => 9990]);

        $this->actingAs($user)
            ->getJson('/api/v1/dashboard')
            ->assertSuccessful()
            ->assertJsonPath('data.balance.outstanding_in_cents', 0)
            ->assertJsonPath('data.balance.open_invoices_count', 0);
    });

    it('does not leak data from other users', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $plan = Plan::factory()->create();
        Subscription::factory()->active()->create(['user_id' => $other->id, 'plan_id' => $plan->id]);
        Invoice::factory()->open()->count(3)->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/dashboard')
            ->assertSuccessful()
            ->assertJsonPath('data.balance.open_invoices_count', 0)
            ->assertJsonPath('data.subscription', null);
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    });
});
