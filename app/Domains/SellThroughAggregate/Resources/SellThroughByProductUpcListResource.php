<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Resources;

use App\CommonFunctions;
use App\Domains\Product\Services\ProductService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellThroughByProductUpcListResource extends JsonResource
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
        $productService = resolve(ProductService::class);
        $releasedDate = $sellThroughAggregate->original_created_at ?? $sellThroughAggregate->created_at;

        return [
            'id' => $sellThroughAggregate->product_id,
            'image' => $sellThroughAggregate->product->getDiskBasedFirstMediaUrl('thumbnail') ?? null,
            'name' => $sellThroughAggregate->name,
            'price' => $sellThroughAggregate->price ?? 0.00,
            'upc' => $sellThroughAggregate->upc,
            'color' => config('app.product_variant') ? null : $sellThroughAggregate->color_name ?? 'N/A',
            'size' => config('app.product_variant') ? null : $sellThroughAggregate->size_name ?? 'N/A',
            'received' => $sellThroughAggregate->received,
            'sold' => $sellThroughAggregate->sold ?? 0,
            'online_sold' => CommonFunctions::numberFormat((float) $sellThroughAggregate->online_sold),
            'net_sale_amount' => CommonFunctions::numberFormat((float) $sellThroughAggregate->net_sale_amount),
            'online_sale_amount' => CommonFunctions::numberFormat((float) $sellThroughAggregate->online_sale_amount),
            'balance' => CommonFunctions::numberFormat((float) $sellThroughAggregate->balance),
            'sell_through' => CommonFunctions::numberFormat((float) $sellThroughAggregate->sell_through),
            'attributes' => $productService->getAttributesWithNameAndValueKey($sellThroughAggregate->product),
            'date_released' => Carbon::parse($releasedDate)->format('d-m-Y h:i:s A'),
        ];
    }
}
