<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesTargetListResourceForPromoterApp extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $saleTarget = $this->resource;

        return [
            'id' => $saleTarget->id,
            'name' => $saleTarget->name,
        ];
    }
}
