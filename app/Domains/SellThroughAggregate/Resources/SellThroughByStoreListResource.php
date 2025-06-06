<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Resources;

use App\CommonFunctions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellThroughByStoreListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $location = $this->resource;

        $releasedDate = $location['original_created_at'] ?? $location['created_at'];

        return [
            'id' => $location->location_id,
            'name' => $location->name,
            'code' => $location->code,
            'received' => $location->received ? CommonFunctions::numberFormat((float) $location->received) : 0,
            'sold' => $location->sold ? CommonFunctions::numberFormat((float) $location->sold) : 0,
            'online_sold' => CommonFunctions::numberFormat((float) $location->online_sold),
            'net_sale_amount' => CommonFunctions::numberFormat((float) $location->net_sale_amount),
            'online_sale_amount' => CommonFunctions::numberFormat((float) $location->online_sale_amount),
            'balance' => CommonFunctions::numberFormat((float) $location->balance),
            'sell_through' => CommonFunctions::numberFormat((float) $location->sell_through),
            'date_released' => Carbon::parse($releasedDate)->format('d-m-Y h:i:s A'),
        ];
    }
}
