<?php

use App\Models\Dispute;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;

describe('POST /api/v1/payments/{id}/disputes', function () {
    it('opens a dispute and returns 201 with gateway_dispute_id', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->succeeded()->create([
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/payments/{$payment->id}/disputes", [
                'reason' => 'fraudulent',
                'description' => 'Não reconheço esta cobrança.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.reason', 'fraudulent')
            ->assertJsonStructure(['data' => ['gateway_dispute_id', 'payment']]);
    });

    it('opens a dispute without description', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->succeeded()->create([
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/payments/{$payment->id}/disputes", ['reason' => 'duplicate'])
            ->assertCreated()
            ->assertJsonPath('data.description', null);
    });

    it('returns 403 when payment belongs to another user', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $other->id]);
        $payment = Payment::factory()->succeeded()->create([
            'user_id' => $other->id,
            'invoice_id' => $invoice->id,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/payments/{$payment->id}/disputes", ['reason' => 'fraudulent'])
            ->assertForbidden();
    });

    it('returns 404 for a non-existent payment', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/payments/99999/disputes', ['reason' => 'fraudulent'])
            ->assertNotFound();
    });

    it('returns 422 when payment is not succeeded', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->pending()->create([
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/payments/{$payment->id}/disputes", ['reason' => 'fraudulent'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.payment.0', 'Only succeeded payments can be disputed.');
    });

    it('returns 422 when payment already has an active dispute', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->succeeded()->create([
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);
        Dispute::factory()->open()->create([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/payments/{$payment->id}/disputes", ['reason' => 'duplicate'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.payment.0', 'Payment already has an active dispute.');
    });

    it('returns 422 for an invalid reason', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->succeeded()->create([
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/payments/{$payment->id}/disputes", ['reason' => 'invalid_reason'])
            ->assertUnprocessable();
    });

    it('requires authentication', function () {
        $this->postJson('/api/v1/payments/1/disputes', [])->assertUnauthorized();
    });
});

describe('GET /api/v1/disputes', function () {
    it('returns paginated disputes for the authenticated user', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->succeeded()->create([
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);
        Dispute::factory()->count(3)->create([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/disputes')
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'status', 'reason', 'created_at']], 'meta']);
    });

    it('does not return disputes from other users', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $other->id]);
        $payment = Payment::factory()->succeeded()->create([
            'user_id' => $other->id,
            'invoice_id' => $invoice->id,
        ]);
        Dispute::factory()->count(2)->create([
            'user_id' => $other->id,
            'payment_id' => $payment->id,
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/disputes')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('filters by status correctly', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->succeeded()->create([
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);
        Dispute::factory()->open()->create(['user_id' => $user->id, 'payment_id' => $payment->id]);
        Dispute::factory()->won()->create(['user_id' => $user->id, 'payment_id' => $payment->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/disputes?status=open')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'open');
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/disputes')->assertUnauthorized();
    });
});

describe('GET /api/v1/disputes/{id}', function () {
    it('returns dispute detail with embedded payment', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->succeeded()->create([
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);
        $dispute = Dispute::factory()->open()->create([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
        ]);

        $this->actingAs($user)
            ->getJson("/api/v1/disputes/{$dispute->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.id', $dispute->id)
            ->assertJsonStructure(['data' => ['payment']]);
    });

    it('returns 403 when dispute belongs to another user', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $other->id]);
        $payment = Payment::factory()->succeeded()->create([
            'user_id' => $other->id,
            'invoice_id' => $invoice->id,
        ]);
        $dispute = Dispute::factory()->create([
            'user_id' => $other->id,
            'payment_id' => $payment->id,
        ]);

        $this->actingAs($user)
            ->getJson("/api/v1/disputes/{$dispute->id}")
            ->assertForbidden();
    });

    it('returns 404 for a non-existent dispute', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/disputes/99999')
            ->assertNotFound();
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/disputes/1')->assertUnauthorized();
    });
});

describe('DELETE /api/v1/disputes/{id}', function () {
    it('withdraws an open dispute', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->succeeded()->create([
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);
        $dispute = Dispute::factory()->open()->create([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/disputes/{$dispute->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'withdrawn');

        expect($dispute->fresh()->withdrawn_at)->not->toBeNull();
    });

    it('returns 422 when trying to withdraw an under_review dispute', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->succeeded()->create([
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);
        $dispute = Dispute::factory()->underReview()->create([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/disputes/{$dispute->id}")
            ->assertUnprocessable()
            ->assertJsonPath('errors.dispute.0', 'Dispute cannot be withdrawn in its current status.');
    });

    it('returns 403 when dispute belongs to another user', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $other->id]);
        $payment = Payment::factory()->succeeded()->create([
            'user_id' => $other->id,
            'invoice_id' => $invoice->id,
        ]);
        $dispute = Dispute::factory()->open()->create([
            'user_id' => $other->id,
            'payment_id' => $payment->id,
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/disputes/{$dispute->id}")
            ->assertForbidden();
    });

    it('returns 404 for a non-existent dispute', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson('/api/v1/disputes/99999')
            ->assertNotFound();
    });

    it('requires authentication', function () {
        $this->deleteJson('/api/v1/disputes/1')->assertUnauthorized();
    });
});
