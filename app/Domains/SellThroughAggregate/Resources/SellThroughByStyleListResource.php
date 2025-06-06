<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Resources;

use App\CommonFunctions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellThroughByStyleListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $style = $this->resource;

        $releasedDate = $style['original_created_at'] ?? $style['created_at'];

        return [
            'id' => $style['style_id'],
            'name' => $style['name'],
            'received' => $style['received'] ? CommonFunctions::numberFormat((float) $style['received']) : 0,
            'sold' => $style['sold'] ? CommonFunctions::numberFormat((float) $style['sold']) : 0,
            'online_sold' => CommonFunctions::numberFormat((float) $style['online_sold']),
            'net_sale_amount' => CommonFunctions::numberFormat((float) $style['net_sale_amount']),
            'online_sale_amount' => CommonFunctions::numberFormat((float) $style['online_sale_amount']),
            'balance' => CommonFunctions::numberFormat((float) $style['balance']),
            'sell_through' => CommonFunctions::numberFormat((float) $style['sell_through']),
            'date_released' => Carbon::parse($releasedDate)->format('d-m-Y h:i:s A'),
        ];
    }
}
