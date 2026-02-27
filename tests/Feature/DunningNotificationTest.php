<?php

use App\Console\Commands\ProcessDunning;
use App\Console\Commands\SendDueSoonReminders;
use App\Contracts\PaymentGatewayInterface;
use App\DTOs\GatewayResponse;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\InvoiceDueSoonNotification;
use App\Notifications\InvoiceOverdueNotification;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\PaymentSucceededNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

use function Pest\Laravel\mock;

describe('PaymentSucceededNotification', function () {
    it('is sent when a bank_debit payment succeeds', function () {
        Notification::fake();

        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", ['method' => 'bank_debit'])
            ->assertCreated();

        Notification::assertSentTo($user, PaymentSucceededNotification::class);
    });

    it('contains the correct payment reference', function () {
        Notification::fake();

        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", ['method' => 'bank_debit'])
            ->assertCreated();

        Notification::assertSentTo(
            $user,
            PaymentSucceededNotification::class,
            fn (PaymentSucceededNotification $n) => $n->payment->invoice_id === $invoice->id
        );
    });

    it('is not sent when payment remains pending (pix)', function () {
        Notification::fake();

        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", ['method' => 'pix'])
            ->assertCreated();

        Notification::assertNotSentTo($user, PaymentSucceededNotification::class);
    });
});

describe('PaymentFailedNotification', function () {
    it('is sent when the gateway returns a failed status', function () {
        Notification::fake();

        mock(PaymentGatewayInterface::class)
            ->shouldReceive('charge')
            ->andReturn(new GatewayResponse(
                status: PaymentStatus::Failed,
                gatewayPaymentId: 'stub_'.Str::uuid(),
                failureReason: 'Card declined',
                failedAt: now(),
            ));

        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", ['method' => 'bank_debit'])
            ->assertCreated();

        Notification::assertSentTo($user, PaymentFailedNotification::class);
        Notification::assertNotSentTo($user, PaymentSucceededNotification::class);
    });

    it('contains the failure reason', function () {
        Notification::fake();

        mock(PaymentGatewayInterface::class)
            ->shouldReceive('charge')
            ->andReturn(new GatewayResponse(
                status: PaymentStatus::Failed,
                gatewayPaymentId: 'stub_'.Str::uuid(),
                failureReason: 'Insufficient funds',
                failedAt: now(),
            ));

        $user = User::factory()->create();
        $invoice = Invoice::factory()->open()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", ['method' => 'bank_debit'])
            ->assertCreated();

        Notification::assertSentTo(
            $user,
            PaymentFailedNotification::class,
            fn (PaymentFailedNotification $n) => $n->payment->failure_reason === 'Insufficient funds'
        );
    });
});

describe('billing:send-due-soon-reminders', function () {
    it('sends InvoiceDueSoonNotification to users with invoices due in 3 days', function () {
        Notification::fake();

        $user = User::factory()->create();
        Invoice::factory()->open()->create([
            'user_id' => $user->id,
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->artisan(SendDueSoonReminders::class)->assertSuccessful();

        Notification::assertSentTo($user, InvoiceDueSoonNotification::class);
    });

    it('does not notify users whose invoices are not due in 3 days', function () {
        Notification::fake();

        $user = User::factory()->create();
        Invoice::factory()->open()->create([
            'user_id' => $user->id,
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        $this->artisan(SendDueSoonReminders::class)->assertSuccessful();

        Notification::assertNotSentTo($user, InvoiceDueSoonNotification::class);
    });

    it('respects the --days option', function () {
        Notification::fake();

        $user = User::factory()->create();
        Invoice::factory()->open()->create([
            'user_id' => $user->id,
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $this->artisan(SendDueSoonReminders::class, ['--days' => 7])->assertSuccessful();

        Notification::assertSentTo($user, InvoiceDueSoonNotification::class);
    });

    it('does not notify for paid invoices', function () {
        Notification::fake();

        $user = User::factory()->create();
        Invoice::factory()->paid()->create([
            'user_id' => $user->id,
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->artisan(SendDueSoonReminders::class)->assertSuccessful();

        Notification::assertNotSentTo($user, InvoiceDueSoonNotification::class);
    });
});

describe('billing:process-dunning', function () {
    it('sends InvoiceOverdueNotification to users with overdue invoices', function () {
        Notification::fake();

        $user = User::factory()->create();
        Invoice::factory()->overdue()->create(['user_id' => $user->id]);

        $this->artisan(ProcessDunning::class)->assertSuccessful();

        Notification::assertSentTo($user, InvoiceOverdueNotification::class);
    });

    it('does not notify users with no overdue invoices', function () {
        Notification::fake();

        $user = User::factory()->create();
        Invoice::factory()->open()->create([
            'user_id' => $user->id,
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->artisan(ProcessDunning::class)->assertSuccessful();

        Notification::assertNotSentTo($user, InvoiceOverdueNotification::class);
    });

    it('sends one notification per overdue invoice', function () {
        Notification::fake();

        $user = User::factory()->create();
        Invoice::factory()->overdue()->count(3)->create(['user_id' => $user->id]);

        $this->artisan(ProcessDunning::class)->assertSuccessful();

        Notification::assertSentToTimes($user, InvoiceOverdueNotification::class, 3);
    });

    it('does not notify for paid invoices', function () {
        Notification::fake();

        $user = User::factory()->create();
        Invoice::factory()->paid()->create([
            'user_id' => $user->id,
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        $this->artisan(ProcessDunning::class)->assertSuccessful();

        Notification::assertNotSentTo($user, InvoiceOverdueNotification::class);
    });
});
