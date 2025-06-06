<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommissionUpdate\Resources;

use App\CommonFunctions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromoterCommissionHistoryListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $promoterCommissionUpdate = $this->resource;

        return [
            'date' => $promoterCommissionUpdate['happened_at'],
            'item_sold' => (int) CommonFunctions::truncateDecimal(
                (float) $promoterCommissionUpdate['total_units_sold']
            ),
            'item_returned' => (int) CommonFunctions::truncateDecimal(
                (float) $promoterCommissionUpdate['total_sale_return_units_sold']
            ),
            'commission' => (float) CommonFunctions::currencyFormat(
                (float) $promoterCommissionUpdate['total_commission_amount'],
                4
            ),
        ];
    }
}
