<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderInvoice\Services;

use App\Domains\Currency\CurrencyQueries;
use App\Domains\Product\Services\ProductService;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrderInvoice\Enums\InvoiceStatuses;
use App\Domains\PurchaseOrderInvoice\PurchaseOrderInvoiceQueries;
use App\Models\ExternalLocation;
use App\Models\Model;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\PurchaseOrderInvoice;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Collection;

class PurchaseOrderPrintInvoiceService
{
    public function printInvoice(int $purchaseOrderInvoiceId, int $companyId, ?int $locationId = null): string
    {
        $productVariant = config('app.product_variant');
        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);
        $productService = resolve(ProductService::class);

        $purchaseOrderInvoice = $purchaseOrderInvoiceQueries->getByIdAndLocationForPrint(
            $purchaseOrderInvoiceId,
            $companyId,
            $locationId,
        );
        $purchaseOrderFulfillments = $purchaseOrderInvoice->fulfillments;

        $deliveryOrderNumber = $purchaseOrderFulfillments->pluck('delivery_order_number')->toArray();

        $deliveryNumber = implode(',', $deliveryOrderNumber);

        $fulfillmentItemsData = $this->getFormattedData($purchaseOrderFulfillments, $productService);

        [$toCompany, $fromCompany] = $this->getToAndFromCompany($purchaseOrderInvoice);

        $orderNumber = $purchaseOrderInvoice->purchaseOrder?->order_number;

        [$toLocation, $fromLocation] = $this->getToAndFromLocation($purchaseOrderInvoice);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return view('prints.purchase_order_invoice', [
            'purchaseOrderInvoice' => $purchaseOrderInvoice,
            'toCompany' => $toCompany,
            'fromCompany' => $fromCompany,
            'toLocation' => $toLocation,
            'fromLocation' => $fromLocation,
            'purchaseOrderFulfillmentItems' => $fulfillmentItemsData,
            'total_amount' => collect($fulfillmentItemsData)->sum('amount'),
            'deliveryNumber' => $deliveryNumber,
            'orderNumber' => $orderNumber,
            'status' => $this->getStatus($purchaseOrderInvoice->status),
            'currencySymbol' => $currency->getSymbol(),
            'productVariant' => $productVariant,
        ])->render();
    }

    public function getToAndFromCompany(PurchaseOrderInvoice $purchaseOrderInvoice): array
    {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderInvoice->purchaseOrder;

        if ($purchaseOrderInvoice->created_by_company_id) {
            return [$purchaseOrder->externalCompany, $purchaseOrderInvoice->company];
        }

        return [$purchaseOrderInvoice->company, $purchaseOrder->externalCompany];
    }

    public function getToAndFromLocation(PurchaseOrderInvoice $purchaseOrderInvoice): array
    {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $purchaseOrderInvoice->purchaseOrder;

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

    public function getFormattedData(Collection $purchaseOrderFulfillments, ProductService $productService): array
    {
        $fulfillmentItemsData = [];

        foreach ($purchaseOrderFulfillments as $purchaseOrderFulfillment) {
            $purchaseOrderFulfillmentItems = $purchaseOrderFulfillment->getItems();
            foreach ($purchaseOrderFulfillmentItems as $purchaseOrderFulfillmentItem) {
                $fulfillmentItemsData[] = $this->preparedFulfillmentItem(
                    $purchaseOrderFulfillmentItem,
                    $productService
                );
            }
        }

        return $fulfillmentItemsData;
    }

    public function getStatus(int $status): string
    {
        if ($status === InvoiceStatuses::PAID->value) {
            return InvoiceStatuses::getFormattedCaseName($status);
        }

        return 'UnPaid';
    }

    private function preparedFulfillmentItem(
        PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem,
        ProductService $productService
    ): array {
        /** @var Product $product */
        $product = $purchaseOrderFulfillmentItem->product;

        /** @var PurchaseOrderItem $purchaseOrderItem */
        $purchaseOrderItem = $purchaseOrderFulfillmentItem->purchaseOrderItem;

        return [
            'name' => $product->name,
            'article_number' => config(
                'app.product_variant'
            ) ? $product->masterProduct?->article_number ?? 'N/A' : $product->article_number,
            'upc' => $product->upc,
            'color' => config('app.product_variant') ? null : $product->color?->name ?? 'N/A',
            'size' => config('app.product_variant') ? null : $product->size?->name ?? 'N/A',
            'attributes' => $productService->getAttributesArray($product),
            'quantity' => $purchaseOrderFulfillmentItem->received_quantity,
            'purchase_cost' => $purchaseOrderItem->purchase_cost,
            'amount' => ($purchaseOrderItem->purchase_cost * $purchaseOrderFulfillmentItem->received_quantity),
        ];
    }
}
