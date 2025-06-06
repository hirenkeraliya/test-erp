<?php

declare(strict_types=1);

namespace App\Domains\PaymentType\Resources;

use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosPaymentTypeListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var PaymentType $paymentType */
        $paymentType = $this;

        return [
            'id' => $paymentType->id,
            'name' => $paymentType->name,
            'status' => (int) $paymentType->status,
            'sub_payment_types' => $paymentType->activeSubPaymentTypes->map(fn ($subPaymentType): array => [
                'id' => $subPaymentType->id,
                'name' => $subPaymentType->name,
                'is_member_required' => $subPaymentType->is_member_required,
                'is_available_for_refund' => $subPaymentType->is_available_for_refund,
                'trigger_maybank_card' => $subPaymentType->trigger_card_payment_machine,
                'trigger_maybank_qr_code' => $subPaymentType->trigger_qr_code_payment_machine,
                'trigger_card_payment_machine' => $subPaymentType->trigger_card_payment_machine,
                'trigger_qr_code_payment_machine' => $subPaymentType->trigger_qr_code_payment_machine,
                'trigger_card_affin_payment_machine' => $subPaymentType->trigger_card_affin_payment_machine,
                'trigger_card_bank_rakyat_terminal	' => $subPaymentType->trigger_card_bank_rakyat_terminal,
                'is_card_payment' => $subPaymentType->is_card_payment,
                'image_name' => config('app.url') . '/images/payment_types/' . $subPaymentType->image_name,
            ]),
            'is_member_required' => $paymentType->is_member_required,
            'is_available_for_refund' => $paymentType->is_available_for_refund,
            'trigger_maybank_card' => $paymentType->trigger_card_payment_machine,
            'trigger_maybank_qr_code' => $paymentType->trigger_qr_code_payment_machine,
            'trigger_card_payment_machine' => $paymentType->trigger_card_payment_machine,
            'trigger_qr_code_payment_machine' => $paymentType->trigger_qr_code_payment_machine,
            'trigger_card_affin_payment_machine' => $paymentType->trigger_card_affin_payment_machine,
            'trigger_card_bank_rakyat_terminal' => $paymentType->trigger_card_bank_rakyat_terminal,
            'is_card_payment' => $paymentType->is_card_payment,
            'image_name' => config('app.url') . '/images/payment_types/' . $paymentType->image_name,
            'payment_terminal_key' => $paymentType->payment_terminal_key ?? null,
            'site_key' => $paymentType->site_key ?? null,
            'secret_key' => $paymentType->secret_key ?? null,
        ];
    }
}
