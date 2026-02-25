<?php

namespace App\Http\Resources\V1;

use App\Enums\PaymentMethodType;
use App\Traits\FormatsMonetary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    use FormatsMonetary;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isPix = $this->payment_method_type === PaymentMethodType::Pix;
        $isBoleto = $this->payment_method_type === PaymentMethodType::Boleto;

        return [
            'id' => $this->id,
            'amount_in_cents' => $this->amount_in_cents,
            'amount_formatted' => $this->formatCurrency($this->amount_in_cents, $this->currency),
            'status' => $this->status->value,
            'payment_method_type' => $this->payment_method_type->value,
            'gateway' => $this->gateway,
            'pix_qr_code' => $this->when($isPix, $this->pix_qr_code),
            'pix_expires_at' => $this->when($isPix, $this->pix_expires_at),
            'boleto_url' => $this->when($isBoleto, $this->boleto_url),
            'boleto_barcode' => $this->when($isBoleto, $this->boleto_barcode),
            'boleto_expires_at' => $this->when($isBoleto, $this->boleto_expires_at),
            'failure_reason' => $this->failure_reason,
            'paid_at' => $this->paid_at,
            'failed_at' => $this->failed_at,
            'invoice' => $this->whenLoaded('invoice', fn () => new InvoiceResource($this->invoice)),
            'created_at' => $this->created_at,
        ];
    }
}
