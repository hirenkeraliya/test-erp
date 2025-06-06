<?php

declare(strict_types=1);

namespace App\Domains\PaymentType;

use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderPaymentTypeListResource extends JsonResource
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
            'image_name' => config('app.url') . '/images/payment_types/' . $paymentType->image_name,
        ];
    }
}
