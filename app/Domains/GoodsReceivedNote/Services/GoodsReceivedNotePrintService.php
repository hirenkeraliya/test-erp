<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\Services;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Services\ProductService;
use App\Models\GoodsReceivedNote;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GoodsReceivedNotePrintService
{
    public function goodsReceivedNotePrint(GoodsReceivedNote $goodsReceivedNote): string
    {
        if (config('app.product_variant')) {
            $goodsReceivedNoteProducts = $this->getFormattedData(
                $goodsReceivedNote->goodsReceivedNoteProducts->groupBy('product.masterProduct.article_number')
            );
        } else {
            $goodsReceivedNoteProducts = $this->getFormattedData(
                $goodsReceivedNote->goodsReceivedNoteProducts->groupBy('product.article_number')
            );
        }

        /** @var Location $location */
        $location = $goodsReceivedNote->location;
        $createdBy = $goodsReceivedNote->createdBy;
        $employee = $createdBy->employee;
        $locationType = LocationTypes::getFormattedCaseName($location->type_id);
        $productVariant = config('app.product_variant');

        return view('prints.goods_received_note', [
            'goodsReceivedNote' => $goodsReceivedNote,
            'goodsReceivedNoteProducts' => $goodsReceivedNoteProducts,
            'goodsReceivedCreatedUser' => $employee->getFullName() . '(' . $employee->staff_id . ')',
            'locationType' => $locationType,
            'productVariant' => $productVariant,
        ])->render();
    }

    public function getFormattedData(Collection $goodsReceivedProductsCollection): array
    {
        $productService = resolve(ProductService::class);
        $goodsReceivedProductsData = [];

        foreach ($goodsReceivedProductsCollection as $key => $goodsReceivedProducts) {
            $product = $goodsReceivedProducts->first()->product;

            $goodsReceivedProductsData[$key] = [
                'article_number' => $key,
                'qty' => 0,
                'items' => [],
            ];

            foreach ($goodsReceivedProducts as $goodReceivedProduct) {
                $product = $goodReceivedProduct->product;
                $batch = $goodReceivedProduct->batch;

                $batchExpiryDate = 'N/A';

                if ($batch && $batch->expiry_date) {
                    /** @var Carbon $batchExpiryDate */
                    $batchExpiryDate = Carbon::createFromFormat('Y-m-d', $batch->expiry_date);
                    $batchExpiryDate = $batchExpiryDate->format('d-m-Y');
                }

                $goodsReceivedProductsData[$key]['items'][] = [
                    'upc' => $product->upc,
                    'name' => $product->name,
                    'color' => config('app.product_variant') ? null : $product->color?->name ?? 'N/A',
                    'size' => config('app.product_variant') ? null : $product->size?->name ?? 'N/A',
                    'attributes' => $productService->getAttributesArray($product),
                    'quantity' => (float) $goodReceivedProduct->quantity,
                    'batch_number' => $batch ? $batch->number : 'N/A',
                    'batch_expiry_date' => $batchExpiryDate,
                ];
            }

            $goodsReceivedProductsData[$key]['quantity'] = collect($goodsReceivedProductsData[$key]['items'])->sum(
                'quantity'
            );
        }

        return $goodsReceivedProductsData;
    }
}
