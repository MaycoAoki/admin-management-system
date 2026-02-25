<?php

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;

describe('GET /api/v1/payment-methods', function () {
    it('returns payment methods for the authenticated user', function () {
        $user = User::factory()->create();
        PaymentMethod::factory()->creditCard()->asDefault()->create(['user_id' => $user->id]);
        PaymentMethod::factory()->pix()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/payment-methods')
            ->assertSuccessful()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'type', 'is_default', 'brand', 'last_four', 'created_at']]]);
    });

    it('does not return methods from other users', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        PaymentMethod::factory()->count(3)->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/payment-methods')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('returns an empty array when user has no methods', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/payment-methods')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/payment-methods')->assertUnauthorized();
    });
});

describe('POST /api/v1/payment-methods', function () {
    it('adds a credit card and returns 201', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/payment-methods', [
                'type' => 'credit_card',
                'last_four' => '4242',
                'brand' => 'visa',
                'expiry_month' => 12,
                'expiry_year' => date('Y') + 2,
                'holder_name' => 'João Silva',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'credit_card')
            ->assertJsonPath('data.last_four', '4242')
            ->assertJsonPath('data.brand', 'visa');
    });

    it('adds a PIX key and returns 201', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/payment-methods', [
                'type' => 'pix',
                'pix_key' => 'joao@email.com',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'pix')
            ->assertJsonPath('data.pix_key', 'joao@email.com');
    });

    it('sets first method as default automatically', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/payment-methods', [
                'type' => 'credit_card',
                'last_four' => '1234',
                'brand' => 'mastercard',
                'expiry_month' => 6,
                'expiry_year' => date('Y') + 1,
                'holder_name' => 'João Silva',
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_default', true);
    });

    it('does not set subsequent methods as default', function () {
        $user = User::factory()->create();
        PaymentMethod::factory()->creditCard()->asDefault()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson('/api/v1/payment-methods', [
                'type' => 'credit_card',
                'last_four' => '9999',
                'brand' => 'elo',
                'expiry_month' => 3,
                'expiry_year' => date('Y') + 2,
                'holder_name' => 'João Silva',
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_default', false);
    });

    it('returns 422 when card fields are missing', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/payment-methods', ['type' => 'credit_card'])
            ->assertUnprocessable();
    });

    it('returns 422 when expiry_year is in the past', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/payment-methods', [
                'type' => 'credit_card',
                'last_four' => '4242',
                'brand' => 'visa',
                'expiry_month' => 1,
                'expiry_year' => date('Y') - 1,
                'holder_name' => 'João Silva',
            ])
            ->assertUnprocessable();
    });

    it('returns 422 when pix_key is missing for pix type', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/payment-methods', ['type' => 'pix'])
            ->assertUnprocessable();
    });

    it('returns 422 when type is invalid', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/payment-methods', ['type' => 'bitcoin'])
            ->assertUnprocessable();
    });

    it('requires authentication', function () {
        $this->postJson('/api/v1/payment-methods', [])->assertUnauthorized();
    });
});

describe('GET /api/v1/payment-methods/{id}', function () {
    it('returns the payment method detail', function () {
        $user = User::factory()->create();
        $method = PaymentMethod::factory()->creditCard()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson("/api/v1/payment-methods/{$method->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.id', $method->id)
            ->assertJsonPath('data.type', 'credit_card');
    });

    it('returns 403 when method belongs to another user', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $method = PaymentMethod::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->getJson("/api/v1/payment-methods/{$method->id}")
            ->assertForbidden();
    });

    it('returns 404 for a non-existent method', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/payment-methods/99999')
            ->assertNotFound();
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/payment-methods/1')->assertUnauthorized();
    });
});

describe('DELETE /api/v1/payment-methods/{id}', function () {
    it('soft-deletes the method and returns 204', function () {
        $user = User::factory()->create();
        $method = PaymentMethod::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/payment-methods/{$method->id}")
            ->assertNoContent();

        expect(PaymentMethod::withTrashed()->find($method->id)->deleted_at)->not->toBeNull();
    });

    it('returns 403 when method belongs to another user', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $method = PaymentMethod::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/payment-methods/{$method->id}")
            ->assertForbidden();
    });

    it('returns 404 for a non-existent method', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson('/api/v1/payment-methods/99999')
            ->assertNotFound();
    });

    it('returns 422 when method has pending payments', function () {
        $user = User::factory()->create();
        $method = PaymentMethod::factory()->creditCard()->create(['user_id' => $user->id]);
        $invoice = \App\Models\Invoice::factory()->open()->create(['user_id' => $user->id]);
        Payment::factory()->create([
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
            'payment_method_id' => $method->id,
            'status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/payment-methods/{$method->id}")
            ->assertUnprocessable()
            ->assertJsonPath('errors.payment_method.0', 'Payment method has pending payments.');
    });

    it('requires authentication', function () {
        $this->deleteJson('/api/v1/payment-methods/1')->assertUnauthorized();
    });
});

describe('PATCH /api/v1/payment-methods/{id}/default', function () {
    it('sets the method as default and unsets the previous one', function () {
        $user = User::factory()->create();
        $first = PaymentMethod::factory()->creditCard()->asDefault()->create(['user_id' => $user->id]);
        $second = PaymentMethod::factory()->pix()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patchJson("/api/v1/payment-methods/{$second->id}/default")
            ->assertSuccessful()
            ->assertJsonPath('data.id', $second->id)
            ->assertJsonPath('data.is_default', true);

        expect($first->fresh()->is_default)->toBeFalse();
        expect($second->fresh()->is_default)->toBeTrue();
    });

    it('returns 403 when method belongs to another user', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $method = PaymentMethod::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->patchJson("/api/v1/payment-methods/{$method->id}/default")
            ->assertForbidden();
    });

    it('returns 404 for a non-existent method', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/v1/payment-methods/99999/default')
            ->assertNotFound();
    });

    it('requires authentication', function () {
        $this->patchJson('/api/v1/payment-methods/1/default')->assertUnauthorized();
    });
});
