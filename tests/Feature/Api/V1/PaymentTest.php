<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\User;

describe('POST /api/v1/invoices/{id}/payments', function () {
    it('pays an invoice via PIX and returns pending with QR code', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id, 'amount_in_cents' => 9990]);

        $this->actingAs($user)
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", ['method' => 'pix'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_method_type', 'pix')
            ->assertJsonStructure(['data' => ['pix_qr_code', 'pix_expires_at']]);

        expect($invoice->fresh()->status)->toBe(InvoiceStatus::Open);
    });

    it('pays an invoice via boleto and returns pending with barcode', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", ['method' => 'boleto'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_method_type', 'boleto')
            ->assertJsonStructure(['data' => ['boleto_url', 'boleto_barcode', 'boleto_expires_at']]);
    });

    it('pays an invoice via credit card and marks it as paid', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id, 'amount_in_cents' => 9990]);
        $method = PaymentMethod::factory()->creditCard()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", [
                'method' => 'credit_card',
                'payment_method_id' => $method->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'succeeded');

        $fresh = $invoice->fresh();
        expect($fresh->status)->toBe(InvoiceStatus::Paid)
            ->and($fresh->paid_at)->not->toBeNull();
    });

    it('handles a partial payment keeping invoice open', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id, 'amount_in_cents' => 9990]);
        $method = PaymentMethod::factory()->creditCard()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", [
                'method' => 'credit_card',
                'payment_method_id' => $method->id,
                'amount_in_cents' => 4990,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'succeeded');

        $fresh = $invoice->fresh();
        expect($fresh->status)->toBe(InvoiceStatus::Open)
            ->and($fresh->amount_paid_in_cents)->toBe(4990);
    });

    it('uses the full outstanding amount when amount_in_cents is omitted', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create([
            'user_id' => $user->id,
            'amount_in_cents' => 9990,
            'amount_paid_in_cents' => 4000,
        ]);
        $method = PaymentMethod::factory()->creditCard()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", [
                'method' => 'credit_card',
                'payment_method_id' => $method->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.amount_in_cents', 5990);
    });

    it('returns 403 when the invoice belongs to another user', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", ['method' => 'pix'])
            ->assertForbidden();
    });

    it('returns 404 for a non-existent invoice', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/invoices/99999/payments', ['method' => 'pix'])
            ->assertNotFound();
    });

    it('returns 422 when trying to pay a paid invoice', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->paid()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", ['method' => 'pix'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.invoice.0', 'Invoice is not payable.');
    });

    it('returns 422 when amount_in_cents exceeds the outstanding balance', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id, 'amount_in_cents' => 9990]);

        $this->actingAs($user)
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", [
                'method' => 'pix',
                'amount_in_cents' => 99999,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.amount_in_cents.0', 'Amount exceeds the invoice outstanding balance.');
    });

    it('returns 422 when credit_card is used without payment_method_id', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", ['method' => 'credit_card'])
            ->assertUnprocessable();
    });

    it('returns 422 when payment_method_id belongs to another user', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);
        $method = PaymentMethod::factory()->creditCard()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", [
                'method' => 'credit_card',
                'payment_method_id' => $method->id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.payment_method_id.0', 'The selected payment method is invalid.');
    });

    it('requires authentication', function () {
        $this->postJson('/api/v1/invoices/1/payments', ['method' => 'pix'])
            ->assertUnauthorized();
    });
});

describe('GET /api/v1/payments', function () {
    it('returns paginated payment history for the authenticated user', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);
        \App\Models\Payment::factory()->succeeded()->count(3)->create([
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/payments')
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'status', 'amount_in_cents', 'amount_formatted']], 'meta']);
    });

    it('does not return payments from other users', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $other->id]);
        \App\Models\Payment::factory()->count(3)->create(['user_id' => $other->id, 'invoice_id' => $invoice->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/payments')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/payments')->assertUnauthorized();
    });
});

describe('GET /api/v1/payments/{id}', function () {
    it('returns payment detail with embedded invoice', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);
        $payment = \App\Models\Payment::factory()->succeeded()->create([
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);

        $this->actingAs($user)
            ->getJson("/api/v1/payments/{$payment->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.id', $payment->id)
            ->assertJsonStructure(['data' => ['invoice']]);
    });

    it('returns 403 when accessing another user payment', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $other->id]);
        $payment = \App\Models\Payment::factory()->create(['user_id' => $other->id, 'invoice_id' => $invoice->id]);

        $this->actingAs($user)
            ->getJson("/api/v1/payments/{$payment->id}")
            ->assertForbidden();
    });

    it('returns 404 for a non-existent payment', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/payments/99999')
            ->assertNotFound();
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/payments/1')->assertUnauthorized();
    });
});
