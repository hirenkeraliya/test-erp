<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillment;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Batch\BatchQueries;
use App\Domains\City\CityQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Domains\ExternalLocation\ExternalLocationQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\PartiallyReceiveFulfillment\PartiallyReceiveFulfillmentQueries;
use App\Domains\PartiallyReceiveFulfillmentItem\PartiallyReceiveFulfillmentItemQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\PurchaseOrderFulfillmentItemBatch\PurchaseOrderFulfillmentItemBatchQueries;
use App\Domains\PurchaseOrderFulfillmentItemUnit\PurchaseOrderFulfillmentItemUnitQueries;
use App\Domains\PurchaseOrderFulfillmentTransaction\PurchaseOrderFulfillmentTransactionQueries;
use App\Domains\PurchaseOrderInvoice\PurchaseOrderInvoiceQueries;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\PurchaseOrderFulfillment;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseOrderFulfillmentQueries
{
    public function listQuery(array $filterData, int $purchaseOrderId, int $companyId): LengthAwarePaginator
    {
        return $this->getPurchaseOrderFulfillments($filterData, $purchaseOrderId, $companyId)->paginate(
            $filterData['per_page']
        );
    }

    public function deliveryOrderListQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getDeliveryOrders($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function listQueryForInternalApplication(
        array $filterData,
        int $purchaseOrderId,
        int $companyId
    ): LengthAwarePaginator {
        return $this->getPurchaseOrderFulfillmentsForInternalApplication(
            $filterData,
            $purchaseOrderId,
            $companyId
        )->paginate($filterData['per_page']);
    }

    public function getLocationColumnName(): string
    {
        return 'id,name,code';
    }

    public function getIdAndOrderIdColumn(): string
    {
        return 'id,purchase_order_id,delivery_order_number';
    }

    public function getById(int $purchaseOrderFulfillmentId): PurchaseOrderFulfillment
    {
        return PurchaseOrderFulfillment::query()
            ->select('id', 'purchase_order_id', 'status', 'purchase_order_invoice_id', 'created_by_company_id')
            ->findOrFail($purchaseOrderFulfillmentId);
    }

    public function getByIdAndCompanyId(int $purchaseOrderFulfillmentId, int $companyId): PurchaseOrderFulfillment
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        return PurchaseOrderFulfillment::query()
            ->select('id', 'purchase_order_id', 'status', 'created_by_company_id')
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
            ->findOrFail($purchaseOrderFulfillmentId);
    }

    public function getByIdLocationAndCompanyId(
        int $purchaseOrderFulfillmentId,
        int $companyId,
        int $locationId,
    ): PurchaseOrderFulfillment {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        return PurchaseOrderFulfillment::query()
            ->select('id', 'purchase_order_id', 'status', 'created_by_company_id')
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompanyAndLocation($companyId, $locationId))
            ->findOrFail($purchaseOrderFulfillmentId);
    }

    public function getLocationIdAndLocationType(
        int $purchaseOrderFulfillmentId,
        int $companyId
    ): PurchaseOrderFulfillment {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        return PurchaseOrderFulfillment::query()
            ->select('id', 'purchase_order_id', 'status')
            ->with('purchaseOrder:' . $purchaseOrderQueries->getBasicColumn())
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
            ->findOrFail($purchaseOrderFulfillmentId);
    }

    public function getByIdAndCompanyIdWithItems(
        int $purchaseOrderFulfillmentId,
        int $companyId
    ): PurchaseOrderFulfillment {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        return PurchaseOrderFulfillment::query()
            ->select('id', 'purchase_order_id', 'status')
            ->with([
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
            ])
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
            ->findOrFail($purchaseOrderFulfillmentId);
    }

    public function getByIdLocationAndCompanyIdWithItems(
        int $purchaseOrderFulfillmentId,
        int $companyId,
        int $locationId,
    ): PurchaseOrderFulfillment {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);

        return PurchaseOrderFulfillment::query()
            ->select('id', 'purchase_order_id', 'status')
            ->with([
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
            ])
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompanyAndLocation($companyId, $locationId))
            ->findOrFail($purchaseOrderFulfillmentId);
    }

    public function getByIdWithRelation(int $purchaseOrderFulfillmentId, int $companyId): PurchaseOrderFulfillment
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $purchaseOrderFulfillmentItemBatchQueries = resolve(PurchaseOrderFulfillmentItemBatchQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $purchaseOrderFulfillmentItemUnitQueries = resolve(PurchaseOrderFulfillmentItemUnitQueries::class);
        $partiallyReceiveFulfillmentQueries = resolve(PartiallyReceiveFulfillmentQueries::class);
        $partiallyReceiveFulfillmentItemQueries = resolve(PartiallyReceiveFulfillmentItemQueries::class);

        return PurchaseOrderFulfillment::query()
            ->select(
                'id',
                'purchase_order_id',
                'status',
                'created_by_company_id',
                'external_purchase_order_fulfillment_id',
                'happened_at',
                'notes',
                'delivery_order_number'
            )
            ->with([
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'purchaseOrder.items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                'purchaseOrder.externalCompany.externalConnection:' . $externalConnectionQueries->getBasicColumnNames(),
                'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                'items.purchaseOrderItem:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'items.units:' . $purchaseOrderFulfillmentItemUnitQueries->getBasicColumn(),
                'items.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'items.itemBatches:' . $purchaseOrderFulfillmentItemBatchQueries->getBasicColumnNames(),
                'items.itemBatches.batch:' . $batchQueries->getIdNumberAndExpiryDateColumnNames(),
                'items.media:' . $mediaQueries->getBasicColumnNames(),
                'items.partialReceivedItems:' . $partiallyReceiveFulfillmentItemQueries->getBasicColumnNames(),
                'partiallyReceives:' . $partiallyReceiveFulfillmentQueries->getBasicColumns(),
            ])
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
            ->findOrFail($purchaseOrderFulfillmentId);
    }

    public function getByIdLocationWithRelation(
        int $purchaseOrderFulfillmentId,
        int $companyId,
        int $locationId,
    ): PurchaseOrderFulfillment {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $purchaseOrderFulfillmentItemBatchQueries = resolve(PurchaseOrderFulfillmentItemBatchQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $purchaseOrderFulfillmentItemUnitQueries = resolve(PurchaseOrderFulfillmentItemUnitQueries::class);
        $partiallyReceiveFulfillmentQueries = resolve(PartiallyReceiveFulfillmentQueries::class);
        $partiallyReceiveFulfillmentItemQueries = resolve(PartiallyReceiveFulfillmentItemQueries::class);

        return PurchaseOrderFulfillment::query()
            ->select(
                'id',
                'purchase_order_id',
                'status',
                'created_by_company_id',
                'external_purchase_order_fulfillment_id',
                'happened_at',
                'notes',
                'delivery_order_number'
            )
            ->with([
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'purchaseOrder.items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                'purchaseOrder.externalCompany.externalConnection:' . $externalConnectionQueries->getBasicColumnNames(),
                'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                'items.partialReceivedItems:' . $partiallyReceiveFulfillmentItemQueries->getBasicColumnNames(),
                'items.units:' . $purchaseOrderFulfillmentItemUnitQueries->getBasicColumn(),
                'items.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'items.itemBatches:' . $purchaseOrderFulfillmentItemBatchQueries->getBasicColumnNames(),
                'items.itemBatches.batch:' . $batchQueries->getIdNumberAndExpiryDateColumnNames(),
                'items.media:' . $mediaQueries->getBasicColumnNames(),
                'items.purchaseOrderItem:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'items.purchaseOrderItem.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'partiallyReceives:' . $partiallyReceiveFulfillmentQueries->getBasicColumns(),
            ])
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompanyAndLocation($companyId, $locationId))
            ->findOrFail($purchaseOrderFulfillmentId);
    }

    public function getByIdWithRelationForEdit(
        int $purchaseOrderFulfillmentId,
        int $companyId
    ): PurchaseOrderFulfillment {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $purchaseOrderFulfillmentItemBatchQueries = resolve(PurchaseOrderFulfillmentItemBatchQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $purchaseOrderFulfillmentItemUnitQueries = resolve(PurchaseOrderFulfillmentItemUnitQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $partiallyReceiveFulfillmentItemQueries = resolve(PartiallyReceiveFulfillmentItemQueries::class);
        $partiallyReceiveFulfillmentQueries = resolve(PartiallyReceiveFulfillmentQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrderFulfillment::query()
                ->select(
                    'id',
                    'purchase_order_id',
                    'status',
                    'created_by_company_id',
                    'external_purchase_order_fulfillment_id',
                    'happened_at',
                    'delivery_order_number',
                    'notes'
                )
                ->with([
                    'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                    'purchaseOrder.items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                    'purchaseOrder.items.product:' . $productQueries->getBasicColumnNames(),
                    'purchaseOrder.items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                    'purchaseOrder.items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'purchaseOrder.items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'purchaseOrder.items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                    'items.units:' . $purchaseOrderFulfillmentItemUnitQueries->getBasicColumn(),
                    'items.product:' . $productQueries->getBasicColumnNames(),
                    'items.itemBatches:' . $purchaseOrderFulfillmentItemBatchQueries->getBasicColumnNames(),
                    'items.itemBatches.batch:' . $batchQueries->getBasicColumnNames(),
                    'items.partialReceivedItems:' . $partiallyReceiveFulfillmentItemQueries->getBasicColumnNames(),
                    'partiallyReceives:' . $partiallyReceiveFulfillmentQueries->getBasicColumns(),
                ])
                ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
                ->findOrFail($purchaseOrderFulfillmentId);
        }

        return PurchaseOrderFulfillment::query()
            ->select(
                'id',
                'purchase_order_id',
                'status',
                'created_by_company_id',
                'external_purchase_order_fulfillment_id',
                'happened_at',
                'delivery_order_number',
                'notes'
            )
            ->with([
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'purchaseOrder.items.product:' . $productQueries->getBasicColumnNames(),
                'purchaseOrder.items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'purchaseOrder.items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'purchaseOrder.items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                'items.units:' . $purchaseOrderFulfillmentItemUnitQueries->getBasicColumn(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'items.itemBatches:' . $purchaseOrderFulfillmentItemBatchQueries->getBasicColumnNames(),
                'items.itemBatches.batch:' . $batchQueries->getBasicColumnNames(),
            ])
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
            ->findOrFail($purchaseOrderFulfillmentId);
    }

    public function getByIdForCancelDeliveryOrder(
        int $purchaseOrderFulfillmentId,
        int $companyId
    ): PurchaseOrderFulfillment {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $purchaseOrderFulfillmentItemUnitQueries = resolve(PurchaseOrderFulfillmentItemUnitQueries::class);

        return PurchaseOrderFulfillment::query()
            ->select(
                'id',
                'purchase_order_id',
                'status',
                'created_by_company_id',
                'external_purchase_order_fulfillment_id',
                'happened_at',
                'delivery_order_number',
                'notes'
            )
            ->with([
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                'items.units:' . $purchaseOrderFulfillmentItemUnitQueries->getBasicColumn(),
                'items.purchaseOrderItem:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'items.purchaseOrderItem.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            ])
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
            ->findOrFail($purchaseOrderFulfillmentId);
    }

    public function loadRelations(PurchaseOrderFulfillment $purchaseOrderFulfillment): PurchaseOrderFulfillment
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $purchaseOrderFulfillmentItemBatchQueries = resolve(PurchaseOrderFulfillmentItemBatchQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return $purchaseOrderFulfillment->load([
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'purchaseOrder.items.product:' . $productQueries->getBasicColumnNames(),
                'purchaseOrder.items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'purchaseOrder.items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                'purchaseOrder.items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'purchaseOrder.items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'items.itemBatches:' . $purchaseOrderFulfillmentItemBatchQueries->getBasicColumnNames(),
                'items.itemBatches.batch:' . $batchQueries->getBasicColumnNames(),
            ]);
        }

        return $purchaseOrderFulfillment->load([
            'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
            'purchaseOrder.items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
            'purchaseOrder.items.product:' . $productQueries->getBasicColumnNames(),
            'purchaseOrder.items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            'purchaseOrder.items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
            'purchaseOrder.items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
            'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
            'items.product:' . $productQueries->getBasicColumnNames(),
            'items.itemBatches:' . $purchaseOrderFulfillmentItemBatchQueries->getBasicColumnNames(),
            'items.itemBatches.batch:' . $batchQueries->getBasicColumnNames(),
        ]);
    }

    public function getByIdLocationWithRelationForEdit(
        int $purchaseOrderFulfillmentId,
        int $companyId,
        int $locationId,
    ): PurchaseOrderFulfillment {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $purchaseOrderFulfillmentItemBatchQueries = resolve(PurchaseOrderFulfillmentItemBatchQueries::class);
        $batchQueries = resolve(BatchQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrderFulfillment::query()
                ->select(
                    'id',
                    'purchase_order_id',
                    'status',
                    'created_by_company_id',
                    'external_purchase_order_fulfillment_id',
                    'happened_at',
                    'delivery_order_number',
                    'notes'
                )
                ->with([
                    'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                    'purchaseOrder.items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                    'purchaseOrder.items.product:' . $productQueries->getBasicColumnNames(),
                    'purchaseOrder.items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                    'purchaseOrder.items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'purchaseOrder.items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'purchaseOrder.items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                    'items.product:' . $productQueries->getBasicColumnNames(),
                    'items.itemBatches:' . $purchaseOrderFulfillmentItemBatchQueries->getBasicColumnNames(),
                    'items.itemBatches.batch:' . $batchQueries->getBasicColumnNames(),
                ])
                ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompanyAndLocation($companyId, $locationId))
                ->findOrFail($purchaseOrderFulfillmentId);
        }

        return PurchaseOrderFulfillment::query()
            ->select(
                'id',
                'purchase_order_id',
                'status',
                'created_by_company_id',
                'external_purchase_order_fulfillment_id',
                'happened_at',
                'delivery_order_number',
                'notes'
            )
            ->with([
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'purchaseOrder.items.product:' . $productQueries->getBasicColumnNames(),
                'purchaseOrder.items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'purchaseOrder.items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'purchaseOrder.items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'items.itemBatches:' . $purchaseOrderFulfillmentItemBatchQueries->getBasicColumnNames(),
                'items.itemBatches.batch:' . $batchQueries->getBasicColumnNames(),
            ])
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompanyAndLocation($companyId, $locationId))
            ->findOrFail($purchaseOrderFulfillmentId);
    }

    public function updateStatus(PurchaseOrderFulfillment $purchaseOrderFulfillment, int $status): void
    {
        $purchaseOrderFulfillment->status = $status;
        $purchaseOrderFulfillment->save();
    }

    public function updateExternalId(
        int $purchaseOrderFulfillmentId,
        int $externalPurchaseOrderFulfillmentId
    ): void {
        $purchaseOrderFulfillment = $this->getById($purchaseOrderFulfillmentId);
        $purchaseOrderFulfillment->external_purchase_order_fulfillment_id = $externalPurchaseOrderFulfillmentId;
        $purchaseOrderFulfillment->save();
    }

    public function addNew(array $purchaseOrderFulfillmentData): PurchaseOrderFulfillment
    {
        return PurchaseOrderFulfillment::create($purchaseOrderFulfillmentData);
    }

    public function filterById(int $id): Closure
    {
        return fn ($query) => $query->where('id', $id);
    }

    public function filterByPurchaseOrderIdAndStatusNotDraft(int $purchaseOrderId): Closure
    {
        return fn ($query) => $query->where('purchase_order_id', $purchaseOrderId)
            ->whereNot('status', FulfillmentStatuses::DRAFT->value);
    }

    public function update(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        array $purchaseOrderFulfillmentData
    ): void {
        $purchaseOrderFulfillment->update($purchaseOrderFulfillmentData);
    }

    public function getBasicColumns(): string
    {
        return 'id,purchase_order_id,created_by_company_id,external_purchase_order_fulfillment_id,happened_at,delivery_order_number,status,purchase_order_invoice_id';
    }

    public function getFulfillmentDetailsByPurchaseOrderId(
        int $purchaseOrderInvoiceId,
        int $purchaseOrderId,
        int $companyId
    ): Collection {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrderFulfillment::query()
                ->select(
                    'id',
                    'purchase_order_id',
                    'status',
                    'created_by_company_id',
                    'external_purchase_order_fulfillment_id',
                    'happened_at',
                    'purchase_order_invoice_id',
                    'delivery_order_number'
                )
                ->with([
                    'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                    'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                    'items.product:' . $productQueries->getBasicColumnNames(),
                    'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                ])
                ->where('purchase_order_id', $purchaseOrderId)
                ->where('status', FulfillmentStatuses::CLOSED->value)
                ->where(function ($query) use ($purchaseOrderInvoiceId): void {
                    $query->where('purchase_order_invoice_id', $purchaseOrderInvoiceId)
                        ->orWhereNull('purchase_order_invoice_id');
                })
                ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
                ->get();
        }

        return PurchaseOrderFulfillment::query()
            ->select(
                'id',
                'purchase_order_id',
                'status',
                'created_by_company_id',
                'external_purchase_order_fulfillment_id',
                'happened_at',
                'purchase_order_invoice_id',
                'delivery_order_number'
            )
             ->with([
                 'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                 'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                 'items.product:' . $productQueries->getBasicColumnNames(),
                 'items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                 'items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
             ])
            ->where('purchase_order_id', $purchaseOrderId)
            ->where('status', FulfillmentStatuses::CLOSED->value)
            ->where(function ($query) use ($purchaseOrderInvoiceId): void {
                $query->where('purchase_order_invoice_id', $purchaseOrderInvoiceId)
                    ->orWhereNull('purchase_order_invoice_id');
            })
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
            ->get();
    }

    public function updateInvoiceId(
        PurchaseOrderFulfillment $purchaseOrderFulfillment,
        int $purchaseOrderInvoiceId
    ): void {
        $purchaseOrderFulfillment->purchase_order_invoice_id = $purchaseOrderInvoiceId;
        $purchaseOrderFulfillment->save();
    }

    public function updateRemoveInvoiceId(PurchaseOrderFulfillment $purchaseOrderFulfillment): void
    {
        $purchaseOrderFulfillment->purchase_order_invoice_id = null;
        $purchaseOrderFulfillment->save();
    }

    public function getPurchaseOrderFulfillmentByInvoiceId(int $purchaseOrderInvoiceId): Collection
    {
        return PurchaseOrderFulfillment::query()
           ->select('id', 'purchase_order_id', 'purchase_order_invoice_id')
           ->where('purchase_order_invoice_id', $purchaseOrderInvoiceId)
           ->get();
    }

    public function getByIds(array $purchaseOrderFulfillmentIds, int $companyId): Collection
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        return PurchaseOrderFulfillment::select(
            'id',
            'purchase_order_id',
            'status',
            'created_by_company_id',
            'external_purchase_order_fulfillment_id',
            'happened_at',
            'purchase_order_invoice_id',
            'delivery_order_number'
        )
            ->whereIntegerInRaw('id', $purchaseOrderFulfillmentIds)
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
            ->get();
    }

    public function getByIdForPrint(int $purchaseOrderFulfillmentId, int $companyId): PurchaseOrderFulfillment
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrderFulfillment::query()
                ->select(
                    'id',
                    'purchase_order_id',
                    'status',
                    'created_by_company_id',
                    'external_purchase_order_fulfillment_id',
                    'happened_at',
                    'delivery_order_number',
                    'notes',
                    'created_at'
                )
                ->with([
                    'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                    'items.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                    'items.product:' . $productQueries->getBasicColumnNames(),
                    'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                    'purchaseOrder.company:' . $companyQueries->getBasicColumnNamesForPurchaseOrderInvoicePrint(),
                    'purchaseOrder.company.media:' . $mediaQueries->getBasicColumnNames(),
                    'purchaseOrder.location',
                    'purchaseOrder.externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
                    'purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                    'purchaseOrder.externalCompany.media:' . $mediaQueries->getBasicColumnNames(),
                ])
                ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
                ->findOrFail($purchaseOrderFulfillmentId);
        }

        return PurchaseOrderFulfillment::query()
            ->select(
                'id',
                'purchase_order_id',
                'status',
                'created_by_company_id',
                'external_purchase_order_fulfillment_id',
                'happened_at',
                'delivery_order_number',
                'notes',
                'created_at'
            )
            ->with([
                'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                'items.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.company:' . $companyQueries->getBasicColumnNamesForPurchaseOrderInvoicePrint(),
                'purchaseOrder.company.media:' . $mediaQueries->getBasicColumnNames(),
                'purchaseOrder.location',
                'purchaseOrder.externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
                'purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                'purchaseOrder.externalCompany.media:' . $mediaQueries->getBasicColumnNames(),
            ])
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
            ->findOrFail($purchaseOrderFulfillmentId);
    }

    public function getByIdAndLocationForPrint(
        int $purchaseOrderFulfillmentId,
        int $companyId,
        ?int $locationId,
    ): PurchaseOrderFulfillment {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $cityQueries = resolve(CityQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrderFulfillment::query()
                ->select(
                    'id',
                    'purchase_order_id',
                    'status',
                    'created_by_company_id',
                    'external_purchase_order_fulfillment_id',
                    'happened_at',
                    'delivery_order_number',
                    'notes',
                    'created_at'
                )
                ->with([
                    'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                    'items.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                    'items.product:' . $productQueries->getBasicColumnNames(),
                    'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                    'purchaseOrder.parentPurchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                    'purchaseOrder.company:' . $companyQueries->getBasicColumnNamesForPurchaseOrderInvoicePrint(),
                    'purchaseOrder.company.media:' . $mediaQueries->getBasicColumnNames(),
                    'purchaseOrder.location',
                    'purchaseOrder.location.city:' . $cityQueries->getBasicColumnNames(),
                    'purchaseOrder.externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
                    'purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                    'purchaseOrder.externalCompany.media:' . $mediaQueries->getBasicColumnNames(),
                ])
                ->when(null !== $locationId, function ($query) use (
                    $purchaseOrderQueries,
                    $companyId,
                    $locationId
                ): void {
                    if (! $locationId) {
                        return;
                    }

                    $query->whereHas(
                        'purchaseOrder',
                        $purchaseOrderQueries->filterByCompanyAndLocation($companyId, $locationId)
                    );
                }, function ($query) use ($purchaseOrderQueries, $companyId): void {
                    $query->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId));
                })
                ->findOrFail($purchaseOrderFulfillmentId);
        }

        return PurchaseOrderFulfillment::query()
            ->select(
                'id',
                'purchase_order_id',
                'status',
                'created_by_company_id',
                'external_purchase_order_fulfillment_id',
                'happened_at',
                'delivery_order_number',
                'notes',
                'created_at'
            )
            ->with([
                'items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                'items.packageType:' . $packageTypeQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.parentPurchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.company:' . $companyQueries->getBasicColumnNamesForPurchaseOrderInvoicePrint(),
                'purchaseOrder.company.media:' . $mediaQueries->getBasicColumnNames(),
                'purchaseOrder.location',
                'purchaseOrder.location.city:' . $cityQueries->getBasicColumnNames(),
                'purchaseOrder.externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
                'purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                'purchaseOrder.externalCompany.media:' . $mediaQueries->getBasicColumnNames(),
            ])
            ->when(null !== $locationId, function ($query) use (
                $purchaseOrderQueries,
                $companyId,
                $locationId
            ): void {
                if (! $locationId) {
                    return;
                }

                $query->whereHas(
                    'purchaseOrder',
                    $purchaseOrderQueries->filterByCompanyAndLocation($companyId, $locationId)
                );
            }, function ($query) use ($purchaseOrderQueries, $companyId): void {
                $query->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId));
            })
            ->findOrFail($purchaseOrderFulfillmentId);
    }

    public function allFulfillmentStatusCount(array $filterData, int $purchaseOrderId, int $companyId): Collection
    {
        return $this->getStatusCount($filterData, $purchaseOrderId, $companyId)->get();
    }

    public function allDeliveryOrdersStatusCount(array $filterData, int $companyId): Collection
    {
        return $this->getDeliveryOrdersStatusCount($filterData, $companyId)->get();
    }

    public function getFulfillmentDetailsByOrderNumber(int $purchaseOrderId, int $companyId): Collection
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        return PurchaseOrderFulfillment::query()
            ->select(
                'id',
                'purchase_order_id',
                'status',
                'created_by_company_id',
                'external_purchase_order_fulfillment_id',
                'happened_at',
                'purchase_order_invoice_id',
                'delivery_order_number'
            )
            ->where('purchase_order_id', $purchaseOrderId)
            ->where('status', FulfillmentStatuses::CLOSED->value)
            ->where(function ($query): void {
                $query->whereNull('purchase_order_invoice_id');
            })
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
            ->get();
    }

    public function loadMorphLocationColumns(): string
    {
        return 'id,name,type_id';
    }

    public function getDashboardStatusCount(array $filterData, int $companyId): Collection
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        return PurchaseOrderFulfillment::query()
            ->select('status', DB::raw('COUNT(id) as count'))
            ->whereHas('purchaseOrder', function ($query) use (
                $purchaseOrderQueries,
                $filterData,
                $companyId
            ): void {
                $query->where($purchaseOrderQueries->filterByCompany($companyId))
                    ->where('order_type', (int) $filterData['order_type']);
                $query->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData): void {
                    $query->where('location_id', (int) $filterData['location_id']);
                });
            })
            ->groupBy('status')
            ->get();
    }

    private function getStatusCount(array $filterData, int $purchaseOrderId, int $companyId): Builder
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        return PurchaseOrderFulfillment::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('purchase_order_id', $purchaseOrderId)
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
            ->groupBy('status')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('delivery_order_number', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when((int) $filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['select_status']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                            ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function getDeliveryOrdersStatusCount(array $filterData, int $companyId): Builder
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        return PurchaseOrderFulfillment::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereHas('purchaseOrder', function ($query) use (
                $filterData,
                $purchaseOrderQueries,
                $companyId
            ): void {
                $query->select('id', 'purchase_order_id')
                    ->where($purchaseOrderQueries->filterByCompany($companyId))
                    ->when(
                        null !== $filterData['location_id'],
                        function ($query) use ($filterData): void {
                            $query->where('location_id', (int) $filterData['location_id']);
                        }
                    )
                    ->when(
                        array_key_exists('select_order_type', $filterData) && (int) $filterData['select_order_type'],
                        function ($query) use ($filterData): void {
                            $query->where('order_type', (int) $filterData['select_order_type']);
                        }
                    );
            })
            ->groupBy('status')
            ->when((int) $filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['select_status']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            });
    }

    private function getPurchaseOrderFulfillments(array $filterData, int $purchaseOrderId, int $companyId): Builder
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);

        return PurchaseOrderFulfillment::query()
            ->select(
                'id',
                'purchase_order_id',
                'happened_at',
                'delivery_order_number',
                'status',
                'created_by_company_id',
            )

            ->where('purchase_order_id', $purchaseOrderId)
            ->with([
                'transactions:' . $purchaseOrderFulfillmentTransactionQueries->getNameColumnNames(),
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.parentPurchaseOrder:' . $purchaseOrderQueries->getIdAndOrderNumberColumn(),
                'purchaseOrder.childPurchaseOrder:' . $purchaseOrderQueries->getIdAndOrderNumberColumn(),
            ])
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('delivery_order_number', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when((int) $filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['select_status']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function getDeliveryOrders(array $filterData, int $companyId): Builder
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);

        return PurchaseOrderFulfillment::query()
            ->select(
                'id',
                'purchase_order_id',
                'happened_at',
                'delivery_order_number',
                'status',
                'created_by_company_id',
            )
            ->with([
                'transactions:' . $purchaseOrderFulfillmentTransactionQueries->getNameColumnNames(),
                'purchaseOrder:'. $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.parentPurchaseOrder:' . $purchaseOrderQueries->getIdAndOrderNumberColumn(),
                'purchaseOrder.childPurchaseOrder:' . $purchaseOrderQueries->getIdAndOrderNumberColumn(),
                'purchaseOrder.location:'. $this->loadMorphLocationColumns(),
                'purchaseOrder.externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
            ])
            ->whereHas('purchaseOrder', function ($query) use (
                $filterData,
                $purchaseOrderQueries,
                $companyId
            ): void {
                $query->select('id', 'purchase_order_id')
                    ->where($purchaseOrderQueries->filterByCompany($companyId))
                    ->when(
                        null !== $filterData['location_id'],
                        function ($query) use ($filterData): void {
                            $query->where('location_id', (int) $filterData['location_id']);
                        }
                    )
                    ->when(
                        (int) $filterData['select_order_type'],
                        function ($query) use ($filterData): void {
                            $query->where('order_type', (int) $filterData['select_order_type']);
                        }
                    )->whereHas('externalCompany', function ($query): void {
                        $query->whereNull('deleted_at');
                    });
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('delivery_order_number', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when((int) $filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['select_status']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    private function getPurchaseOrderFulfillmentsForInternalApplication(
        array $filterData,
        int $purchaseOrderId,
        int $companyId
    ): Builder {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $purchaseOrderFulfillmentTransactionQueries = resolve(PurchaseOrderFulfillmentTransactionQueries::class);

        $isDateRange = $filterData['date_range'] &&
            $filterData['date_range'][0] &&
            $filterData['date_range'][1];

        return PurchaseOrderFulfillment::query()
            ->select(
                'id',
                'purchase_order_id',
                'happened_at',
                'delivery_order_number',
                'status',
                'created_by_company_id',
            )

            ->where('purchase_order_id', $purchaseOrderId)
            ->with('transactions:' . $purchaseOrderFulfillmentTransactionQueries->getNameColumnNames())
            ->whereHas('purchaseOrder', function ($query) use (
                $purchaseOrderQueries,
                $filterData,
                $companyId
            ): void {
                $query->where($purchaseOrderQueries->filterByCompany($companyId))
                    ->where('location_id', $filterData['location_id']);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('delivery_order_number', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when((int) $filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['select_status']);
            })
            ->when($isDateRange, function ($query) use ($filterData): void {
                $query->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function fetchAggregateCountByStatus(): Closure
    {
        return function ($query): void {
            $query->select('purchase_order_id', 'status', DB::raw('count(*) as status_count'))
                ->groupBy('purchase_order_id', 'status');
        };
    }

    public function getByDeliveryOrderForCustomReport(array $filterData, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrderFulfillment::query()
                ->select(
                    'id',
                    'purchase_order_id',
                    'created_by_company_id',
                    'external_purchase_order_fulfillment_id',
                    'purchase_order_invoice_id',
                    'happened_at',
                    'delivery_order_number',
                    'notes',
                    'status',
                )
                ->with([
                    'purchaseOrderInvoice:' . $purchaseOrderInvoiceQueries->getColumnForCustomReport(),
                    'items' => function ($query) use ($filterData, $masterProductQueries): void {
                        $query
                            ->select(
                                'id',
                                'purchase_order_fulfillment_id',
                                'purchase_order_item_id',
                                'product_id',
                                'external_purchase_order_fulfillment_item_id',
                                'transfer_quantity',
                                'received_quantity',
                                'package_type_id',
                                'package_quantity',
                                'package_total_quantity',
                                'remarks'
                            )
                            ->whereHas('product', function ($query): void {
                                $query->isInventoryProduct();
                            })
                            ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                                $query->where('product_id', $filterData['product_id']);
                            })
                            ->when(null !== $filterData['article_number'], function ($query) use (
                                $filterData,
                                $masterProductQueries
                            ): void {
                                $query->whereIn('product_id', function ($query) use (
                                    $filterData,
                                    $masterProductQueries
                                ): void {
                                    $query->select('id')
                                        ->from('products')
                                        ->with('masterProduct:' . $masterProductQueries->getBasicColumnNames())
                                            ->whereHas('masterProduct', function ($query) use ($filterData): void {
                                                $query->where('article_number', $filterData['article_number']);
                                            });
                                });
                            })
                            ->when(
                                array_key_exists(
                                    'product_collection_id',
                                    $filterData
                                ) && null !== $filterData['product_collection_id'],
                                function ($query) use ($filterData): void {
                                    $query->whereIn('product_id', function ($query) use ($filterData): void {
                                        $query->select('product_id')
                                            ->from('product_collection_products')
                                            ->where(
                                                'product_collection_id',
                                                (int) $filterData['product_collection_id']
                                            );
                                    });
                                }
                            );
                    },
                    'items.purchaseOrderItem:' . $purchaseOrderItemQueries->getColumnForPrintInvoice(),
                    'items.product' => function ($query) use ($productQueries, $masterProductQueries): void {
                        $columns = explode(',', $productQueries->getBasicColumnNames());
                        $query->select(...$columns)
                            ->with('masterProduct:' . $masterProductQueries->getBasicColumnNames())
                                ->whereHas('masterProduct', function ($query): void {
                                    $query->where('is_non_inventory', false);
                                });
                    },
                    'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                    'purchaseOrder.company:' . $companyQueries->getBasicColumnNamesForPurchaseOrderInvoicePrint(),
                    'purchaseOrder.location',
                    'purchaseOrder.externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
                    'purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                ])
                ->whereHas('items', function ($query): void {
                    $query->select('id', 'product_id')
                        ->whereHas('product', function ($query): void {
                            $query->select('id')
                                ->isInventoryProduct();
                        });
                })
                ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
                ->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
                ->whereHas('items', function ($query) use ($filterData, $masterProductQueries): void {
                    $query->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                        $query->where('product_id', $filterData['product_id']);
                    })
                    ->when(null !== $filterData['article_number'], function ($query) use (
                        $filterData,
                        $masterProductQueries
                    ): void {
                        $query->whereIn('product_id', function ($query) use ($filterData, $masterProductQueries): void {
                            $query->select('id', 'master_product_id')
                                ->from('products')
                                ->with('masterProduct:' . $masterProductQueries->getBasicColumnNames())
                                ->whereHas('masterProduct', function ($query) use ($filterData): void {
                                    $query->where('article_number', $filterData['article_number']);
                                });
                        });
                    })
                    ->when(
                        array_key_exists(
                            'product_collection_id',
                            $filterData
                        ) && null !== $filterData['product_collection_id'],
                        function ($query) use ($filterData): void {
                            $query->whereIn('product_id', function ($query) use ($filterData): void {
                                $query->select('product_id')
                                    ->from('product_collection_products')
                                    ->where('product_collection_id', (int) $filterData['product_collection_id']);
                            });
                        }
                    );
                })
                ->when((int) $filterData['external_location_id'], function ($query) use ($filterData): void {
                    $query->whereHas('purchaseOrder', function ($query) use ($filterData): void {
                        $query->where('external_location_id', (int) $filterData['external_location_id']);
                    });
                })
                ->when((int) $filterData['external_company_id'], function ($query) use ($filterData): void {
                    $query->whereHas('purchaseOrder', function ($query) use ($filterData): void {
                        $query->where('external_company_id', (int) $filterData['external_company_id']);
                    });
                })
                ->when((int) $filterData['location_id'], function ($query) use ($filterData): void {
                    $query->whereHas('purchaseOrder', function ($query) use ($filterData): void {
                        $query->where('location_id', (int) $filterData['location_id']);
                    });
                })
                ->get();
        }

        return PurchaseOrderFulfillment::query()
            ->select(
                'id',
                'purchase_order_id',
                'created_by_company_id',
                'external_purchase_order_fulfillment_id',
                'purchase_order_invoice_id',
                'happened_at',
                'delivery_order_number',
                'notes',
                'status',
            )
            ->with([
                'purchaseOrderInvoice:' . $purchaseOrderInvoiceQueries->getColumnForCustomReport(),
                'items' => function ($query) use ($filterData): void {
                    $query
                        ->select(
                            'id',
                            'purchase_order_fulfillment_id',
                            'purchase_order_item_id',
                            'product_id',
                            'external_purchase_order_fulfillment_item_id',
                            'transfer_quantity',
                            'received_quantity',
                            'package_type_id',
                            'package_quantity',
                            'package_total_quantity',
                            'remarks'
                        )
                        ->whereHas('product', function ($query): void {
                            $query->isInventoryProduct();
                        })
                        ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                            $query->where('product_id', $filterData['product_id']);
                        })
                        ->when(null !== $filterData['article_number'], function ($query) use ($filterData): void {
                            $query->whereIn('product_id', function ($query) use ($filterData): void {
                                $query->select('id')
                                    ->from('products')
                                    ->where('article_number', $filterData['article_number']);
                            });
                        })
                        ->when(
                            array_key_exists(
                                'product_collection_id',
                                $filterData
                            ) && null !== $filterData['product_collection_id'],
                            function ($query) use ($filterData): void {
                                $query->whereIn('product_id', function ($query) use ($filterData): void {
                                    $query->select('product_id')
                                        ->from('product_collection_products')
                                        ->where('product_collection_id', (int) $filterData['product_collection_id']);
                                });
                            }
                        );
                },
                'items.purchaseOrderItem:' . $purchaseOrderItemQueries->getColumnForPrintInvoice(),
                'items.product' => function ($query) use ($productQueries): void {
                    $columns = explode(',', $productQueries->getBasicColumnNames());
                    $query->select(...$columns)
                        ->isInventoryProduct();
                },
                'items.product.color:' . $colorQueries->getBasicColumnNames(),
                'items.product.size:' . $sizeQueries->getBasicColumnNames(),
                'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
                'purchaseOrder.company:' . $companyQueries->getBasicColumnNamesForPurchaseOrderInvoicePrint(),
                'purchaseOrder.location',
                'purchaseOrder.externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
                'purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumn(),
            ])
            ->whereHas('items', function ($query): void {
                $query->select('id', 'product_id')
                    ->whereHas('product', function ($query): void {
                        $query->select('id')
                            ->isInventoryProduct();
                    });
            })
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
            ->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
            ->whereHas('items', function ($query) use ($filterData): void {
                $query->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                    $query->where('product_id', $filterData['product_id']);
                })
                ->when(null !== $filterData['article_number'], function ($query) use ($filterData): void {
                    $query->whereIn('product_id', function ($query) use ($filterData): void {
                        $query->select('id')
                            ->from('products')
                            ->where('article_number', $filterData['article_number']);
                    });
                })
                ->when(
                    array_key_exists(
                        'product_collection_id',
                        $filterData
                    ) && null !== $filterData['product_collection_id'],
                    function ($query) use ($filterData): void {
                        $query->whereIn('product_id', function ($query) use ($filterData): void {
                            $query->select('product_id')
                                ->from('product_collection_products')
                                ->where('product_collection_id', (int) $filterData['product_collection_id']);
                        });
                    }
                );
            })
            ->when((int) $filterData['external_location_id'], function ($query) use ($filterData): void {
                $query->whereHas('purchaseOrder', function ($query) use ($filterData): void {
                    $query->where('external_location_id', (int) $filterData['external_location_id']);
                });
            })
            ->when((int) $filterData['external_company_id'], function ($query) use ($filterData): void {
                $query->whereHas('purchaseOrder', function ($query) use ($filterData): void {
                    $query->where('external_company_id', (int) $filterData['external_company_id']);
                });
            })
            ->when((int) $filterData['location_id'], function ($query) use ($filterData): void {
                $query->whereHas('purchaseOrder', function ($query) use ($filterData): void {
                    $query->where('location_id', (int) $filterData['location_id']);
                });
            })
            ->get();
    }
}
