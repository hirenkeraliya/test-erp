<?php

declare(strict_types=1);

namespace App\Domains\OrderPickingList\Services;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\OrderItem\OrderItemQueries;
use App\Domains\OrderPickingList\OrderPickingListQueries;
use App\Domains\Product\Services\ProductService;
use App\Models\MasterProduct;
use App\Models\Product;
use Illuminate\Support\Collection;

class PrintOrderPackingListService
{
    public function print(int $orderPickingListId, int $companyId): string
    {
        $productService = resolve(ProductService::class);
        $orderItemQueries = resolve(OrderItemQueries::class);
        $orderItems = $orderItemQueries->getOrderPickingListItemsBy($orderPickingListId);
        $orderItems = $this->preparedData($orderItems, $productService);

        $orderPickingListQueries = resolve(OrderPickingListQueries::class);
        $orderPickingList = $orderPickingListQueries->getById($orderPickingListId);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        return view('prints.order_packing_list', [
            'orderItems' => $orderItems,
            'company' => $company,
            'pickingListNumber' => $orderPickingList->number,
            'date' => now()->format('d-m-Y D h:s:i A'),
            'productVariant' => config('app.product_variant'),
        ])->render();
    }

    /**
     * @return mixed[]
     */
    private function preparedData(Collection $orderItems, ProductService $productService): array
    {
        $orderListData = [];
        foreach ($orderItems as $orderItem) {
            /** @var Product $product */
            $product = $orderItem->getProduct();

            if (config('app.product_variant')) {
                /** @var MasterProduct $masterProduct */
                $masterProduct = $product->masterProduct;
            }

            $orderListData[] = [
                'name' => $product->compound_product_name,
                'upc' => $product->getUpc(),
                'article_number' => config(
                    'app.product_variant'
                ) ? $masterProduct->article_number ?? 'N/A' : $product->article_number ?? 'N/A',
                'color' => config('app.product_variant') ? null : $product->color?->name,
                'size' => config('app.product_variant') ? null : $product->size?->name,
                'attributes' => $productService->getAttributesForPrint($product),
                'quantity' => CommonFunctions::numberFormat((float) $orderItem->total_quantity),
            ];
        }

        $orderListData[] = [
            'name' => 'Total',
            'upc' => '',
            'article_number' => '',
            'color' => '',
            'size' => '',
            'attributes' => '',
            'quantity' => collect($orderListData)->sum('quantity'),
        ];

        return $orderListData;
    }
}
