<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder\Services;

use App\Domains\Inventory\InventoryQueries;
use App\Domains\PurchaseOrder\DataObjects\PurchaseOrderData;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Support\Collection;

class PurchaseOrderCheckRequestService
{
    public function checkRequestDetails(Collection $products, PurchaseOrderData $purchaseOrderData): void
    {
        $transferItems = $purchaseOrderData->transfer_items;
        $productIds = collect($transferItems)->pluck('product_id')->unique()->filter()->toArray();

        $inventories = null;
        if ($purchaseOrderData->order_type === OrderTypes::TRANSFER_REQUEST->value) {
            $inventoryQueries = resolve(InventoryQueries::class);
            $inventories = $inventoryQueries->getInventoriesByProductIds(
                $purchaseOrderData->location_id,
                $productIds
            );
        }

        $purchaseOrderService = resolve(PurchaseOrderService::class);

        $externalInventories = null;
        if ($purchaseOrderData->order_type === OrderTypes::PURCHASE_REQUEST->value) {
            $externalInventories = $purchaseOrderService->getExternalProductStocks(
                $products->pluck('upc')->toArray(),
                $purchaseOrderData->external_company_id,
                $purchaseOrderData->external_location_id,
            );
        }

        $externalProducts = $purchaseOrderService->getExternalProducts(
            $purchaseOrderData->external_company_id,
            $products->pluck('upc')->toArray()
        );

        foreach ($transferItems as $transferItem) {
            /** @var Product|null $product */
            $product = $products->firstWhere('id', $transferItem['product_id']);

            if (! $product instanceof Product) {
                throw new RedirectBackWithErrorException('Selected Product is not available.');
            }

            $externalProduct = $externalProducts->firstWhere('upc', $product->upc);

            if (! $externalProduct) {
                throw new RedirectBackWithErrorException(
                    'Selected Product: ' . $product->compound_product_name . ' is not available on the selected external company.'
                );
            }

            $this->checkUnitOfMeasure($product, $externalProduct, $transferItem);

            if (config('app.product_variant')) {
                if ($product->masterProduct && $product->masterProduct->has_batch !== $externalProduct['has_batch']) {
                    throw new RedirectBackWithErrorException(
                        'Selected Product: ' . $product->compound_product_name . ' has batch but selected external company product does not has batch.'
                    );
                }
            } elseif ($product->has_batch !== $externalProduct['has_batch']) {
                throw new RedirectBackWithErrorException(
                    'Selected Product: ' . $product->compound_product_name . ' has batch but selected external company product does not has batch.'
                );
            }

            if (config('app.product_variant')) {
                if ($product->masterProduct && $product->masterProduct->is_non_inventory !== $externalProduct['is_non_inventory']) {
                    throw new RedirectBackWithErrorException(
                        'Selected Product: ' . $product->compound_product_name . ' has inventory but selected external company product has does not inventory product.'
                    );
                }
            } elseif ($product->is_non_inventory !== $externalProduct['is_non_inventory']) {
                throw new RedirectBackWithErrorException(
                    'Selected Product: ' . $product->compound_product_name . ' has inventory but selected external company product has does not inventory product.'
                );
            }

            $quantity = (float) $transferItem['quantity'];

            if (array_key_exists('derivative', $transferItem) && $transferItem['derivative']) {
                $quantity /= (float) $transferItem['derivative']['ratio'];
            }

            if ($inventories) {
                $inventory = $inventories->firstWhere('product_id', $transferItem['product_id']);

                if (! $inventory) {
                    throw new RedirectBackWithErrorException(
                        'product (UPC - ' . $product->upc . ') source stock is not available.'
                    );
                }

                if ($quantity > (float) $inventory->stock) {
                    throw new RedirectBackWithErrorException(
                        'Transfer stock (' . $quantity . ') of the product (UPC - ' . $product->upc . ') cannot be more than the current source stock (' . $inventory->stock . ').'
                    );
                }
            }

            if ($externalInventories) {
                $externalInventory = $externalInventories->firstWhere('upc', $product->upc);

                if (! $externalInventory) {
                    throw new RedirectBackWithErrorException(
                        'product (UPC - ' . $product->upc . ') source stock is not available.'
                    );
                }

                if ($quantity > (float) $externalInventory['external_stock']) {
                    throw new RedirectBackWithErrorException(
                        'Transfer stock (' . $quantity . ') of the product (UPC - ' . $product->upc . ') cannot be more than the current source stock (' . $externalInventory['external_stock'] . ').'
                    );
                }
            }
        }
    }

    public function getProducts(int $companyId, PurchaseOrderData $purchaseOrderData): Collection
    {
        $transferItems = $purchaseOrderData->transfer_items;
        $productIds = collect($transferItems)->pluck('product_id')->unique()->filter()->toArray();

        $purchaseOrderService = resolve(PurchaseOrderService::class);

        return $purchaseOrderService->getProducts($productIds, $companyId);
    }

    public function checkUnitOfMeasure(Product $product, array $externalProduct, array $transferItem): void
    {
        if (config('app.product_variant')) {
            if ($product->masterProduct && ! $product->masterProduct->unit_of_measure_id) {
                return;
            }
        } elseif (! $product->unit_of_measure_id) {
            return;
        }

        if (! array_key_exists('unit_of_measure_derivative_id', $transferItem)) {
            return;
        }

        if (! $transferItem['unit_of_measure_derivative_id']) {
            return;
        }

        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        if (config('app.product_variant')) {
            /** @var MasterProduct $masterProduct */
            $masterProduct = $product->masterProduct;

            if (! $masterProduct->unit_of_measure_id) {
                return;
            }

            $unitOfMeasureDerivative = $unitOfMeasureDerivativeQueries->getById(
                $masterProduct->unit_of_measure_id,
                (int) $transferItem['unit_of_measure_derivative_id']
            );
        } else {
            $unitOfMeasureDerivative = $unitOfMeasureDerivativeQueries->getById(
                $product->unit_of_measure_id,
                (int) $transferItem['unit_of_measure_derivative_id']
            );
        }

        if (! is_array($externalProduct)) {
            throw new RedirectBackWithErrorException(
                'Selected Product: ' . $product->compound_product_name . ' has Unit Of Measure but selected external company product has does not Unit Of Measure.'
            );
        }

        if (config('app.product_variant')) {
            if (! array_key_exists('master_product', $externalProduct) && ! array_key_exists(
                'unit_of_measure',
                $externalProduct
            )) {
                throw new RedirectBackWithErrorException(
                    'Selected Product: ' . $product->compound_product_name . ' has Unit Of Measure but selected external company product has does not Unit Of Measure.'
                );
            }
        } elseif (! array_key_exists('unit_of_measure', $externalProduct)) {
            throw new RedirectBackWithErrorException(
                'Selected Product: ' . $product->compound_product_name . ' has Unit Of Measure but selected external company product has does not Unit Of Measure.'
            );
        }

        $externalProductUnitOfMeasure = config(
            'app.product_variant'
        ) ? $externalProduct['master_product']['unit_of_measure'] : $externalProduct['unit_of_measure'];
        if (! is_array($externalProductUnitOfMeasure)) {
            throw new RedirectBackWithErrorException(
                'Selected Product: ' . $product->compound_product_name . ' has Unit Of Measure derivatives but selected external company product has does not Unit Of Measure derivatives.'
            );
        }

        if (! array_key_exists('derivatives', $externalProductUnitOfMeasure)) {
            throw new RedirectBackWithErrorException(
                'Selected Product: ' . $product->compound_product_name . ' has Unit Of Measure derivatives but selected external company product has does not Unit Of Measure derivatives.'
            );
        }

        if (! $externalProductUnitOfMeasure['derivatives']) {
            throw new RedirectBackWithErrorException(
                'Selected Product: ' . $product->compound_product_name . ' has Unit Of Measure derivatives but selected external company product has does not Unit Of Measure derivatives.'
            );
        }

        /** @var array $externalProductUnitOfMeasureDerivatives */
        $externalProductUnitOfMeasureDerivatives = $externalProductUnitOfMeasure['derivatives'];
        $externalDerivatives = collect($externalProductUnitOfMeasureDerivatives);
        $externalDerivative = $externalDerivatives->firstWhere(
            fn ($item): bool => strcasecmp($item['name'], $unitOfMeasureDerivative->name) === 0
        );

        if (! $externalDerivative) {
            throw new RedirectBackWithErrorException(
                'Selected Product: ' . $product->compound_product_name . ' has Unit Of Measure derivative not match with external company product Unit Of Measure derivative.'
            );
        }

        if ((float) $externalDerivative['ratio'] !== (float) $unitOfMeasureDerivative->ratio) {
            throw new RedirectBackWithErrorException(
                'Selected Product: ' . $product->compound_product_name . ' has Unit Of Measure derivative ratio not match with external company product Unit Of Measure derivative ratio.'
            );
        }
    }

    public function checkTransferType(PurchaseOrderData $purchaseOrderData, int $locationId): void
    {
        if ($locationId === $purchaseOrderData->location_id) {
            return;
        }

        throw new RedirectBackWithErrorException('The location has to be the current location');
    }

    public function isPurchaseOrderEdit(PurchaseOrder $purchaseOrder): bool
    {
        if ($purchaseOrder->status === Statuses::DRAFT->value) {
            return true;
        }

        if ($purchaseOrder->status !== Statuses::OPENED->value) {
            return false;
        }

        return ! $purchaseOrder->created_by_company_id;
    }

    public function canPurchaseOrderDeliveryOrder(PurchaseOrder $purchaseOrder): bool
    {
        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value
            && $purchaseOrder->status === Statuses::APPROVED->value
        ) {
            return true;
        }

        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value
            && $purchaseOrder->status === Statuses::CLOSED->value
        ) {
            return true;
        }

        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value
            && $purchaseOrder->status === Statuses::PARTIAL_FULFILLMENT->value
        ) {
            return true;
        }

        if (
            $purchaseOrder->order_type === OrderTypes::PURCHASE_ORDER->value
            && $purchaseOrder->status === Statuses::FULFILLMENT_COMPLETED->value
        ) {
            return true;
        }

        if (
            $purchaseOrder->order_type === OrderTypes::SALES_ORDER->value
            && $purchaseOrder->status === Statuses::APPROVED->value
        ) {
            return true;
        }

        if (
            $purchaseOrder->order_type === OrderTypes::SALES_ORDER->value
            && $purchaseOrder->status === Statuses::CLOSED->value
        ) {
            return true;
        }

        if ($purchaseOrder->order_type !== OrderTypes::SALES_ORDER->value) {
            return false;
        }

        if ($purchaseOrder->status === Statuses::FULFILLMENT_COMPLETED->value) {
            return true;
        }

        return $purchaseOrder->status === Statuses::PARTIAL_FULFILLMENT->value;
    }

    public function checkExternalStockBeforeProceeding(PurchaseOrder $purchaseRequest): void
    {
        /** @var Collection $purchaseRequestItems */
        $purchaseRequestItems = $purchaseRequest->items;

        $purchaseOrderService = resolve(PurchaseOrderService::class);

        $externalInventories = $purchaseOrderService->getExternalProductStocks(
            $purchaseRequestItems->pluck('product.upc')->toArray(),
            $purchaseRequest->external_company_id,
            $purchaseRequest->external_location_id,
        );

        foreach ($purchaseRequestItems as $purchaseRequestItem) {
            /** @var Product $product */
            $product = $purchaseRequestItem->product;

            $derivative = $purchaseRequestItem->derivative;
            $quantity = $purchaseRequestItem->quantity;
            if ($derivative && $derivative->ratio > 0) {
                $quantity /= (float) $derivative->ratio;
            }

            if ($externalInventories->isNotEmpty()) {
                $externalInventory = $externalInventories->firstWhere('upc', $product->upc);

                if (! $externalInventory) {
                    throw new RedirectBackWithErrorException(
                        'product (UPC - ' . $product->upc . ') source stock is not available.'
                    );
                }

                if ((float) $quantity > (float) $externalInventory['external_stock']) {
                    throw new RedirectBackWithErrorException(
                        'Purchase Order stock (' . $quantity . ') of the product (UPC - ' . $product->upc . ') cannot be more than the current source stock (' . $externalInventory['external_stock'] . ').'
                    );
                }
            }
        }
    }
}
