<?php

declare(strict_types=1);

namespace App\Domains\PurchasePlan\Services;

use App\Domains\PurchaseOrder\DataObjects\PurchaseOrderData;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchasePlan\DataObjects\PurchasePlanData;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Support\Collection;

class PurchasePlanCheckRequestService
{
    public function checkRequestDetails(Collection $products, PurchasePlanData $purchasePlanData): void
    {
        $transferItems = $purchasePlanData->transfer_items;

        foreach ($transferItems as $transferItem) {
            /** @var Product|null $product */
            $product = $products->firstWhere('id', $transferItem['product_id']);

            if (! $product instanceof Product) {
                throw new RedirectBackWithErrorException('Selected Product is not available.');
            }

            $this->checkUnitOfMeasure($product, $transferItem);
        }
    }

    public function getProducts(int $companyId, PurchasePlanData $purchasePlanData): Collection
    {
        $transferItems = $purchasePlanData->transfer_items;
        $productIds = collect($transferItems)->pluck('product_id')->unique()->filter()->toArray();

        $purchasePlanService = resolve(PurchasePlanService::class);

        return $purchasePlanService->getProducts($productIds, $companyId);
    }

    public function checkUnitOfMeasure(Product $product, array $transferItem): void
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
    }

    public function checkTransferType(PurchaseOrderData $purchasePlanData, int $locationId): void
    {
        if ($locationId === $purchasePlanData->location_id) {
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
}
