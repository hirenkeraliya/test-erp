<?php

declare(strict_types=1);

namespace App\Domains\PurchasePlan\Services;

use App\Domains\Product\Services\ProductService;
use App\Domains\PurchasePlan\Enums\Statuses;
use App\Domains\PurchasePlan\PurchasePlanQueries;
use App\Domains\PurchasePlanTransaction\PurchasePlanTransactionQueries;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchasePlanItem;
use App\Models\Vendor;
use Illuminate\Support\Collection;

class PurchasePlanPrintService
{
    public function print(int $purchasePlanId, int $companyId, ?int $locationId = null): string
    {
        $purchasePlanQueries = resolve(PurchasePlanQueries::class);

        $purchasePlan = $purchasePlanQueries->getByIdForPrint($purchasePlanId, $locationId, $companyId);

        /** @var Location $location */
        $location = $purchasePlan->location;

        /** @var Vendor $vendor */
        $vendor = $purchasePlan->vendor;

        $purchasePlanItems = $purchasePlan->getItems();
        $purchasePlanItemsData = $this->getFormattedData($purchasePlanItems);

        foreach ($purchasePlanItemsData as &$purchasePlanItem) {
            $totalQty = 0;
            $totalTransferQty = 0;

            foreach ($purchasePlanItem['products'] as $product) {
                $totalQty += $product['quantity'];
                $totalTransferQty += $product['transferred_quantity'];
            }

            $purchasePlanItem['total_quantity'] = $totalQty;
            $purchasePlanItem['total_transferred_quantity'] = $totalTransferQty;
        }

        return view('prints.purchase_plan', [
            'purchasePlan' => $purchasePlan,
            'toLocation' => $location,
            'fromLocation' => $vendor,
            'purchasePlanItems' => $purchasePlanItemsData,
            'approvedBy' => $this->getTransactionUserName($purchasePlanId, Statuses::APPROVED->value),
            'cancelledBy' => $this->getTransactionUserName($purchasePlanId, Statuses::CANCELLED->value),
            'closedBy' => $this->getTransactionUserName($purchasePlanId, Statuses::COMPLETED->value),
        ])->render();
    }

    public function getTransactionUserName(int $purchasePlanId, int $status): ?string
    {
        $purchasePlanTransactionQueries = resolve(PurchasePlanTransactionQueries::class);

        $purchasePlanTransaction = $purchasePlanTransactionQueries->getByPurchasePlanIdAndNewStatus(
            $purchasePlanId,
            $status
        );

        if (! $purchasePlanTransaction) {
            return 'N/A';
        }

        $user = $purchasePlanTransaction->user;

        $employee = $user->employee;
        if ($employee) {
            return $employee->getFullName();
        }

        return 'N/A';
    }

    public function getFormattedData(Collection $purchasePlanItems): array
    {
        $purchasePlanItemsData = [];

        if (config('app.product_variant')) {
            foreach ($purchasePlanItems->groupBy(
                'product.masterProduct.article_number'
            ) as $purchasePlanArticleWiseItems) {
                $firstPurchasePlanItem = $purchasePlanArticleWiseItems->first();
                $product = $firstPurchasePlanItem->product;
                $purchasePlanItemsData[$product->masterProduct->article_number]['article_number'] = $product->masterProduct->article_number;
                $purchasePlanItemsData[$product->masterProduct->article_number]['product_name'] = $product->masterProduct->name;
                $purchasePlanItemsData[$product->masterProduct->article_number]['products'] = [];

                foreach ($purchasePlanArticleWiseItems as $purchasePlanItem) {
                    $purchasePlanItemsData[$product->masterProduct->article_number]['products'][] = $this->preparedItem(
                        $purchasePlanItem
                    );
                }
            }

            return $purchasePlanItemsData;
        }

        foreach ($purchasePlanItems->groupBy('product.article_number') as $purchasePlanArticleWiseItems) {
            $firstPurchasePlanItem = $purchasePlanArticleWiseItems->first();
            $product = $firstPurchasePlanItem->product;
            $purchasePlanItemsData[$product->article_number]['article_number'] = $product->article_number;
            $purchasePlanItemsData[$product->article_number]['product_name'] = $product->name;
            $purchasePlanItemsData[$product->article_number]['products'] = [];

            foreach ($purchasePlanArticleWiseItems as $purchasePlanItem) {
                $purchasePlanItemsData[$product->article_number]['products'][] = $this->preparedItem(
                    $purchasePlanItem
                );
            }
        }

        return $purchasePlanItemsData;
    }

    private function preparedItem(PurchasePlanItem $purchasePlanItem): array
    {
        /** @var Product $product */
        $product = $purchasePlanItem->product;
        $productService = resolve(ProductService::class);

        [$color, $size] = $productService->getColorAndSize($product);

        return [
            'name' => $product->name,
            'article_number' => $product->article_number,
            'color' => $color,
            'size' => $size,
            'upc' => $product->upc,
            'quantity' => $purchasePlanItem->quantity,
            'transferred_quantity' => $purchasePlanItem->transferred_quantity ?? 0,
        ];
    }
}
