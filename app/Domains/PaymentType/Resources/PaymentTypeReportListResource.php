<?php

declare(strict_types=1);

namespace App\Domains\PaymentType\Resources;

use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTypeReportListResource extends JsonResource
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
            'total_transactions' => $paymentType->total_transactions ?? 0,
            'total_amount' => $paymentType->total_amount ?? 0,
        ];
    }
}
