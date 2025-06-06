<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrder\Services;

use App\Domains\ExternalPurchaseOrder\Enums\Statuses;
use App\Domains\ExternalPurchaseOrder\ExternalPurchaseOrderQueries;
use App\Domains\ExternalPurchaseOrderTransaction\ExternalPurchaseOrderTransactionQueries;
use App\Models\Company;
use App\Models\ExternalPurchaseOrderItem;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchasePlan;
use App\Models\Vendor;
use Illuminate\Support\Collection;

class ExternalPurchaseOrderPrintService
{
    public function print(int $externalPurchaseOrderId): string
    {
        $externalPurchaseOrderQueries = resolve(ExternalPurchaseOrderQueries::class);

        $externalPurchaseOrder = $externalPurchaseOrderQueries->getByIdForPrint($externalPurchaseOrderId);

        $externalPurchaseOrderItems = $externalPurchaseOrder->getItems();
        $externalPurchaseOrderItemsData = $this->getFormattedData($externalPurchaseOrderItems);

        /** @var PurchasePlan $purchasePlan */
        $purchasePlan = $externalPurchaseOrder->purchasePlan;

        /** @var Vendor $vendor */
        $vendor = $purchasePlan->vendor;

        /** @var Location $location */
        $location = $purchasePlan->location;

        /** @var Company $company */
        $company = $purchasePlan->company;

        return view('prints.external_purchase_order', [
            'externalPurchaseOrder' => $externalPurchaseOrder,
            'purchasePlan' => $purchasePlan,
            'fromCompany' => $company,
            'fromLocation' => $vendor,
            'toLocation' => $location,
            'externalPurchaseOrderItems' => $externalPurchaseOrderItemsData,
            'partialBy' => $this->getTransactionUserName($externalPurchaseOrder->id, Statuses::PARTIAL->value),
            'cancelledBy' => $this->getTransactionUserName($externalPurchaseOrder->id, Statuses::CANCELLED->value),
            'completedBy' => $this->getTransactionUserName($externalPurchaseOrder->id, Statuses::COMPLETED->value),
        ])->render();
    }

    public function getTransactionUserName(int $externalPurchaseOrderId, int $status): ?string
    {
        $externalPurchaseOrderTransactionQueries = resolve(ExternalPurchaseOrderTransactionQueries::class);

        $externalPurchaseOrderTransaction = $externalPurchaseOrderTransactionQueries->getByExternalPurchaseOrderIdAndNewStatus(
            $externalPurchaseOrderId,
            $status
        );

        if (! $externalPurchaseOrderTransaction) {
            return 'N/A';
        }

        $user = $externalPurchaseOrderTransaction->user;

        $employee = $user->employee;
        if ($employee) {
            return $employee->getFullName();
        }

        return 'N/A';
    }

    public function getFormattedData(Collection $externalPurchaseOrderItems): array
    {
        $externalPurchaseOrderItemsData = [];
        $groupByArticleNumberExternalPurchaseOrderItems = $externalPurchaseOrderItems->groupBy(
            'product.article_number'
        );

        foreach ($groupByArticleNumberExternalPurchaseOrderItems as $externalPurchaseOrderArticleWiseItems) {
            $firstExternalPurchaseOrderItem = $externalPurchaseOrderArticleWiseItems->first();
            $product = $firstExternalPurchaseOrderItem->product;

            $externalPurchaseOrderItemsData[$product->article_number]['products'] = [];
            $externalPurchaseOrderItemsData[$product->article_number]['article_number'] = $product->article_number;
            $externalPurchaseOrderItemsData[$product->article_number]['product_name'] = $product->name;
            $externalPurchaseOrderItemsData[$product->article_number]['quantity'] = $externalPurchaseOrderArticleWiseItems->sum(
                'quantity'
            );
            $externalPurchaseOrderItemsData[$product->article_number]['received_quantity'] = $externalPurchaseOrderArticleWiseItems->sum(
                'received_quantity'
            );

            foreach ($externalPurchaseOrderArticleWiseItems as $externalpurchaseOrderItem) {
                $externalPurchaseOrderItemsData[$product->article_number]['products'][] = $this->preparedItem(
                    $externalpurchaseOrderItem
                );
            }
        }

        return $externalPurchaseOrderItemsData;
    }

    private function preparedItem(ExternalPurchaseOrderItem $externalPurchaseOrderItem): array
    {
        /** @var Product $product */
        $product = $externalPurchaseOrderItem->product;

        return [
            'name' => $product->name,
            'article_number' => $product->article_number,
            'color' => $product->color?->name,
            'size' => $product->size?->name,
            'upc' => $product->upc,
            'quantity' => $externalPurchaseOrderItem->quantity,
            'received_quantity' => $externalPurchaseOrderItem->received_quantity,
            'remarks' => $externalPurchaseOrderItem->remarks,
        ];
    }
}
