<?php

declare(strict_types=1);

namespace App\Domains\Sale\Resources;

use App\Models\Promoter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PromoterSalesListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Promoter $promoter */
        $promoter = $this;

        /** @var Collection $saleItems */
        $saleItems = $promoter->saleItems;

        return [
            'promoter' => $promoter->employee,
            /* @phpstan-ignore-next-line */
            'units_sold' => $promoter->units_sold,
            'units_returned' => $saleItems->sum('returned_quantity'),
            'total_units_returned_amount' => $this->getTotalUnitsReturnAmount($saleItems),
            /* @phpstan-ignore-next-line */
            'gross_amount' => $promoter->gross_amount,
            /* @phpstan-ignore-next-line */
            'discount_amount' => $promoter->discount_amount,
            /* @phpstan-ignore-next-line */
            'tax_amount' => $promoter->tax_amount,
            /* @phpstan-ignore-next-line */
            'net_amount' => $promoter->net_amount,
        ];
    }

    public function getTotalUnitsReturnAmount(Collection $saleItems): int|float
    {
        $saleItems = $saleItems->where('returned_quantity', '!=', 0.00);

        if ($saleItems->isNotEmpty()) {
            return $saleItems->sum('returned_quantity') * $saleItems->sum('price_paid_per_unit');
        }

        return 0.00;
    }
}
