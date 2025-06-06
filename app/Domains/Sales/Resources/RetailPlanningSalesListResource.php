<?php

declare(strict_types=1);

namespace App\Domains\Sales\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RetailPlanningSalesListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $sale = $this->resource;

        return [
            'date' => $sale->date,
            'location_id' => $sale->location_id,
            'product_id' => $sale->product_id,
            'quantity' => $sale->quantity,
            'amount' => $sale->amount,
        ];
    }
}
