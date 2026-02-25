<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddPaymentMethodRequest extends FormRequest
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
        $type = $this->string('type')->toString();
        $isCard = in_array($type, [PaymentMethodType::CreditCard->value, PaymentMethodType::DebitCard->value], true);
        $isPix = $type === PaymentMethodType::Pix->value;
        $isBankDebit = $type === PaymentMethodType::BankDebit->value;

        return [
            'type' => ['required', 'string', Rule::in(PaymentMethodType::values())],
            'last_four' => [Rule::requiredIf($isCard), 'nullable', 'string', 'digits:4'],
            'brand' => [Rule::requiredIf($isCard), 'nullable', 'string', 'max:50'],
            'expiry_month' => [Rule::requiredIf($isCard), 'nullable', 'integer', 'between:1,12'],
            'expiry_year' => [Rule::requiredIf($isCard), 'nullable', 'integer', 'min:'.date('Y')],
            'holder_name' => [Rule::requiredIf($isCard || $isBankDebit), 'nullable', 'string', 'max:255'],
            'pix_key' => [Rule::requiredIf($isPix), 'nullable', 'string', 'max:255'],
            'bank_name' => [Rule::requiredIf($isBankDebit), 'nullable', 'string', 'max:100'],
        ];
    }
}
