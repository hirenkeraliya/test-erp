<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderReceive\Services;

use App\Domains\ExternalPurchaseOrderReceive\ExternalPurchaseOrderReceiveQueries;
use App\Models\Company;
use App\Models\ExternalPurchaseOrder;
use App\Models\ExternalPurchaseOrderItem;
use App\Models\ExternalPurchaseOrderPartialReceiveItem;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchasePlan;
use App\Models\Vendor;
use Illuminate\Support\Collection;

class ExternalPurchaseOrderPartialReceivePrintService
{
    public function print(int $externalPurchaseOrderReceiveId): string
    {
        $externalPurchaseOrderReceiveQueries = resolve(ExternalPurchaseOrderReceiveQueries::class);

        $externalPurchaseOrderReceive = $externalPurchaseOrderReceiveQueries->getById($externalPurchaseOrderReceiveId);

        $externalPurchaseOrderReceiveItems = $externalPurchaseOrderReceive->getItems();
        $externalPurchaseOrderReceiveItemsData = $this->getFormattedData($externalPurchaseOrderReceiveItems);

        /** @var ExternalPurchaseOrder $externalPurchaseOrder */
        $externalPurchaseOrder = $externalPurchaseOrderReceive->externalPurchaseOrder;

        /** @var PurchasePlan $purchasePlan */
        $purchasePlan = $externalPurchaseOrder->purchasePlan;

        /** @var Vendor $vendor */
        $vendor = $purchasePlan->vendor;

        /** @var Location $location */
        $location = $purchasePlan->location;

        /** @var Company $company */
        $company = $purchasePlan->company;

        return view('prints.external_purchase_order_partial_receive', [
            'externalPurchaseOrderReceive' => $externalPurchaseOrderReceive,
            'purchasePlan' => $purchasePlan,
            'fromCompany' => $company,
            'fromLocation' => $vendor,
            'toLocation' => $location,
            'externalPurchaseOrder' => $externalPurchaseOrder,
            'externalPurchaseOrderReceiveItems' => $externalPurchaseOrderReceiveItemsData,
        ])->render();
    }

    public function getFormattedData(Collection $externalPurchaseOrderReceiveItems): array
    {
        $externalPurchaseOrderReceiveItemsData = [];
        $groupByArticleNumberExternalPurchaseOrderItems = $externalPurchaseOrderReceiveItems->groupBy(
            'externalPurchaseOrderItem.product.article_number'
        );

        foreach ($groupByArticleNumberExternalPurchaseOrderItems as $externalPurchaseOrderArticleWiseItems) {
            $firstExternalPurchaseOrderItem = $externalPurchaseOrderArticleWiseItems->first();
            $externalPurchaseOrderItem = $firstExternalPurchaseOrderItem->externalPurchaseOrderItem;
            $product = $externalPurchaseOrderItem->product;

            if (! isset($externalPurchaseOrderReceiveItemsData[$product->article_number])) {
                $externalPurchaseOrderReceiveItemsData[$product->article_number] = [
                    'products' => [],
                    'article_number' => $product->article_number,
                    'product_name' => $product->name,
                    'quantity' => 0,
                    'received_quantity' => 0,
                ];
            }

            foreach ($externalPurchaseOrderArticleWiseItems as $externalPurchaseOrderPartialReceiveItem) {
                $externalPurchaseOrderReceiveItemsData[$product->article_number]['quantity'] += $externalPurchaseOrderPartialReceiveItem->externalPurchaseOrderItem->received_quantity;
                $externalPurchaseOrderReceiveItemsData[$product->article_number]['received_quantity'] += $externalPurchaseOrderPartialReceiveItem->quantity_received;

                $externalPurchaseOrderReceiveItemsData[$product->article_number]['products'][] = $this->preparedItem(
                    $externalPurchaseOrderPartialReceiveItem
                );
            }
        }

        return $externalPurchaseOrderReceiveItemsData;
    }

    private function preparedItem(
        ExternalPurchaseOrderPartialReceiveItem $externalPurchaseOrderPartialReceiveItem
    ): array {
        /** @var ExternalPurchaseOrderItem $externalPurchaseOrderItem */
        $externalPurchaseOrderItem = $externalPurchaseOrderPartialReceiveItem->externalPurchaseOrderItem;

        /** @var Product $product */
        $product = $externalPurchaseOrderItem->product;

        return [
            'name' => $product->name,
            'article_number' => $product->article_number,
            'color' => $product->color?->name,
            'size' => $product->size?->name,
            'upc' => $product->upc,
            'quantity' => $externalPurchaseOrderItem->quantity,
            'received_quantity' => $externalPurchaseOrderPartialReceiveItem->quantity_received,
            'notes' => $externalPurchaseOrderPartialReceiveItem->notes ?? 'N/A',
        ];
    }
}
