<?php

declare(strict_types=1);

namespace App\Domains\TransitStock\Resources;

use App\CommonFunctions;
use App\Domains\TransitStock\Services\TransitInventoryReportService;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

class TransitInventoryReportListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $transitStock = $this->resource;

        $currentRouteName = Route::currentRouteName();

        $prefix = 'admin';
        if (null !== $currentRouteName && str_starts_with($currentRouteName, 'store_manager.')) {
            $prefix = 'store_manager';
        }

        if (null !== $currentRouteName && str_starts_with($currentRouteName, 'warehouse_manager.')) {
            $prefix = 'warehouse_manager';
        }

        $transitInventoryReportService = resolve(TransitInventoryReportService::class);
        $referenceNumberArray = $transitInventoryReportService->getTransitInventoryReportReferenceNumber(
            $transitStock,
            $prefix
        );

        /** @var Inventory $inventory */
        $inventory = $transitStock->inventory;

        /** @var Product $product */
        $product = $inventory->product;

        return [
            'item_name' => $product->name,
            'article_number' => $product->article_number,
            'color' => config('app.product_variant') ? null : $product->color?->name,
            'size' => config('app.product_variant') ? null : $product->size?->name,
            'upc' => $product->upc,
            'reference' => $referenceNumberArray,
            'stock' => CommonFunctions::numberFormatString((float) $transitStock->quantity),
            'attributes' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
        ];
    }
}
