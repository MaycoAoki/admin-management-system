<?php

namespace App\Models;

use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'invoice_id',
        'payment_method_id',
        'amount_in_cents',
        'currency',
        'status',
        'payment_method_type',
        'gateway',
        'gateway_payment_id',
        'gateway_response',
        'paid_at',
        'failed_at',
        'failure_reason',
        'pix_qr_code',
        'pix_expires_at',
        'boleto_url',
        'boleto_barcode',
        'boleto_expires_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'payment_method_type' => PaymentMethodType::class,
            'amount_in_cents' => 'integer',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'pix_expires_at' => 'datetime',
            'boleto_expires_at' => 'datetime',
            'gateway_response' => 'array',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
