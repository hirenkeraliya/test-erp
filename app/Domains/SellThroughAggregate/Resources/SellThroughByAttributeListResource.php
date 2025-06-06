<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Resources;

use App\CommonFunctions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellThroughByAttributeListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $attribute = $this->resource;

        $releasedDate = $attribute['original_created_at'] ?? $attribute['created_at'];

        return [
            'name' => $attribute['name'],
            'received' => $attribute['received'] ? CommonFunctions::numberFormat((float) $attribute['received']) : 0,
            'sold' => $attribute['sold'] ? CommonFunctions::numberFormat((float) $attribute['sold']) : 0,
            'online_sold' => CommonFunctions::numberFormat((float) $attribute['online_sold']),
            'net_sale_amount' => CommonFunctions::numberFormat((float) $attribute['net_sale_amount']),
            'online_sale_amount' => CommonFunctions::numberFormat((float) $attribute['online_sale_amount']),
            'balance' => CommonFunctions::numberFormat((float) $attribute['balance']),
            'sell_through' => CommonFunctions::numberFormat((float) $attribute['sell_through']),
            'date_released' => Carbon::parse($releasedDate)->format('d-m-Y h:i:s A'),
        ];
    }
}
