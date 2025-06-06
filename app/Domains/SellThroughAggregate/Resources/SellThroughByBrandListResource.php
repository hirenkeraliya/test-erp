<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Resources;

use App\CommonFunctions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellThroughByBrandListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $brand = $this->resource;

        $releasedDate = $brand['original_created_at'] ?? $brand['created_at'];

        return [
            'id' => $brand['brand_id'],
            'name' => $brand['name'],
            'received' => $brand['received'] ? CommonFunctions::numberFormat((float) $brand['received']) : 0,
            'sold' => $brand['sold'] ? CommonFunctions::numberFormat((float) $brand['sold']) : 0,
            'online_sold' => CommonFunctions::numberFormat((float) $brand['online_sold']),
            'net_sale_amount' => CommonFunctions::numberFormat((float) $brand['net_sale_amount']),
            'online_sale_amount' => CommonFunctions::numberFormat((float) $brand['online_sale_amount']),
            'balance' => CommonFunctions::numberFormat((float) $brand['balance']),
            'sell_through' => CommonFunctions::numberFormat((float) $brand['sell_through']),
            'date_released' => Carbon::parse($releasedDate)->format('d-m-Y h:i:s A'),
        ];
    }
}
