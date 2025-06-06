<?php

declare(strict_types=1);

namespace App\Domains\PromoterGroup\Resources;

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromoterGroupListsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $promoterGroup = $this->resource;

        return [
            'id' => $promoterGroup->id,
            'name' => $promoterGroup->name,
            'code' => $promoterGroup->code,
            'type' => SaleReturnOrVoidSaleReasonTypes::getFormattedCaseName($promoterGroup->type_id->value),
        ];
    }
}
