<?php

declare(strict_types=1);

namespace App\Domains\ComplimentaryItemReason\Resources;

use App\CommonFunctions;
use App\Models\ComplimentaryItemReason;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminComplimentaryItemReasonListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var ComplimentaryItemReason $complimentaryItemReason */
        $complimentaryItemReason = $this;

        $saleDiscount = $complimentaryItemReason->saleDiscountComplimentaryItemReason;
        $saleItemDiscount = $complimentaryItemReason->saleItemDiscountComplimentaryItemReason;

        return [
            'id' => $complimentaryItemReason->id,
            'reason' => $complimentaryItemReason->reason,
            'total_used_counts' => ($saleDiscount->count() + $saleItemDiscount->count()),
            'total_discount_amount' => CommonFunctions::numberFormat(
                $saleDiscount->sum('amount') + $saleItemDiscount->sum('amount')
            ),
        ];
    }
}
