<?php

declare(strict_types=1);

namespace App\Domains\SaleReturnReason\Resources;

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class SaleReturnReasonListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $saleReturnReason = $this->resource;

        /** @var Collection $saleReturnReasonTypes */
        $saleReturnReasonTypes = $saleReturnReason->saleReturnReasonTypes;

        $types = $saleReturnReasonTypes->map(
            fn ($saleReturnReasonType): string => SaleReturnOrVoidSaleReasonTypes::getFormattedCaseName(
                $saleReturnReasonType->type_id
            )
        )->implode(', ');

        return [
            'id' => $saleReturnReason->id,
            'reason' => $saleReturnReason->reason,
            'type' => $types,
            'put_back_in_inventory' => $saleReturnReason->put_back_in_inventory,
        ];
    }
}
