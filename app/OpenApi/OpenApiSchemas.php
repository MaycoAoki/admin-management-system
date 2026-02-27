<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

final class OpenApiSchemas
{
    #[OA\Schema(
        schema: 'User',
        required: ['id', 'name', 'email', 'created_at'],
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
            new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        ],
        type: 'object'
    )]
    public function user(): void {}

    #[OA\Schema(
        schema: 'AuthTokenResponse',
        required: ['user', 'token'],
        example: [
            'user' => [
                'id' => 1,
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'email_verified_at' => null,
                'created_at' => '2026-02-27T12:00:00Z',
            ],
            'token' => '1|abc123',
        ],
        properties: [
            new OA\Property(property: 'user', ref: '#/components/schemas/User'),
            new OA\Property(property: 'token', type: 'string', example: '1|abc123'),
        ],
        type: 'object'
    )]
    public function authTokenResponse(): void {}

    #[OA\Schema(
        schema: 'MessageResponse',
        required: ['message'],
        properties: [
            new OA\Property(property: 'message', type: 'string', example: 'Operation completed successfully.'),
        ],
        type: 'object'
    )]
    public function messageResponse(): void {}

    #[OA\Schema(
        schema: 'UnauthorizedError',
        required: ['message'],
        properties: [
            new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
        ],
        type: 'object'
    )]
    public function unauthorizedError(): void {}

    #[OA\Schema(
        schema: 'ForbiddenError',
        required: ['message'],
        properties: [
            new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
        ],
        type: 'object'
    )]
    public function forbiddenError(): void {}

    #[OA\Schema(
        schema: 'NotFoundError',
        required: ['message'],
        properties: [
            new OA\Property(property: 'message', type: 'string', example: 'Resource not found.'),
        ],
        type: 'object'
    )]
    public function notFoundError(): void {}

    #[OA\Schema(
        schema: 'ValidationError',
        required: ['message', 'errors'],
        example: [
            'message' => 'The given data was invalid.',
            'errors' => [
                'email' => ['The email field is required.'],
            ],
        ],
        properties: [
            new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
            new OA\Property(
                property: 'errors',
                type: 'object',
                additionalProperties: new OA\AdditionalProperties(
                    type: 'array',
                    items: new OA\Items(type: 'string')
                )
            ),
        ],
        type: 'object'
    )]
    public function validationError(): void {}

    #[OA\Schema(
        schema: 'BusinessRuleError',
        required: ['message', 'errors'],
        example: [
            'message' => 'The given data was invalid.',
            'errors' => [
                'subscription' => ['No active subscription found.'],
            ],
        ],
        properties: [
            new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
            new OA\Property(
                property: 'errors',
                type: 'object',
                additionalProperties: new OA\AdditionalProperties(
                    type: 'array',
                    items: new OA\Items(type: 'string')
                )
            ),
        ],
        type: 'object'
    )]
    public function businessRuleError(): void {}

    #[OA\Schema(
        schema: 'PaginationLinks',
        properties: [
            new OA\Property(property: 'first', type: 'string', nullable: true, example: 'http://localhost/api/v1/invoices?page=1'),
            new OA\Property(property: 'last', type: 'string', nullable: true, example: 'http://localhost/api/v1/invoices?page=3'),
            new OA\Property(property: 'prev', type: 'string', nullable: true),
            new OA\Property(property: 'next', type: 'string', nullable: true),
        ],
        type: 'object'
    )]
    public function paginationLinks(): void {}

    #[OA\Schema(
        schema: 'PaginationMeta',
        properties: [
            new OA\Property(property: 'current_page', type: 'integer', example: 1),
            new OA\Property(property: 'from', type: 'integer', nullable: true, example: 1),
            new OA\Property(property: 'last_page', type: 'integer', example: 3),
            new OA\Property(property: 'path', type: 'string', example: 'http://localhost/api/v1/invoices'),
            new OA\Property(property: 'per_page', type: 'integer', example: 15),
            new OA\Property(property: 'to', type: 'integer', nullable: true, example: 15),
            new OA\Property(property: 'total', type: 'integer', example: 45),
        ],
        type: 'object'
    )]
    public function paginationMeta(): void {}

    #[OA\Schema(
        schema: 'Plan',
        required: ['id', 'name', 'slug', 'billing_cycle', 'price_in_cents', 'price_formatted', 'currency'],
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 2),
            new OA\Property(property: 'name', type: 'string', example: 'Pro'),
            new OA\Property(property: 'slug', type: 'string', example: 'pro'),
            new OA\Property(property: 'billing_cycle', type: 'string', enum: ['monthly', 'quarterly', 'semiannual', 'annual']),
            new OA\Property(property: 'price_in_cents', type: 'integer', example: 9900),
            new OA\Property(property: 'price_formatted', type: 'string', example: '$99.00'),
            new OA\Property(property: 'currency', type: 'string', example: 'USD'),
            new OA\Property(property: 'trial_days', type: 'integer', nullable: true, example: 14),
        ],
        type: 'object'
    )]
    public function plan(): void {}

    #[OA\Schema(
        schema: 'SubscriptionSummary',
        required: ['id', 'status', 'plan_name', 'plan_slug', 'billing_cycle', 'price_in_cents', 'price_formatted', 'auto_renew', 'auto_pay', 'is_trial'],
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 10),
            new OA\Property(property: 'status', type: 'string', enum: ['active', 'trialing', 'past_due', 'canceled', 'expired', 'paused']),
            new OA\Property(property: 'plan_name', type: 'string', example: 'Pro'),
            new OA\Property(property: 'plan_slug', type: 'string', example: 'pro'),
            new OA\Property(property: 'billing_cycle', type: 'string', enum: ['monthly', 'quarterly', 'semiannual', 'annual']),
            new OA\Property(property: 'price_in_cents', type: 'integer', example: 9900),
            new OA\Property(property: 'price_formatted', type: 'string', example: '$99.00'),
            new OA\Property(property: 'current_period_end', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'auto_renew', type: 'boolean', example: true),
            new OA\Property(property: 'auto_pay', type: 'boolean', example: false),
            new OA\Property(property: 'is_trial', type: 'boolean', example: false),
            new OA\Property(property: 'trial_ends_at', type: 'string', format: 'date-time', nullable: true),
        ],
        type: 'object'
    )]
    public function subscriptionSummary(): void {}

    #[OA\Schema(
        schema: 'Subscription',
        required: ['id', 'status', 'auto_renew', 'auto_pay'],
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 10),
            new OA\Property(property: 'status', type: 'string', enum: ['active', 'trialing', 'past_due', 'canceled', 'expired', 'paused']),
            new OA\Property(property: 'auto_renew', type: 'boolean', example: true),
            new OA\Property(property: 'auto_pay', type: 'boolean', example: false),
            new OA\Property(property: 'current_period_start', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'current_period_end', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'trial_ends_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'canceled_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'cancel_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'plan', ref: '#/components/schemas/Plan', nullable: true),
        ],
        type: 'object'
    )]
    public function subscription(): void {}

    #[OA\Schema(
        schema: 'DashboardBalance',
        required: ['outstanding_in_cents', 'outstanding_formatted', 'open_invoices_count', 'overdue_invoices_count'],
        properties: [
            new OA\Property(property: 'outstanding_in_cents', type: 'integer', example: 14900),
            new OA\Property(property: 'outstanding_formatted', type: 'string', example: '$149.00'),
            new OA\Property(property: 'open_invoices_count', type: 'integer', example: 2),
            new OA\Property(property: 'overdue_invoices_count', type: 'integer', example: 1),
        ],
        type: 'object'
    )]
    public function dashboardBalance(): void {}

    #[OA\Schema(
        schema: 'NextDue',
        required: ['invoice_number', 'amount_due_in_cents', 'amount_due_formatted', 'due_date', 'is_overdue', 'days_until_due'],
        properties: [
            new OA\Property(property: 'invoice_number', type: 'string', example: 'INV-2026-0001'),
            new OA\Property(property: 'amount_due_in_cents', type: 'integer', example: 14900),
            new OA\Property(property: 'amount_due_formatted', type: 'string', example: '$149.00'),
            new OA\Property(property: 'due_date', type: 'string', format: 'date', example: '2026-03-05'),
            new OA\Property(property: 'is_overdue', type: 'boolean', example: false),
            new OA\Property(property: 'days_until_due', type: 'integer', example: 6),
        ],
        type: 'object'
    )]
    public function nextDue(): void {}

    #[OA\Schema(
        schema: 'Dashboard',
        required: ['balance'],
        properties: [
            new OA\Property(property: 'balance', ref: '#/components/schemas/DashboardBalance'),
            new OA\Property(property: 'next_due', ref: '#/components/schemas/NextDue', nullable: true),
            new OA\Property(property: 'subscription', ref: '#/components/schemas/SubscriptionSummary', nullable: true),
        ],
        type: 'object'
    )]
    public function dashboard(): void {}

    #[OA\Schema(
        schema: 'PaymentNested',
        required: ['id', 'status', 'amount_in_cents', 'amount_formatted', 'payment_method_type'],
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 88),
            new OA\Property(property: 'status', type: 'string', enum: ['pending', 'processing', 'succeeded', 'failed', 'refunded', 'canceled']),
            new OA\Property(property: 'amount_in_cents', type: 'integer', example: 9900),
            new OA\Property(property: 'amount_formatted', type: 'string', example: '$99.00'),
            new OA\Property(property: 'payment_method_type', type: 'string', enum: ['credit_card', 'debit_card', 'pix', 'boleto', 'bank_debit']),
        ],
        type: 'object'
    )]
    public function paymentNested(): void {}

    #[OA\Schema(
        schema: 'InvoiceSummary',
        required: ['id', 'invoice_number', 'status', 'amount_in_cents', 'amount_paid_in_cents', 'amount_due_in_cents', 'amount_formatted', 'currency', 'is_overdue', 'created_at'],
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 45),
            new OA\Property(property: 'invoice_number', type: 'string', example: 'INV-2026-0001'),
            new OA\Property(property: 'status', type: 'string', enum: ['draft', 'open', 'paid', 'void', 'uncollectible']),
            new OA\Property(property: 'amount_in_cents', type: 'integer', example: 14900),
            new OA\Property(property: 'amount_paid_in_cents', type: 'integer', example: 0),
            new OA\Property(property: 'amount_due_in_cents', type: 'integer', example: 14900),
            new OA\Property(property: 'amount_formatted', type: 'string', example: '$149.00'),
            new OA\Property(property: 'currency', type: 'string', example: 'USD'),
            new OA\Property(property: 'description', type: 'string', nullable: true, example: 'March subscription'),
            new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true),
            new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'period_start', type: 'string', format: 'date', nullable: true),
            new OA\Property(property: 'period_end', type: 'string', format: 'date', nullable: true),
            new OA\Property(property: 'is_overdue', type: 'boolean', example: false),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        ],
        type: 'object'
    )]
    public function invoiceSummary(): void {}

    #[OA\Schema(
        schema: 'InvoiceDetail',
        required: ['id', 'invoice_number', 'status', 'amount_in_cents', 'amount_paid_in_cents', 'amount_due_in_cents', 'amount_formatted', 'currency', 'is_overdue', 'created_at'],
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 45),
            new OA\Property(property: 'invoice_number', type: 'string', example: 'INV-2026-0001'),
            new OA\Property(property: 'status', type: 'string', enum: ['draft', 'open', 'paid', 'void', 'uncollectible']),
            new OA\Property(property: 'amount_in_cents', type: 'integer', example: 14900),
            new OA\Property(property: 'amount_paid_in_cents', type: 'integer', example: 0),
            new OA\Property(property: 'amount_due_in_cents', type: 'integer', example: 14900),
            new OA\Property(property: 'amount_formatted', type: 'string', example: '$149.00'),
            new OA\Property(property: 'currency', type: 'string', example: 'USD'),
            new OA\Property(property: 'description', type: 'string', nullable: true, example: 'March subscription'),
            new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true),
            new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'period_start', type: 'string', format: 'date', nullable: true),
            new OA\Property(property: 'period_end', type: 'string', format: 'date', nullable: true),
            new OA\Property(property: 'is_overdue', type: 'boolean', example: false),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
            new OA\Property(property: 'pdf_url', type: 'string', nullable: true, example: null),
            new OA\Property(property: 'subscription', ref: '#/components/schemas/SubscriptionSummary', nullable: true),
            new OA\Property(property: 'payments', type: 'array', items: new OA\Items(ref: '#/components/schemas/PaymentNested')),
        ],
        type: 'object'
    )]
    public function invoiceDetail(): void {}

    #[OA\Schema(
        schema: 'PaymentSummary',
        required: ['id', 'amount_in_cents', 'amount_formatted', 'status', 'payment_method_type', 'created_at'],
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 88),
            new OA\Property(property: 'amount_in_cents', type: 'integer', example: 9900),
            new OA\Property(property: 'amount_formatted', type: 'string', example: '$99.00'),
            new OA\Property(property: 'status', type: 'string', enum: ['pending', 'processing', 'succeeded', 'failed', 'refunded', 'canceled']),
            new OA\Property(property: 'payment_method_type', type: 'string', enum: ['credit_card', 'debit_card', 'pix', 'boleto', 'bank_debit']),
            new OA\Property(property: 'gateway', type: 'string', nullable: true, example: 'stripe'),
            new OA\Property(property: 'pix_qr_code', type: 'string', nullable: true),
            new OA\Property(property: 'pix_expires_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'boleto_url', type: 'string', nullable: true),
            new OA\Property(property: 'boleto_barcode', type: 'string', nullable: true),
            new OA\Property(property: 'boleto_expires_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'failure_reason', type: 'string', nullable: true),
            new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'failed_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        ],
        type: 'object'
    )]
    public function paymentSummary(): void {}

    #[OA\Schema(
        schema: 'PaymentDetail',
        required: ['id', 'amount_in_cents', 'amount_formatted', 'status', 'payment_method_type', 'created_at'],
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 88),
            new OA\Property(property: 'amount_in_cents', type: 'integer', example: 9900),
            new OA\Property(property: 'amount_formatted', type: 'string', example: '$99.00'),
            new OA\Property(property: 'status', type: 'string', enum: ['pending', 'processing', 'succeeded', 'failed', 'refunded', 'canceled']),
            new OA\Property(property: 'payment_method_type', type: 'string', enum: ['credit_card', 'debit_card', 'pix', 'boleto', 'bank_debit']),
            new OA\Property(property: 'gateway', type: 'string', nullable: true, example: 'stripe'),
            new OA\Property(property: 'pix_qr_code', type: 'string', nullable: true),
            new OA\Property(property: 'pix_expires_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'boleto_url', type: 'string', nullable: true),
            new OA\Property(property: 'boleto_barcode', type: 'string', nullable: true),
            new OA\Property(property: 'boleto_expires_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'failure_reason', type: 'string', nullable: true),
            new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'failed_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'invoice', ref: '#/components/schemas/InvoiceSummary', nullable: true),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        ],
        type: 'object'
    )]
    public function paymentDetail(): void {}

    #[OA\Schema(
        schema: 'Dispute',
        required: ['id', 'status', 'reason', 'created_at'],
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 15),
            new OA\Property(property: 'status', type: 'string', enum: ['open', 'under_review', 'won', 'lost', 'withdrawn']),
            new OA\Property(property: 'reason', type: 'string', enum: ['fraudulent', 'duplicate', 'product_not_received', 'product_not_as_described', 'unrecognized', 'other']),
            new OA\Property(property: 'description', type: 'string', nullable: true),
            new OA\Property(property: 'gateway_dispute_id', type: 'string', nullable: true, example: 'dp_12345'),
            new OA\Property(property: 'resolved_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'withdrawn_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'payment', ref: '#/components/schemas/PaymentNested', nullable: true),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        ],
        type: 'object'
    )]
    public function dispute(): void {}

    #[OA\Schema(
        schema: 'PaymentMethod',
        required: ['id', 'type', 'is_default', 'created_at'],
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 7),
            new OA\Property(property: 'type', type: 'string', enum: ['credit_card', 'debit_card', 'pix', 'boleto', 'bank_debit']),
            new OA\Property(property: 'is_default', type: 'boolean', example: true),
            new OA\Property(property: 'brand', type: 'string', nullable: true, example: 'visa'),
            new OA\Property(property: 'last_four', type: 'string', nullable: true, example: '4242'),
            new OA\Property(property: 'expiry_month', type: 'integer', nullable: true, example: 12),
            new OA\Property(property: 'expiry_year', type: 'integer', nullable: true, example: 2028),
            new OA\Property(property: 'holder_name', type: 'string', nullable: true, example: 'Jane Doe'),
            new OA\Property(property: 'pix_key', type: 'string', nullable: true, example: 'billing@example.com'),
            new OA\Property(property: 'bank_name', type: 'string', nullable: true, example: 'ACME Bank'),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        ],
        type: 'object'
    )]
    public function paymentMethod(): void {}

    #[OA\Schema(
        schema: 'UserDataResponse',
        required: ['data'],
        example: [
            'data' => [
                'id' => 1,
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'email_verified_at' => null,
                'created_at' => '2026-02-27T12:00:00Z',
            ],
        ],
        properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/User'),
        ],
        type: 'object'
    )]
    public function userDataResponse(): void {}

    #[OA\Schema(
        schema: 'DashboardDataResponse',
        required: ['data'],
        example: [
            'data' => [
                'balance' => [
                    'outstanding_in_cents' => 14900,
                    'outstanding_formatted' => '$149.00',
                    'open_invoices_count' => 2,
                    'overdue_invoices_count' => 1,
                ],
                'next_due' => [
                    'invoice_number' => 'INV-2026-0001',
                    'amount_due_in_cents' => 14900,
                    'amount_due_formatted' => '$149.00',
                    'due_date' => '2026-03-05',
                    'is_overdue' => false,
                    'days_until_due' => 6,
                ],
                'subscription' => [
                    'id' => 10,
                    'status' => 'active',
                    'plan_name' => 'Pro',
                    'plan_slug' => 'pro',
                    'billing_cycle' => 'monthly',
                    'price_in_cents' => 9900,
                    'price_formatted' => '$99.00',
                    'current_period_end' => '2026-03-27T12:00:00Z',
                    'auto_renew' => true,
                    'auto_pay' => false,
                    'is_trial' => false,
                    'trial_ends_at' => null,
                ],
            ],
        ],
        properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/Dashboard'),
        ],
        type: 'object'
    )]
    public function dashboardDataResponse(): void {}

    #[OA\Schema(
        schema: 'InvoiceCollectionResponse',
        required: ['data', 'links', 'meta'],
        example: [
            'data' => [
                [
                    'id' => 45,
                    'invoice_number' => 'INV-2026-0001',
                    'status' => 'open',
                    'amount_in_cents' => 14900,
                    'amount_paid_in_cents' => 0,
                    'amount_due_in_cents' => 14900,
                    'amount_formatted' => '$149.00',
                    'currency' => 'USD',
                    'description' => 'March subscription',
                    'due_date' => '2026-03-05',
                    'paid_at' => null,
                    'period_start' => '2026-03-01',
                    'period_end' => '2026-03-31',
                    'is_overdue' => false,
                    'created_at' => '2026-02-27T12:00:00Z',
                ],
            ],
            'links' => [
                'first' => 'http://localhost/api/v1/invoices?page=1',
                'last' => 'http://localhost/api/v1/invoices?page=1',
                'prev' => null,
                'next' => null,
            ],
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'path' => 'http://localhost/api/v1/invoices',
                'per_page' => 15,
                'to' => 1,
                'total' => 1,
            ],
        ],
        properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/InvoiceSummary')),
            new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
            new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
        ],
        type: 'object'
    )]
    public function invoiceCollectionResponse(): void {}

    #[OA\Schema(
        schema: 'InvoiceDataResponse',
        required: ['data'],
        example: [
            'data' => [
                'id' => 45,
                'invoice_number' => 'INV-2026-0001',
                'status' => 'open',
                'amount_in_cents' => 14900,
                'amount_paid_in_cents' => 0,
                'amount_due_in_cents' => 14900,
                'amount_formatted' => '$149.00',
                'currency' => 'USD',
                'description' => 'March subscription',
                'due_date' => '2026-03-05',
                'paid_at' => null,
                'period_start' => '2026-03-01',
                'period_end' => '2026-03-31',
                'is_overdue' => false,
                'created_at' => '2026-02-27T12:00:00Z',
                'pdf_url' => null,
                'subscription' => [
                    'id' => 10,
                    'status' => 'active',
                    'plan_name' => 'Pro',
                    'plan_slug' => 'pro',
                    'billing_cycle' => 'monthly',
                    'price_in_cents' => 9900,
                    'price_formatted' => '$99.00',
                    'current_period_end' => '2026-03-27T12:00:00Z',
                    'auto_renew' => true,
                    'auto_pay' => false,
                    'is_trial' => false,
                    'trial_ends_at' => null,
                ],
                'payments' => [],
            ],
        ],
        properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/InvoiceDetail'),
        ],
        type: 'object'
    )]
    public function invoiceDataResponse(): void {}

    #[OA\Schema(
        schema: 'PaymentCollectionResponse',
        required: ['data', 'links', 'meta'],
        example: [
            'data' => [
                [
                    'id' => 88,
                    'amount_in_cents' => 9900,
                    'amount_formatted' => '$99.00',
                    'status' => 'succeeded',
                    'payment_method_type' => 'credit_card',
                    'gateway' => 'stub',
                    'pix_qr_code' => null,
                    'pix_expires_at' => null,
                    'boleto_url' => null,
                    'boleto_barcode' => null,
                    'boleto_expires_at' => null,
                    'failure_reason' => null,
                    'paid_at' => '2026-02-27T12:05:00Z',
                    'failed_at' => null,
                    'created_at' => '2026-02-27T12:00:00Z',
                ],
            ],
            'links' => [
                'first' => 'http://localhost/api/v1/payments?page=1',
                'last' => 'http://localhost/api/v1/payments?page=1',
                'prev' => null,
                'next' => null,
            ],
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'path' => 'http://localhost/api/v1/payments',
                'per_page' => 15,
                'to' => 1,
                'total' => 1,
            ],
        ],
        properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PaymentSummary')),
            new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
            new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
        ],
        type: 'object'
    )]
    public function paymentCollectionResponse(): void {}

    #[OA\Schema(
        schema: 'PaymentDataResponse',
        required: ['data'],
        example: [
            'data' => [
                'id' => 88,
                'amount_in_cents' => 9900,
                'amount_formatted' => '$99.00',
                'status' => 'succeeded',
                'payment_method_type' => 'credit_card',
                'gateway' => 'stub',
                'pix_qr_code' => null,
                'pix_expires_at' => null,
                'boleto_url' => null,
                'boleto_barcode' => null,
                'boleto_expires_at' => null,
                'failure_reason' => null,
                'paid_at' => '2026-02-27T12:05:00Z',
                'failed_at' => null,
                'invoice' => [
                    'id' => 45,
                    'invoice_number' => 'INV-2026-0001',
                    'status' => 'open',
                    'amount_in_cents' => 14900,
                    'amount_paid_in_cents' => 0,
                    'amount_due_in_cents' => 14900,
                    'amount_formatted' => '$149.00',
                    'currency' => 'USD',
                    'description' => 'March subscription',
                    'due_date' => '2026-03-05',
                    'paid_at' => null,
                    'period_start' => '2026-03-01',
                    'period_end' => '2026-03-31',
                    'is_overdue' => false,
                    'created_at' => '2026-02-27T12:00:00Z',
                ],
                'created_at' => '2026-02-27T12:00:00Z',
            ],
        ],
        properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/PaymentDetail'),
        ],
        type: 'object'
    )]
    public function paymentDataResponse(): void {}

    #[OA\Schema(
        schema: 'PlanCollectionResponse',
        required: ['data'],
        example: [
            'data' => [
                [
                    'id' => 2,
                    'name' => 'Pro',
                    'slug' => 'pro',
                    'billing_cycle' => 'monthly',
                    'price_in_cents' => 9900,
                    'price_formatted' => '$99.00',
                    'currency' => 'USD',
                    'trial_days' => 14,
                ],
            ],
        ],
        properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Plan')),
        ],
        type: 'object'
    )]
    public function planCollectionResponse(): void {}

    #[OA\Schema(
        schema: 'SubscriptionDataResponse',
        required: ['data'],
        example: [
            'data' => [
                'id' => 10,
                'status' => 'active',
                'auto_renew' => true,
                'auto_pay' => false,
                'current_period_start' => '2026-02-27T12:00:00Z',
                'current_period_end' => '2026-03-27T12:00:00Z',
                'trial_ends_at' => null,
                'canceled_at' => null,
                'cancel_at' => null,
                'plan' => [
                    'id' => 2,
                    'name' => 'Pro',
                    'slug' => 'pro',
                    'billing_cycle' => 'monthly',
                    'price_in_cents' => 9900,
                    'price_formatted' => '$99.00',
                    'currency' => 'USD',
                    'trial_days' => 14,
                ],
            ],
        ],
        properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/Subscription'),
        ],
        type: 'object'
    )]
    public function subscriptionDataResponse(): void {}

    #[OA\Schema(
        schema: 'DisputeCollectionResponse',
        required: ['data', 'links', 'meta'],
        example: [
            'data' => [
                [
                    'id' => 15,
                    'status' => 'open',
                    'reason' => 'fraudulent',
                    'description' => 'Unknown charge on statement.',
                    'gateway_dispute_id' => 'dp_12345',
                    'resolved_at' => null,
                    'withdrawn_at' => null,
                    'payment' => [
                        'id' => 88,
                        'status' => 'succeeded',
                        'amount_in_cents' => 9900,
                        'amount_formatted' => '$99.00',
                        'payment_method_type' => 'credit_card',
                    ],
                    'created_at' => '2026-02-27T12:10:00Z',
                ],
            ],
            'links' => [
                'first' => 'http://localhost/api/v1/disputes?page=1',
                'last' => 'http://localhost/api/v1/disputes?page=1',
                'prev' => null,
                'next' => null,
            ],
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'path' => 'http://localhost/api/v1/disputes',
                'per_page' => 15,
                'to' => 1,
                'total' => 1,
            ],
        ],
        properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Dispute')),
            new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
            new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
        ],
        type: 'object'
    )]
    public function disputeCollectionResponse(): void {}

    #[OA\Schema(
        schema: 'DisputeDataResponse',
        required: ['data'],
        example: [
            'data' => [
                'id' => 15,
                'status' => 'open',
                'reason' => 'fraudulent',
                'description' => 'Unknown charge on statement.',
                'gateway_dispute_id' => 'dp_12345',
                'resolved_at' => null,
                'withdrawn_at' => null,
                'payment' => [
                    'id' => 88,
                    'status' => 'succeeded',
                    'amount_in_cents' => 9900,
                    'amount_formatted' => '$99.00',
                    'payment_method_type' => 'credit_card',
                ],
                'created_at' => '2026-02-27T12:10:00Z',
            ],
        ],
        properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/Dispute'),
        ],
        type: 'object'
    )]
    public function disputeDataResponse(): void {}

    #[OA\Schema(
        schema: 'PaymentMethodCollectionResponse',
        required: ['data'],
        example: [
            'data' => [
                [
                    'id' => 7,
                    'type' => 'credit_card',
                    'is_default' => true,
                    'brand' => 'visa',
                    'last_four' => '4242',
                    'expiry_month' => 12,
                    'expiry_year' => 2028,
                    'holder_name' => 'Jane Doe',
                    'pix_key' => null,
                    'bank_name' => null,
                    'created_at' => '2026-02-27T12:00:00Z',
                ],
            ],
        ],
        properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PaymentMethod')),
        ],
        type: 'object'
    )]
    public function paymentMethodCollectionResponse(): void {}

    #[OA\Schema(
        schema: 'PaymentMethodDataResponse',
        required: ['data'],
        example: [
            'data' => [
                'id' => 7,
                'type' => 'credit_card',
                'is_default' => true,
                'brand' => 'visa',
                'last_four' => '4242',
                'expiry_month' => 12,
                'expiry_year' => 2028,
                'holder_name' => 'Jane Doe',
                'pix_key' => null,
                'bank_name' => null,
                'created_at' => '2026-02-27T12:00:00Z',
            ],
        ],
        properties: [
            new OA\Property(property: 'data', ref: '#/components/schemas/PaymentMethod'),
        ],
        type: 'object'
    )]
    public function paymentMethodDataResponse(): void {}
}
