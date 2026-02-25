<?php

namespace App\UseCases;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\InitiatePaymentData;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class InitiatePayment
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
        private readonly PaymentGatewayInterface $gateway,
    ) {}

    /**
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(int $invoiceId, int $userId, InitiatePaymentData $data): Payment
    {
        $invoice = $this->invoiceRepository->findById($invoiceId);

        if (! $invoice) {
            throw new ModelNotFoundException;
        }

        if ($invoice->user_id !== $userId) {
            throw new AuthorizationException;
        }

        if ($invoice->status !== InvoiceStatus::Open) {
            throw ValidationException::withMessages([
                'invoice' => ['Invoice is not payable.'],
            ]);
        }

        $amountInCents = $data->amountInCents ?? $invoice->amount_due_in_cents;

        if ($amountInCents > $invoice->amount_due_in_cents) {
            throw ValidationException::withMessages([
                'amount_in_cents' => ['Amount exceeds the invoice outstanding balance.'],
            ]);
        }

        $paymentMethod = null;
        if ($data->paymentMethodId) {
            $paymentMethod = $this->paymentMethodRepository->findById($data->paymentMethodId);
            if (! $paymentMethod || $paymentMethod->user_id !== $userId) {
                throw ValidationException::withMessages([
                    'payment_method_id' => ['The selected payment method is invalid.'],
                ]);
            }
        }

        return DB::transaction(function () use ($invoice, $userId, $data, $paymentMethod, $amountInCents) {
            $payment = $this->paymentRepository->create([
                'user_id' => $userId,
                'invoice_id' => $invoice->id,
                'payment_method_id' => $paymentMethod?->id,
                'amount_in_cents' => $amountInCents,
                'currency' => $invoice->currency,
                'status' => PaymentStatus::Pending,
                'payment_method_type' => $data->methodType,
                'gateway' => 'stub',
            ]);

            $response = $this->gateway->charge($payment, $paymentMethod);

            $payment = $this->paymentRepository->update($payment, [
                'status' => $response->status,
                'gateway_payment_id' => $response->gatewayPaymentId,
                'pix_qr_code' => $response->pixQrCode,
                'pix_expires_at' => $response->pixExpiresAt,
                'boleto_url' => $response->boletoUrl,
                'boleto_barcode' => $response->boletoBarcode,
                'boleto_expires_at' => $response->boletoExpiresAt,
                'failure_reason' => $response->failureReason,
                'paid_at' => $response->paidAt,
                'failed_at' => $response->failedAt,
            ]);

            if ($response->status === PaymentStatus::Succeeded) {
                $newAmountPaid = $invoice->amount_paid_in_cents + $amountInCents;
                $isFullyPaid = $newAmountPaid >= $invoice->amount_in_cents;

                $this->invoiceRepository->update($invoice, [
                    'amount_paid_in_cents' => $newAmountPaid,
                    'status' => $isFullyPaid ? InvoiceStatus::Paid : InvoiceStatus::Open,
                    'paid_at' => $isFullyPaid ? now() : null,
                ]);
            }

            return $payment;
        });
    }
}
