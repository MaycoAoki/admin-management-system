<?php

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;

describe('GET /api/v1/invoices', function () {
    it('returns paginated invoices for the authenticated user', function () {
        $user = User::factory()->create();
        Invoice::factory()->count(3)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/invoices')
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'invoice_number', 'status', 'amount_in_cents', 'amount_formatted', 'due_date', 'is_overdue']],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('does not return invoices from other users', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Invoice::factory()->count(5)->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/invoices')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('filters by status', function () {
        $user = User::factory()->create();
        Invoice::factory()->open()->count(2)->create(['user_id' => $user->id]);
        Invoice::factory()->paid()->count(3)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/invoices?status=open')
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    });

    it('rejects an invalid status filter', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/invoices?status=invalid')
            ->assertUnprocessable();
    });

    it('silently caps per_page at 50', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/invoices?per_page=100')
            ->assertSuccessful()
            ->assertJsonPath('meta.per_page', 50);
    });

    it('returns empty data when user has no invoices', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/invoices')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/invoices')->assertUnauthorized();
    });
});

describe('GET /api/v1/invoices/{id}', function () {
    it('returns invoice detail with payments and subscription', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);
        Payment::factory()->failed()->create(['user_id' => $user->id, 'invoice_id' => $invoice->id]);

        $this->actingAs($user)
            ->getJson("/api/v1/invoices/{$invoice->id}")
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id', 'invoice_number', 'status', 'amount_in_cents', 'amount_due_in_cents',
                    'is_overdue', 'pdf_url', 'payments', 'subscription',
                ],
            ])
            ->assertJsonPath('data.id', $invoice->id)
            ->assertJsonCount(1, 'data.payments');
    });

    it('marks overdue invoice correctly', function () {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->overdue()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson("/api/v1/invoices/{$invoice->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.is_overdue', true);
    });

    it('returns 403 when accessing another user invoice', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->getJson("/api/v1/invoices/{$invoice->id}")
            ->assertForbidden();
    });

    it('returns 404 for a non-existent invoice', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/invoices/99999')
            ->assertNotFound();
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/invoices/1')->assertUnauthorized();
    });
});
