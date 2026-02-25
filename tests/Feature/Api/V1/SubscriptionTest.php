<?php

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

describe('GET /api/v1/plans', function () {
    it('returns only active plans', function () {
        Plan::factory()->count(3)->create();
        Plan::factory()->inactive()->create();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/plans')
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'price_in_cents', 'price_formatted', 'billing_cycle', 'trial_days']]]);
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/plans')->assertUnauthorized();
    });
});

describe('GET /api/v1/subscription', function () {
    it('returns the active subscription with plan', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()->active()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/subscription')
            ->assertSuccessful()
            ->assertJsonPath('data.id', $subscription->id)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonStructure(['data' => ['plan']]);
    });

    it('returns 404 when user has no active subscription', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/subscription')
            ->assertNotFound();
    });

    it('returns 404 when subscription is canceled', function () {
        $user = User::factory()->create();
        Subscription::factory()->canceled()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/subscription')
            ->assertNotFound();
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/subscription')->assertUnauthorized();
    });
});

describe('POST /api/v1/subscription', function () {
    it('subscribes to a plan without trial and returns active', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['trial_days' => 0]);

        $this->actingAs($user)
            ->postJson('/api/v1/subscription', ['plan_id' => $plan->id])
            ->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.plan.id', $plan->id);

        expect(Subscription::where('user_id', $user->id)->count())->toBe(1);
    });

    it('subscribes to a plan with trial and returns trialing', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->withTrial(14)->create();

        $this->actingAs($user)
            ->postJson('/api/v1/subscription', ['plan_id' => $plan->id])
            ->assertCreated()
            ->assertJsonPath('data.status', 'trialing');

        $subscription = Subscription::where('user_id', $user->id)->first();
        expect($subscription->trial_ends_at)->not->toBeNull()
            ->and($subscription->status)->toBe(SubscriptionStatus::Trialing);
    });

    it('returns 422 when user already has an active subscription', function () {
        $user = User::factory()->create();
        Subscription::factory()->active()->create(['user_id' => $user->id]);
        $plan = Plan::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/subscription', ['plan_id' => $plan->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.subscription.0', 'User already has an active subscription.');
    });

    it('returns 422 for an inactive plan', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->inactive()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/subscription', ['plan_id' => $plan->id])
            ->assertUnprocessable();
    });

    it('requires authentication', function () {
        $this->postJson('/api/v1/subscription', [])->assertUnauthorized();
    });
});

describe('PATCH /api/v1/subscription/plan', function () {
    it('changes the plan successfully', function () {
        $user = User::factory()->create();
        $currentPlan = Plan::factory()->create(['price_in_cents' => 2990]);
        $newPlan = Plan::factory()->create(['price_in_cents' => 9990]);
        Subscription::factory()->active()->create([
            'user_id' => $user->id,
            'plan_id' => $currentPlan->id,
        ]);

        $this->actingAs($user)
            ->patchJson('/api/v1/subscription/plan', ['plan_id' => $newPlan->id])
            ->assertSuccessful()
            ->assertJsonPath('data.plan.id', $newPlan->id);
    });

    it('returns 422 when user has no active subscription', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/v1/subscription/plan', ['plan_id' => $plan->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.subscription.0', 'No active subscription found.');
    });

    it('returns 422 when already on the same plan', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        Subscription::factory()->active()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        $this->actingAs($user)
            ->patchJson('/api/v1/subscription/plan', ['plan_id' => $plan->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.plan_id.0', 'Already subscribed to this plan.');
    });

    it('requires authentication', function () {
        $this->patchJson('/api/v1/subscription/plan', [])->assertUnauthorized();
    });
});

describe('DELETE /api/v1/subscription', function () {
    it('cancels the subscription and sets cancel_at to period end', function () {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->active()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->deleteJson('/api/v1/subscription')
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'canceled');

        $fresh = $subscription->fresh();
        expect($fresh->status)->toBe(SubscriptionStatus::Canceled)
            ->and($fresh->canceled_at)->not->toBeNull()
            ->and($fresh->cancel_at->toDateString())->toBe($subscription->current_period_end->toDateString())
            ->and($fresh->auto_renew)->toBeFalse();
    });

    it('returns 422 when user has no active subscription', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson('/api/v1/subscription')
            ->assertUnprocessable()
            ->assertJsonPath('errors.subscription.0', 'No active subscription found.');
    });

    it('requires authentication', function () {
        $this->deleteJson('/api/v1/subscription')->assertUnauthorized();
    });
});
