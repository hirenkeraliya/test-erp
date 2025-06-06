<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Resources;

use App\CommonFunctions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellThroughBySizeListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $size = $this->resource;

        $releasedDate = $size['original_created_at'] ?? $size['created_at'];

        return [
            'id' => $size['size_id'],
            'name' => $size['name'],
            'received' => $size['received'] ? CommonFunctions::numberFormat((float) $size['received']) : 0,
            'sold' => $size['sold'] ? CommonFunctions::numberFormat((float) $size['sold']) : 0,
            'online_sold' => CommonFunctions::numberFormat((float) $size['online_sold']),
            'net_sale_amount' => CommonFunctions::numberFormat((float) $size['net_sale_amount']),
            'online_sale_amount' => CommonFunctions::numberFormat((float) $size['online_sale_amount']),
            'balance' => CommonFunctions::numberFormat((float) $size['balance']),
            'sell_through' => CommonFunctions::numberFormat((float) $size['sell_through']),
            'date_released' => Carbon::parse($releasedDate)->format('d-m-Y h:i:s A'),
        ];
    }
}
