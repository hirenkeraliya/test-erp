<?php

declare(strict_types=1);

namespace App\Domains\VoidSaleReason\Resources;

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class VoidSaleListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $voidSaleReason = $this->resource;

        /** @var Collection $voidSaleReasonTypes */
        $voidSaleReasonTypes = $voidSaleReason->voidSaleReasonTypes;

        $types = $voidSaleReasonTypes->map(
            fn ($voidSaleReasonType): string => SaleReturnOrVoidSaleReasonTypes::getFormattedCaseName(
                $voidSaleReasonType->type_id
            )
        )->implode(', ');

        return [
            'id' => $voidSaleReason->id,
            'reason' => $voidSaleReason->reason,
            'type' => $types,
        ];
    }
}
