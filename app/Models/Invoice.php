<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'invoice_number',
        'status',
        'amount_in_cents',
        'amount_paid_in_cents',
        'currency',
        'description',
        'due_date',
        'paid_at',
        'period_start',
        'period_end',
        'pdf_path',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'amount_in_cents' => 'integer',
            'amount_paid_in_cents' => 'integer',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'period_start' => 'date',
            'period_end' => 'date',
            'metadata' => 'array',
        ];
    }

    public function getAmountDueInCentsAttribute(): int
    {
        return $this->amount_in_cents - $this->amount_paid_in_cents;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeOpen(Builder $query): void
    {
        $query->where('status', InvoiceStatus::Open);
    }

    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', InvoiceStatus::Open)
            ->where('due_date', '<', now()->toDateString());
    }

    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }
}
