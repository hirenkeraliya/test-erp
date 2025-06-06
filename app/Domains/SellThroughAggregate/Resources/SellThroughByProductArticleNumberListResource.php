<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Resources;

use App\CommonFunctions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellThroughByProductArticleNumberListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $sellThroughAggregate = $this->resource;

        $releasedDate = $sellThroughAggregate->original_created_at ?? $sellThroughAggregate->created_at;

        return [
            'id' => $sellThroughAggregate->article_number,
            'image' => $sellThroughAggregate->product->getDiskBasedFirstMediaUrl('thumbnail') ?? null,
            'name' => $sellThroughAggregate->name,
            'price' => $sellThroughAggregate->price ?? 0.00,
            'article_number' => $sellThroughAggregate->article_number,
            'received' => $sellThroughAggregate->received ? CommonFunctions::numberFormat(
                (float) $sellThroughAggregate->received
            ) : 0,
            'sold' => $sellThroughAggregate->sold ? CommonFunctions::numberFormat(
                (float) $sellThroughAggregate->sold
            ) : 0,
            'online_sold' => CommonFunctions::numberFormat((float) $sellThroughAggregate->online_sold),
            'net_sale_amount' => CommonFunctions::numberFormat((float) $sellThroughAggregate->net_sale_amount),
            'online_sale_amount' => CommonFunctions::numberFormat(
                (float) $sellThroughAggregate->online_sale_amount
            ),
            'balance' => CommonFunctions::numberFormat((float) $sellThroughAggregate->balance),
            'sell_through' => CommonFunctions::numberFormat((float) $sellThroughAggregate->sell_through),
            'date_released' => Carbon::parse($releasedDate)->format('d-m-Y h:i:s A'),
        ];
    }
}
