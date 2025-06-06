<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Domains\Product\Enums\ProductTypes;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Style;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ProductUploadImagesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Product $product */
        $product = $this;

        /** @var ?Style $style */
        $style = $product->style;

        /** @var Brand $brand */
        $brand = $product->brand;

        /** @var Collection $categories */
        $categories = $product->categories;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'article_number' => $product->article_number,
            'type' => ProductTypes::getFormattedCaseName($product->type_id),
            'style' => $style instanceof Style ? $style->name : 'N/A',
            'brand' => $brand->name,
            'categories' => $categories->map(function ($category): array {
                /** @var Category $productCategory */
                $productCategory = $category;

                return [
                    'id' => $productCategory->id,
                    'name' => $productCategory->name,
                ];
            }),
        ];
    }
}
