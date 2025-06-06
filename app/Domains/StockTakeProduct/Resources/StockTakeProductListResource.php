<?php

declare(strict_types=1);

namespace App\Domains\StockTakeProduct\Resources;

use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use App\Models\StockTakeProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTakeProductListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $stockTakeProducts = $this->resource;

        /** @var StockTakeProduct $stockTakeProduct */
        $stockTakeProduct = $stockTakeProducts->first();

        /** @var Product $product */
        $product = $stockTakeProduct->product;

        $articleNumberWiseData = [
            'product' => $product->name,
            'article_number' => $product->article_number,
            'total_submitted_stock' => $stockTakeProducts->sum('submitted_stock'),
            'items' => [],
        ];

        foreach ($stockTakeProducts as $stockTakeProduct) {
            /** @var Product $product */
            $product = $stockTakeProduct->product;

            /** @var ?Color $color */
            $color = $product->color;

            /** @var ?Size $size */
            $size = $product->size;

            $articleNumberWiseData['items'][] = [
                'id' => $product->id,
                'stock_take_product_id' => $stockTakeProduct->id,
                'UPC' => $product->upc,
                'color' => $color instanceof Color ? $color->getName() : 'N/A',
                'size' => $size instanceof Size ? $size->getName() : 'N/A',
                'submitted_stock' => $stockTakeProduct->submitted_stock,
            ];
        }

        return $articleNumberWiseData;
    }
}
