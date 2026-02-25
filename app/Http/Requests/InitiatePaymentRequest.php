<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'method' => ['required', 'string', Rule::in(PaymentMethodType::values())],
            'payment_method_id' => [
                Rule::requiredIf(fn () => PaymentMethodType::tryFrom($this->string('method')->toString())?->requiresStoredMethod() ?? false),
                'nullable',
                'integer',
                'exists:payment_methods,id',
            ],
            'amount_in_cents' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
