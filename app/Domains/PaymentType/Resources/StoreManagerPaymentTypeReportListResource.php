<?php

declare(strict_types=1);

namespace App\Domains\PaymentType\Resources;

use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class StoreManagerPaymentTypeReportListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Collection $salePayments */
        $salePayments = $this;

        return $salePayments->map(function ($salePayment): array {
            /** @var PaymentType $paymentType */
            $paymentType = $salePayment->paymentType;

            return [
                'id' => $paymentType->id,
                'name' => $paymentType->name,
                'total_transactions' => $salePayment->total_count,
                'total_amount' => $salePayment->total_amount,
            ];
        })->toArray();
    }
}
