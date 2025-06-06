<?php

declare(strict_types=1);

namespace App\Domains\OrderPickingList\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\OrderPickingListItem\OrderPickingListItemQueries;
use App\Domains\Product\Services\ProductService;
use App\Models\MasterProduct;
use App\Models\Member;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;

class PrintOrderPickingListService
{
    public function print(int $orderPickingListId, int $companyId): string
    {
        $productService = resolve(ProductService::class);
        $orderPickingListItemQueries = resolve(OrderPickingListItemQueries::class);
        $orderPickingListItems = $orderPickingListItemQueries->getOrderPickingListForOrder(
            $orderPickingListId,
            $companyId
        );

        $pickingListNumber = $orderPickingListItems->first()->orderPickingList->number;

        $orders = $this->preparedData($orderPickingListItems, $productService);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        return view('prints.order_picking_list', [
            'orders' => $orders,
            'company' => $company,
            'pickingListNumber' => $pickingListNumber,
            'date' => now()->format('d-m-Y D h:s:i A'),
            'productVariant' => config('app.product_variant'),
        ])->render();
    }

    /**
     * @return mixed[]
     */
    private function preparedData(Collection $orderPickingListItems, ProductService $productService): array
    {
        $orderPickingListData = [];
        foreach ($orderPickingListItems as $orderPickingListItem) {
            /** @var Order $order */
            $order = $orderPickingListItem->order;

            /** @var Collection $orderItems */
            $orderItems = $order->getOrderItems();

            /** @var ?Member $member */
            $member = $order->getMember();

            $orderPickingListData[] = [
                'member' => $member instanceof Member ? $member->getFullName() : null,
                'receipt_number' => $order->getReceiptNumber(),
                'date' => $order->getCreatedAt()?->format('Y-m-d'),
                'order_items' => $this->getPreparedOrderItems($orderItems, $productService),
                'total_quantity' => collect($this->getPreparedOrderItems($orderItems, $productService))->sum(
                    'quantity'
                ),
            ];
        }

        $orderPickingListData[] = [
            'member' => '',
            'receipt_number' => '',
            'date' => 'Total',
            'order_items' => '',
            'total_quantity' => collect($orderPickingListData)->sum('total_quantity'),
        ];

        return $orderPickingListData;
    }

    private function getPreparedOrderItems(Collection $orderItems, ProductService $productService): array
    {
        return $orderItems->map(function ($orderItem) use ($productService): array {
            /** @var Product $product */
            $product = $orderItem->getProduct();

            if (config('app.product_variant')) {
                /** @var MasterProduct $masterProduct */
                $masterProduct = $product->masterProduct;
            }

            return [
                'id' => $orderItem->getKey(),
                'name' => $product->compound_product_name,
                'upc' => $product->getUpc(),
                'article_number' => config(
                    'app.product_variant'
                ) ? $masterProduct->article_number ?? 'N/A' : $product->article_number ?? 'N/A',
                'color' => config('app.product_variant') ? null : $product->color?->name,
                'size' => config('app.product_variant') ? null : $product->size?->name,
                'attributes' => $productService->getAttributesForPrint($product),
                'quantity' => $orderItem->getQuantity(),
            ];
        })->toArray();
    }
}
