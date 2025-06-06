<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Resources;

use App\CommonFunctions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellThroughByCategoryListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $category = $this->resource;

        $releasedDate = $category['original_created_at'] ?? $category['created_at'];

        return [
            'id' => $category->category_id,
            'name' => $category->name,
            'code' => $category->code,
            'received' => $category->received ? CommonFunctions::numberFormat((float) $category->received) : 0,
            'sold' => $category->sold ? CommonFunctions::numberFormat((float) $category->sold) : 0,
            'online_sold' => CommonFunctions::numberFormat((float) $category->online_sold),
            'net_sale_amount' => CommonFunctions::numberFormat((float) $category->net_sale_amount),
            'online_sale_amount' => CommonFunctions::numberFormat((float) $category->online_sale_amount),
            'balance' => CommonFunctions::numberFormat((float) $category->balance),
            'sell_through' => CommonFunctions::numberFormat((float) $category->sell_through),
            'date_released' => Carbon::parse($releasedDate)->format('d-m-Y h:i:s A'),
        ];
    }
}
