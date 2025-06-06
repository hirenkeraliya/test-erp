<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Resources;

use App\CommonFunctions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellThroughByColorListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $color = $this->resource;

        $releasedDate = $color['original_created_at'] ?? $color['created_at'];

        return [
            'id' => $color['color_id'],
            'name' => $color['name'],
            'received' => $color['received'] ? CommonFunctions::numberFormat((float) $color['received']) : 0,
            'sold' => $color['sold'] ? CommonFunctions::numberFormat((float) $color['sold']) : 0,
            'online_sold' => CommonFunctions::numberFormat((float) $color['online_sold']),
            'net_sale_amount' => CommonFunctions::numberFormat((float) $color['net_sale_amount']),
            'online_sale_amount' => CommonFunctions::numberFormat((float) $color['online_sale_amount']),
            'balance' => CommonFunctions::numberFormat((float) $color['balance']),
            'sell_through' => CommonFunctions::numberFormat((float) $color['sell_through']),
            'date_released' => Carbon::parse($releasedDate)->format('d-m-Y h:i:s A'),
        ];
    }
}
