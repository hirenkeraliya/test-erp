<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Services;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Services\ProductService;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrderTransaction\PurchaseOrderTransactionQueries;
use App\Models\City;
use App\Models\ExternalLocation;
use App\Models\Location;
use App\Models\Model;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Collection;

class PurchaseOrderPrintService
{
    public function print(
        int $purchaseOrderId,
        int $companyId,
        ?int $locationId = null,
        ?string $locationType = null
    ): string {
        $toCity = null;
        $fromCity = null;
        $productVariant = config('app.product_variant');
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        $purchaseOrder = $purchaseOrderQueries->getByIdForPrint(
            $purchaseOrderId,
            $companyId,
            $locationId,
            $locationType
        );

        $orderTypeName = OrderTypes::getFormattedCaseName($purchaseOrder->order_type);

        [$toCompany, $fromCompany] = $this->getToAndFromCompany($purchaseOrder);
        [$toLocation, $fromLocation] = $this->getToAndFromLocation($purchaseOrder);
        [$toLocationType, $fromLocationType] = $this->getToAndFromLocationType($purchaseOrder);

        $toCity = $toLocation->city instanceof City ? $toLocation->city->name ?? 'N/A' : $toLocation->city ?? '';

        $fromCity = $fromLocation->city instanceof City ? $fromLocation->city->name ?? 'N/A' : $fromLocation->city ?? '';

        $purchaseOrderItems = $purchaseOrder->getItems();
        $purchaseOrderItemsData = $this->getFormattedData($purchaseOrderItems);

        foreach ($purchaseOrderItemsData as &$purchaseOrderItem) {
            $totalQty = 0;
            $totalTransferQty = 0;

            foreach ($purchaseOrderItem['products'] as $product) {
                $totalQty += $product['quantity'];
                $totalTransferQty += $product['transferred_quantity'];
            }

            $purchaseOrderItem['total_quantity'] = $totalQty;
            $purchaseOrderItem['total_transferred_quantity'] = $totalTransferQty;
        }

        return view('prints.purchase_order', [
            'purchaseOrder' => $purchaseOrder,
            'toCompany' => $toCompany,
            'fromCompany' => $fromCompany,
            'toLocation' => $toLocation,
            'fromLocation' => $fromLocation,
            'toLocationType' => $toLocationType,
            'fromLocationType' => $fromLocationType,
            'toCity' => $toCity,
            'fromCity' => $fromCity,
            'orderType' => $orderTypeName,
            'purchaseOrderItems' => $purchaseOrderItemsData,
            'approvedBy' => $this->getTransactionUserName($purchaseOrderId, Statuses::APPROVED->value),
            'openedBy' => $this->getTransactionUserName($purchaseOrderId, Statuses::OPENED->value),
            'cancelledBy' => $this->getTransactionUserName($purchaseOrderId, Statuses::CANCELLED->value),
            'closedBy' => $this->getTransactionUserName($purchaseOrderId, Statuses::CLOSED->value),
            'productVariant' => $productVariant,
        ])->render();
    }

    public function getTransactionUserName(int $purchaseOrderId, int $status): ?string
    {
        $purchaseOrderTransactionQueries = resolve(PurchaseOrderTransactionQueries::class);

        $purchaseOrderTransaction = $purchaseOrderTransactionQueries->getByPurchaseOrderIdAndNewStatus(
            $purchaseOrderId,
            $status
        );

        if (! $purchaseOrderTransaction) {
            return 'N/A';
        }

        $user = $purchaseOrderTransaction->user;
        if (! $user) {
            return $purchaseOrderTransaction->external_username;
        }

        $employee = $user->employee;
        if ($employee) {
            return $employee->getFullName();
        }

        return $purchaseOrderTransaction->external_username;
    }

    public function getToAndFromCompany(PurchaseOrder $purchaseOrder): array
    {
        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_REQUEST->value
            && $purchaseOrder->created_by_company_id
        ) {
            return [$purchaseOrder->externalCompany, $purchaseOrder->company];
        }

        if (
            $purchaseOrder->order_type === OrderTypes::TRANSFER_REQUEST->value
            && $purchaseOrder->created_by_company_id
        ) {
            return [$purchaseOrder->externalCompany, $purchaseOrder->company];
        }

        if ($purchaseOrder->order_type === OrderTypes::SALES_ORDER->value) {
            return [$purchaseOrder->externalCompany, $purchaseOrder->company];
        }

        if ($purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value) {
            return [$purchaseOrder->externalCompany, $purchaseOrder->company];
        }

        return [$purchaseOrder->company, $purchaseOrder->externalCompany];
    }

    public function getToAndFromLocation(PurchaseOrder $purchaseOrder): array
    {
        /** @var ExternalLocation $externalLocation */
        $externalLocation = $purchaseOrder->externalLocation;

        /** @var Model $location */
        $location = $purchaseOrder->location;

        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_REQUEST->value
            && $purchaseOrder->created_by_company_id
        ) {
            return [$externalLocation, $location];
        }

        if (
            $purchaseOrder->order_type === OrderTypes::TRANSFER_REQUEST->value
            && $purchaseOrder->created_by_company_id
        ) {
            return [$externalLocation, $location];
        }

        if ($purchaseOrder->order_type === OrderTypes::SALES_ORDER->value) {
            return [$externalLocation, $location];
        }

        if ($purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value) {
            return [$externalLocation, $location];
        }

        return [$location, $externalLocation];
    }

    public function getToAndFromLocationType(PurchaseOrder $purchaseOrder): array
    {
        /** @var Location $location */
        $location = $purchaseOrder->location;

        /** @var ExternalLocation $externalLocation */
        $externalLocation = $purchaseOrder->externalLocation;

        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_REQUEST->value
            && $purchaseOrder->created_by_company_id
        ) {
            return [
                $externalLocation->type_id ? LocationTypes::getFormattedCaseName($externalLocation->type_id) : null,
                LocationTypes::getFormattedCaseName($location->type_id),
            ];
        }

        if (
            $purchaseOrder->order_type === OrderTypes::TRANSFER_REQUEST->value
            && $purchaseOrder->created_by_company_id
        ) {
            return [
                $externalLocation->type_id ? LocationTypes::getFormattedCaseName($externalLocation->type_id) : null,
                LocationTypes::getFormattedCaseName($location->type_id),
            ];
        }

        if ($purchaseOrder->order_type === OrderTypes::SALES_ORDER->value) {
            return [
                $externalLocation->type_id ? LocationTypes::getFormattedCaseName($externalLocation->type_id) : null,
                LocationTypes::getFormattedCaseName($location->type_id),
            ];
        }

        if ($purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value) {
            return [
                $externalLocation->type_id ? LocationTypes::getFormattedCaseName($externalLocation->type_id) : null,
                LocationTypes::getFormattedCaseName($location->type_id),
            ];
        }

        return [
            LocationTypes::getFormattedCaseName($location->type_id),
            $externalLocation->type_id ? LocationTypes::getFormattedCaseName($externalLocation->type_id) : null,
        ];
    }

    public function getFormattedData(Collection $purchaseOrderItems): array
    {
        $purchaseOrderItemsData = [];

        if (config('app.product_variant')) {
            foreach ($purchaseOrderItems->groupBy(
                'product.masterProduct.article_number'
            ) as $purchaseOrderArticleWiseItems) {
                $firstPurchaseOrderItem = $purchaseOrderArticleWiseItems->first();
                $product = $firstPurchaseOrderItem->product;
                $purchaseOrderItemsData[$product->masterProduct?->article_number]['article_number'] = $product->masterProduct?->article_number;
                $purchaseOrderItemsData[$product->masterProduct?->article_number]['product_name'] = $product->name;
                $purchaseOrderItemsData[$product->masterProduct?->article_number]['products'] = [];

                foreach ($purchaseOrderArticleWiseItems as $purchaseOrderItem) {
                    $purchaseOrderItemsData[$product->masterProduct?->article_number]['products'][] = $this->preparedItem(
                        $purchaseOrderItem
                    );
                }
            }

            return $purchaseOrderItemsData;
        }

        foreach ($purchaseOrderItems->groupBy('product.article_number') as $purchaseOrderArticleWiseItems) {
            $firstPurchaseOrderItem = $purchaseOrderArticleWiseItems->first();
            $product = $firstPurchaseOrderItem->product;
            $purchaseOrderItemsData[$product->article_number]['article_number'] = $product->article_number;
            $purchaseOrderItemsData[$product->article_number]['product_name'] = $product->name;
            $purchaseOrderItemsData[$product->article_number]['products'] = [];

            foreach ($purchaseOrderArticleWiseItems as $purchaseOrderItem) {
                $purchaseOrderItemsData[$product->article_number]['products'][] = $this->preparedItem(
                    $purchaseOrderItem
                );
            }
        }

        return $purchaseOrderItemsData;
    }

    private function preparedItem(PurchaseOrderItem $purchaseOrderItem): array
    {
        /** @var Product $product */
        $product = $purchaseOrderItem->product;
        $productService = resolve(ProductService::class);

        return [
            'name' => $product->name,
            'article_number' => $product->article_number,
            'color' => config('app.product_variant') ? null : $product->color?->name,
            'size' => config('app.product_variant') ? null : $product->size?->name,
            'attributes' => $productService->getAttributesArray($product),
            'upc' => $product->upc,
            'quantity' => $purchaseOrderItem->quantity,
            'transferred_quantity' => $purchaseOrderItem->transferred_quantity ?? 0,
        ];
    }
}
