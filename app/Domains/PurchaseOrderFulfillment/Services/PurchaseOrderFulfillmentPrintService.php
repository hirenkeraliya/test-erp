<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillment\Services;

use App\Domains\Product\Services\ProductService;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrderFulfillment\DataPreparer\PurchaseOrderLocationDataPreparer;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\PurchaseOrderFulfillmentTransaction\PurchaseOrderFulfillmentTransactionQueries;
use App\Models\Company;
use App\Models\ExternalCompany;
use App\Models\ExternalLocation;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;
use App\Models\PurchaseOrderFulfillmentItem;
use Illuminate\Support\Collection;
use Picqer\Barcode\BarcodeGeneratorPNG;

class PurchaseOrderFulfillmentPrintService
{
    public function print(int $purchaseOrderFulfillmentId, int $companyId, ?int $locationId = null): string
    {
        $productVariant = config('app.product_variant');
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);

        $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->getByIdAndLocationForPrint(
            $purchaseOrderFulfillmentId,
            $companyId,
            $locationId,
        );

        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->getItems();
        $purchaseOrderFulfillmentItemsData = $this->getFormattedData($purchaseOrderFulfillmentItems);

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        $purchaseOrderLocationDataPreparer = resolve(PurchaseOrderLocationDataPreparer::class);

        return view('prints.purchase_order_fulfillment', [
            'purchaseOrderFulfillment' => $purchaseOrderFulfillment,
            'totalTransferQty' => collect($purchaseOrderFulfillmentItemsData)->sum('transfer_quantity'),
            'totalReceivedQty' => collect($purchaseOrderFulfillmentItemsData)->sum('received_quantity'),
            'purchaseOrderFulfillmentItems' => $purchaseOrderFulfillmentItemsData,
            'toCompany' => $this->getToCompany($purchaseOrder),
            'fromCompany' => $this->getFromCompany($purchaseOrder),
            'toLocation' => $purchaseOrderLocationDataPreparer->getToLocation($purchaseOrder),
            'fromLocation' => $purchaseOrderLocationDataPreparer->getFromLocation($purchaseOrder),
            'discrepancyBy' => $this->getTransactionUserName(
                $purchaseOrderFulfillmentId,
                FulfillmentStatuses::DISCREPANCY->value
            ),
            'shippedBy' => $this->getTransactionUserName(
                $purchaseOrderFulfillmentId,
                FulfillmentStatuses::SHIPPED->value
            ),
            'receivedBy' => $this->getTransactionUserName(
                $purchaseOrderFulfillmentId,
                FulfillmentStatuses::RECEIVED->value
            ),
            'closedBy' => $this->getTransactionUserName(
                $purchaseOrderFulfillmentId,
                FulfillmentStatuses::CLOSED->value
            ),
            'requestTitle' => $this->getRequestTitle($purchaseOrder),
            'orderTitle' => $this->getOrderTitle($purchaseOrder),
            'orderNo' => $this->getOrderNumber($purchaseOrder),
            'requestNo' => $this->getRequestNumber($purchaseOrder),
            'deliveryOrderBarcode' => $this->getBarcode($purchaseOrderFulfillment),
            'productVariant' => $productVariant,
        ])->render();
    }

    public function getTransactionUserName(int $purchaseOrderFulfillmentId, int $status): ?string
    {
        $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);

        $purchaseOrderFulfillmentTransaction = $purchaseOrderFulfillmentTransactionQueries->getByPurchaseOrderFulfillmentIdAndNewStatus(
            $purchaseOrderFulfillmentId,
            $status
        );

        if (! $purchaseOrderFulfillmentTransaction) {
            return 'N/A';
        }

        $user = $purchaseOrderFulfillmentTransaction->user;
        if (! $user) {
            return $purchaseOrderFulfillmentTransaction->external_username;
        }

        $employee = $user->employee;
        if ($employee) {
            return $employee->getFullName();
        }

        return $purchaseOrderFulfillmentTransaction->external_username;
    }

    public function getBarcode(PurchaseOrderFulfillment $purchaseOrderFulfillment): ?string
    {
        if (! $purchaseOrderFulfillment->delivery_order_number) {
            return null;
        }

        $barcodeGeneratorPNG = resolve(BarcodeGeneratorPNG::class);

        return base64_encode(
            $barcodeGeneratorPNG->getBarcode(
                $purchaseOrderFulfillment->delivery_order_number,
                $barcodeGeneratorPNG::TYPE_CODE_128,
                2,
                35
            )
        );
    }

    public function printSticker(
        int $purchaseOrderFulfillmentId,
        int $companyId,
        array $itemIds,
        ?int $locationId = null,
    ): string {
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillment = $purchaseOrderFulfillmentQueries->getByIdAndLocationForPrint(
            $purchaseOrderFulfillmentId,
            $companyId,
            $locationId,
        );
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $purchaseOrderFulfillmentItems = $purchaseOrderFulfillmentItemQueries->printStickerTransferItems(
            $itemIds,
            $purchaseOrderFulfillmentId
        );

        $purchaseOrderFulfillmentItemsData = $this->getFormattedPrintStickerData($purchaseOrderFulfillmentItems);

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderFulfillment->purchaseOrder;

        $purchaseOrderLocationDataPreparer = resolve(PurchaseOrderLocationDataPreparer::class);

        return view('prints.purchase_order_fulfillment_box_sticker', [
            'purchaseOrderFulfillment' => $purchaseOrderFulfillment,
            'totalTransferQty' => $purchaseOrderFulfillmentItemsData->sum('transfer_quantity'),
            'purchaseOrderFulfillmentItems' => $purchaseOrderFulfillmentItemsData,
            'toCompany' => $this->getToCompany($purchaseOrder),
            'fromCompany' => $this->getFromCompany($purchaseOrder),
            'toLocation' => $purchaseOrderLocationDataPreparer->getToLocation($purchaseOrder),
            'fromLocation' => $purchaseOrderLocationDataPreparer->getFromLocation($purchaseOrder),
        ])->render();
    }

    public function getFromCompany(PurchaseOrder $purchaseOrder): ExternalCompany|Company|null
    {
        if ($purchaseOrder->order_type === OrderTypes::SALES_ORDER->value) {
            return $purchaseOrder->company;
        }

        return $purchaseOrder->externalCompany;
    }

    public function getToCompany(PurchaseOrder $purchaseOrder): ExternalCompany|Company|null
    {
        if ($purchaseOrder->order_type === OrderTypes::SALES_ORDER->value) {
            return $purchaseOrder->externalCompany;
        }

        return $purchaseOrder->company;
    }

    public function getFromLocation(PurchaseOrder $purchaseOrder): ExternalLocation|Location|null
    {
        if ($purchaseOrder->order_type === OrderTypes::SALES_ORDER->value) {
            return $purchaseOrder->location;
        }

        return $purchaseOrder->externalLocation;
    }

    public function getToLocation(PurchaseOrder $purchaseOrder): ExternalLocation|Location|null
    {
        if ($purchaseOrder->order_type === OrderTypes::SALES_ORDER->value) {
            return $purchaseOrder->externalLocation;
        }

        return $purchaseOrder->location;
    }

    public function getFormattedData(Collection $purchaseOrderFulfillmentItems): array
    {
        $purchaseOrderFulfillmentItemsData = [];
        if (config('app.product_variant')) {
            $groupByArticleNumberPurchaseOrderFulfillmentItems = $purchaseOrderFulfillmentItems->groupBy(
                'product.masterProduct.article_number'
            );
        } else {
            $groupByArticleNumberPurchaseOrderFulfillmentItems = $purchaseOrderFulfillmentItems->groupBy(
                'product.article_number'
            );
        }

        foreach ($groupByArticleNumberPurchaseOrderFulfillmentItems as $purchaseOrderFulfillmentArticleWiseItems) {
            $firstPurchaseOrderFulfillmentItem = $purchaseOrderFulfillmentArticleWiseItems->first();
            $product = $firstPurchaseOrderFulfillmentItem->product;

            if (config('app.product_variant')) {
                $purchaseOrderFulfillmentItemsData[$product->masterProduct?->article_number]['products'] = [];
                $purchaseOrderFulfillmentItemsData[$product->masterProduct?->article_number]['article_number'] = $product->masterProduct?->article_number;

                $purchaseOrderFulfillmentItemsData[$product->masterProduct?->article_number]['product_name'] = $product->name;
                $purchaseOrderFulfillmentItemsData[$product->masterProduct?->article_number]['transfer_quantity'] = $purchaseOrderFulfillmentArticleWiseItems->sum(
                    'transfer_quantity'
                );
                $purchaseOrderFulfillmentItemsData[$product->masterProduct?->article_number]['received_quantity'] = $purchaseOrderFulfillmentArticleWiseItems->sum(
                    'received_quantity'
                );
                $purchaseOrderFulfillmentItemsData[$product->masterProduct?->article_number]['package_quantity'] = $purchaseOrderFulfillmentArticleWiseItems->sum(
                    'package_quantity'
                );
                $purchaseOrderFulfillmentItemsData[$product->masterProduct?->article_number]['package_total_quantity'] = $purchaseOrderFulfillmentArticleWiseItems->sum(
                    'package_total_quantity'
                );

                foreach ($purchaseOrderFulfillmentArticleWiseItems as $purchaseOrderFulfillmentItem) {
                    $purchaseOrderFulfillmentItemsData[$product->masterProduct?->article_number]['products'][] = $this->preparedItem(
                        $purchaseOrderFulfillmentItem
                    );
                }
            } else {
                $purchaseOrderFulfillmentItemsData[$product->article_number]['products'] = [];
                $purchaseOrderFulfillmentItemsData[$product->article_number]['article_number'] = $product->article_number;
                $purchaseOrderFulfillmentItemsData[$product->article_number]['product_name'] = $product->name;
                $purchaseOrderFulfillmentItemsData[$product->article_number]['transfer_quantity'] = $purchaseOrderFulfillmentArticleWiseItems->sum(
                    'transfer_quantity'
                );
                $purchaseOrderFulfillmentItemsData[$product->article_number]['received_quantity'] = $purchaseOrderFulfillmentArticleWiseItems->sum(
                    'received_quantity'
                );
                $purchaseOrderFulfillmentItemsData[$product->article_number]['package_quantity'] = $purchaseOrderFulfillmentArticleWiseItems->sum(
                    'package_quantity'
                );
                $purchaseOrderFulfillmentItemsData[$product->article_number]['package_total_quantity'] = $purchaseOrderFulfillmentArticleWiseItems->sum(
                    'package_total_quantity'
                );

                foreach ($purchaseOrderFulfillmentArticleWiseItems as $purchaseOrderFulfillmentItem) {
                    $purchaseOrderFulfillmentItemsData[$product->article_number]['products'][] = $this->preparedItem(
                        $purchaseOrderFulfillmentItem
                    );
                }
            }
        }

        return $purchaseOrderFulfillmentItemsData;
    }

    public function getFormattedPrintStickerData(Collection $purchaseOrderFulfillmentItems): Collection
    {
        $purchaseOrderFulfillmentItemsData = collect([]);

        foreach ($purchaseOrderFulfillmentItems as $purchaseOrderFulfillmentItem) {
            $purchaseOrderFulfillmentItemsData->push($this->preparedItemForPrintSticker($purchaseOrderFulfillmentItem));
        }

        return $purchaseOrderFulfillmentItemsData;
    }

    public function getRequestTitle(PurchaseOrder $purchaseOrder): string
    {
        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_REQUEST->value
        ) {
            return 'Purchase Request No';
        }

        if (
            $purchaseOrder->order_type === OrderTypes::TRANSFER_REQUEST->value
        ) {
            return 'Transfer Request No';
        }

        if ($purchaseOrder->order_type === OrderTypes::SALES_ORDER->value) {
            return 'Transfer Request No';
        }

        return 'Purchase Request No';
    }

    public function getOrderTitle(PurchaseOrder $purchaseOrder): string
    {
        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_REQUEST->value
        ) {
            return 'Purchase Order No';
        }

        if (
            $purchaseOrder->order_type === OrderTypes::TRANSFER_REQUEST->value
        ) {
            return 'Sales Order No';
        }

        if ($purchaseOrder->order_type === OrderTypes::SALES_ORDER->value) {
            return 'Sales Order No';
        }

        return 'Purchase Order No';
    }

    public function getRequestNumber(PurchaseOrder $purchaseOrder): ?string
    {
        $parentOrderNumber = $purchaseOrder->parentPurchaseOrder?->order_number;

        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_REQUEST->value
            || $purchaseOrder->order_type === OrderTypes::TRANSFER_REQUEST->value
        ) {
            return $purchaseOrder->order_number;
        }

        return $parentOrderNumber;
    }

    public function getOrderNumber(PurchaseOrder $purchaseOrder): ?string
    {
        $parentOrderNumber = $purchaseOrder->parentPurchaseOrder?->order_number;

        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_REQUEST->value
            || $purchaseOrder->order_type === OrderTypes::TRANSFER_REQUEST->value
        ) {
            return $parentOrderNumber;
        }

        return $purchaseOrder->order_number;
    }

    private function preparedItem(PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem): array
    {
        /** @var Product $product */
        $product = $purchaseOrderFulfillmentItem->product;

        $packageType = $purchaseOrderFulfillmentItem->packageType;

        $productService = resolve(ProductService::class);

        return [
            'name' => $product->name,
            'article_number' => $product->article_number,
            'color' => config('app.product_variant') ? null : $product->color?->name,
            'size' => config('app.product_variant') ? null : $product->size?->name,
            'attributes' => $productService->getAttributesArray($product),
            'upc' => $product->upc,
            'package_type' => $packageType ? $packageType->name : null,
            'transfer_quantity' => $purchaseOrderFulfillmentItem->transfer_quantity,
            'received_quantity' => $purchaseOrderFulfillmentItem->received_quantity,
            'remarks' => $purchaseOrderFulfillmentItem->remarks,
            'package_quantity' => $purchaseOrderFulfillmentItem->package_quantity,
            'package_total_quantity' => $purchaseOrderFulfillmentItem->package_total_quantity,
        ];
    }

    private function preparedItemForPrintSticker(PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem): array
    {
        /** @var Product $product */
        $product = $purchaseOrderFulfillmentItem->product;

        $packageType = $purchaseOrderFulfillmentItem->packageType;

        return [
            'name' => $product->name,
            'package_type' => $packageType ? $packageType->name : null,
            'transfer_quantity' => $purchaseOrderFulfillmentItem->transfer_quantity,
            'received_quantity' => $purchaseOrderFulfillmentItem->received_quantity,
            'package_quantity' => $purchaseOrderFulfillmentItem->package_quantity,
            'package_total_quantity' => $purchaseOrderFulfillmentItem->package_total_quantity,
        ];
    }
}
