<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrder;

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
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\PurchaseOrder\Enums\InterCompanyTransferType;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\PurchaseOrderInvoice\PurchaseOrderInvoiceQueries;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Domains\PurchaseOrderTransaction\PurchaseOrderTransactionQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseOrderQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getPurchaseOrders($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function exportPurchaseOrder(array $filterData, int $companyId): Collection
    {
        return $this->getPurchaseOrders($filterData, $companyId)->get();
    }

    public function getLocationColumnName(): string
    {
        return 'id,name,code,type_id,city_id,phone,fax,address_line_1,address_line_2';
    }

    public function getBasicColumn(): string
    {
        return 'id,external_purchase_order_id,parent_purchase_order_id,external_company_id,external_location_id,created_by_company_id,company_id,location_id,reference_number,remarks,require_date,attention,status,order_type,order_number,external_order_number,created_at';
    }

    public function getIdAndOrderNumberColumn(): string
    {
        return 'id,order_type,order_number,parent_purchase_order_id,external_order_number';
    }

    public function getColumnsForDeliveryOrders(): string
    {
        return 'id,order_type,order_number,location_id,external_location_id';
    }

    public function getExternalLocationId(): string
    {
        return 'id,external_location_id,external_company_id,location_id';
    }

    public function addNew(array $purchaseOrderData): PurchaseOrder
    {
        return PurchaseOrder::create($purchaseOrderData);
    }

    public function getByIdAndCompanyId(int $purchaseOrderId, int $companyId): PurchaseOrder
    {
        return PurchaseOrder::query()
            ->select('id', 'company_id', 'status', 'location_id', 'created_by_company_id', 'order_type')
            ->where('company_id', $companyId)
            ->findOrFail($purchaseOrderId);
    }

    public function getByIdAndCompanyIdWithItems(int $purchaseOrderId, int $companyId): PurchaseOrder
    {
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        return PurchaseOrder::query()
            ->select('id', 'company_id', 'status', 'location_id', 'created_by_company_id', 'order_type')
            ->with('items:' . $purchaseOrderItemQueries->getBasicColumnNames())
            ->where('company_id', $companyId)
            ->findOrFail($purchaseOrderId);
    }

    public function getByIdLocationAndCompanyId(
        int $purchaseOrderId,
        int $companyId,
        int $locationId,
    ): PurchaseOrder {
        return PurchaseOrder::query()
            ->select('id', 'company_id', 'status', 'location_id', 'created_by_company_id', 'order_type')
            ->where('company_id', $companyId)
            ->where('location_id', $locationId)
            ->findOrFail($purchaseOrderId);
    }

    public function getByIdLocationAndCompanyIdWithItems(
        int $purchaseOrderId,
        int $companyId,
        int $locationId,
    ): PurchaseOrder {
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        return PurchaseOrder::query()
            ->select('id', 'company_id', 'status', 'location_id', 'created_by_company_id', 'order_type')
            ->with('items:' . $purchaseOrderItemQueries->getBasicColumnNames())
            ->where('company_id', $companyId)
            ->where('location_id', $locationId)
            ->findOrFail($purchaseOrderId);
    }

    public function getById(int $purchaseOrderId): PurchaseOrder
    {
        return PurchaseOrder::query()
            ->select('id', 'company_id', 'status', 'location_id', 'external_order_number')
            ->findOrFail($purchaseOrderId);
    }

    public function getByIdWithRelationItems(int $purchaseOrderId): PurchaseOrder
    {
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        return PurchaseOrder::query()
            ->select('id', 'company_id', 'status', 'location_id', 'external_order_number')
            ->with(['items:' . $purchaseOrderItemQueries->getBasicColumnNames()])
            ->findOrFail($purchaseOrderId);
    }

    public function getByIdAndCompanyIdWithRelation(int $purchaseOrderId, int $companyId): PurchaseOrder
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrder::query()
                ->select(
                    'id',
                    'external_purchase_order_id',
                    'parent_purchase_order_id',
                    'external_company_id',
                    'external_location_id',
                    'company_id',
                    'location_id',
                    'reference_number',
                    'remarks',
                    'require_date',
                    'attention',
                    'status',
                    'order_type',
                    'order_number',
                    'external_order_number',
                    'created_at',
                    'created_by_company_id'
                )
                ->with([
                    'externalLocation:' . $externalLocationQueries->getBasicColumn(),
                    'externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                    'externalCompany.externalConnection:' . $externalConnectionQueries->getBasicColumnNames(),
                    'items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                    'items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                    'items.product:' . $productQueries->getBasicColumnNames(),
                    'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'fulfillments:' . $purchaseOrderFulfillmentQueries->getBasicColumns(),
                    'fulfillments.items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
                ])
                ->where('company_id', $companyId)
                ->findOrFail($purchaseOrderId);
        }

        return PurchaseOrder::query()
            ->select(
                'id',
                'external_purchase_order_id',
                'parent_purchase_order_id',
                'external_company_id',
                'external_location_id',
                'company_id',
                'location_id',
                'reference_number',
                'remarks',
                'require_date',
                'attention',
                'status',
                'order_type',
                'order_number',
                'external_order_number',
                'created_at',
                'created_by_company_id'
            )
            ->with([
                'externalLocation:' . $externalLocationQueries->getBasicColumn(),
                'externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                'externalCompany.externalConnection:' . $externalConnectionQueries->getBasicColumnNames(),
                'items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'fulfillments:' . $purchaseOrderFulfillmentQueries->getBasicColumns(),
                'fulfillments.items:' . $purchaseOrderFulfillmentItemQueries->getBasicColumn(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($purchaseOrderId);
    }

    public function getByIdLocationAndCompanyIdWithRelation(
        int $purchaseOrderId,
        int $companyId,
        int $locationId,
    ): PurchaseOrder {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $relations = [];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'externalLocation:' . $externalLocationQueries->getBasicColumn(),
                'externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                'externalCompany.externalConnection:' . $externalConnectionQueries->getBasicColumnNames(),
                'items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'externalLocation:' . $externalLocationQueries->getBasicColumn(),
                'externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                'externalCompany.externalConnection:' . $externalConnectionQueries->getBasicColumnNames(),
                'items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getBasicColumnNames(),
            ]);
        }

        return PurchaseOrder::query()
            ->select(
                'id',
                'external_purchase_order_id',
                'parent_purchase_order_id',
                'external_company_id',
                'external_location_id',
                'company_id',
                'location_id',
                'reference_number',
                'remarks',
                'require_date',
                'attention',
                'status',
                'order_type',
                'order_number',
                'external_order_number',
                'created_at',
                'created_by_company_id'
            )
            ->with($relations)
            ->where('company_id', $companyId)
            ->where('location_id', $locationId)
            ->findOrFail($purchaseOrderId);
    }

    public function updateStatus(PurchaseOrder $purchaseOrder, int $status): void
    {
        $purchaseOrder->status = $status;
        $purchaseOrder->save();
    }

    public function updateExternalPurchaseOrderId(
        int $purchaseOrderId,
        int $externalPurchaseOrderId,
        string $externalOrderNumber
    ): void {
        $purchaseOrder = $this->getById($purchaseOrderId);
        $purchaseOrder->external_purchase_order_id = $externalPurchaseOrderId;
        $purchaseOrder->external_order_number = $externalOrderNumber;
        $purchaseOrder->save();
    }

    public function getByIdWithItems(int $purchaseOrderId, int $companyId): PurchaseOrder
    {
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrder::query()
                ->select(
                    'id',
                    'external_purchase_order_id',
                    'parent_purchase_order_id',
                    'external_company_id',
                    'external_location_id',
                    'company_id',
                    'location_id',
                    'reference_number',
                    'remarks',
                    'require_date',
                    'attention',
                    'order_type',
                    'order_number',
                    'external_order_number',
                    'created_by_company_id',
                    'status',
                )
                ->with([
                    'externalCompany:' . $externalCompanyQueries->getBasicColumnNames(),
                    'externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
                    'location:' . $this->getLocationColumnName(),
                    'company:' . $companyQueries->getBasicColumnNamesWithCode(),
                    'items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                    'items.product:' . $productQueries->getBasicColumnNames(),
                    'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'items.product.masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                    'items.product.masterProduct.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                    'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                ])
                ->where('company_id', $companyId)
                ->findOrFail($purchaseOrderId);
        }

        return PurchaseOrder::query()
            ->select(
                'id',
                'external_purchase_order_id',
                'parent_purchase_order_id',
                'external_company_id',
                'external_location_id',
                'company_id',
                'location_id',
                'reference_number',
                'remarks',
                'require_date',
                'attention',
                'order_type',
                'order_number',
                'external_order_number',
                'created_by_company_id',
                'status',
            )
            ->with([
                'externalCompany:' . $externalCompanyQueries->getBasicColumnNames(),
                'externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
                'location:' . $this->getLocationColumnName(),
                'company:' . $companyQueries->getBasicColumnNamesWithCode(),
                'items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'items.product.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'items.product.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($purchaseOrderId);
    }

    public function getBasicColumns(): string
    {
        return 'id,external_purchase_order_id,parent_purchase_order_id,external_company_id,external_location_id,company_id,location_id,reference_number,remarks,require_date,attention,order_type,order_number,external_order_number,created_by_company_id,status';
    }

    public function getByIdWithParentDetails(int $purchaseOrderId, int $companyId): PurchaseOrder
    {
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrder::query()
                ->select(
                    'id',
                    'external_purchase_order_id',
                    'parent_purchase_order_id',
                    'external_company_id',
                    'external_location_id',
                    'company_id',
                    'location_id',
                    'reference_number',
                    'remarks',
                    'require_date',
                    'attention',
                    'order_type',
                    'order_number',
                    'external_order_number',
                    'created_by_company_id',
                    'status',
                )
                ->with([
                    'parentPurchaseOrder:' . $this->getBasicColumns(),
                    'parentPurchaseOrder.items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                    'externalCompany:' . $externalCompanyQueries->getBasicColumnNames(),
                    'externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
                    'location:' . $this->getLocationColumnName(),
                    'company:' . $companyQueries->getBasicColumnNamesWithCode(),
                    'items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                    'items.product:' . $productQueries->getBasicColumnNames(),
                    'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'items.product.masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                    'items.product.masterProduct.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                    'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                ])
                ->where('company_id', $companyId)
                ->findOrFail($purchaseOrderId);
        }

        return PurchaseOrder::query()
            ->select(
                'id',
                'external_purchase_order_id',
                'parent_purchase_order_id',
                'external_company_id',
                'external_location_id',
                'company_id',
                'location_id',
                'reference_number',
                'remarks',
                'require_date',
                'attention',
                'order_type',
                'order_number',
                'external_order_number',
                'created_by_company_id',
                'status',
            )
            ->with([
                'parentPurchaseOrder:' . $this->getBasicColumns(),
                'parentPurchaseOrder.items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'externalCompany:' . $externalCompanyQueries->getBasicColumnNames(),
                'externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
                'location:' . $this->getLocationColumnName(),
                'company:' . $companyQueries->getBasicColumnNamesWithCode(),
                'items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'items.product.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'items.product.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($purchaseOrderId);
    }

    public function getByIdLocationWithItems(
        int $purchaseOrderId,
        int $companyId,
        int $locationId,
    ): PurchaseOrder {
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);

        $relations = [
            'externalCompany:' . $externalCompanyQueries->getBasicColumnNames(),
            'externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
            'location:' . $this->getLocationColumnName(),
            'items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
            'items.product:' . $productQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                'items.product.masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'items.product.masterProduct.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'items.product.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                'items.product.unitOfMeasure.derivatives:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
            ]);
        }

        return PurchaseOrder::query()
            ->select(
                'id',
                'external_purchase_order_id',
                'parent_purchase_order_id',
                'external_company_id',
                'external_location_id',
                'company_id',
                'location_id',
                'reference_number',
                'remarks',
                'require_date',
                'attention',
                'order_type',
                'order_number',
                'created_by_company_id',
                'status',
            )
            ->with($relations)
            ->where('company_id', $companyId)
            ->where('location_id', $locationId)
            ->findOrFail($purchaseOrderId);
    }

    public function update(array $purchaseOrderData, int $companyId, int $purchaseOrderId, Collection $products): void
    {
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $purchaseOrder = $this->getByIdWithItems($purchaseOrderId, $companyId);
        $purchaseOrder->items()->delete();
        $purchaseOrderItems = $purchaseOrderData['transfer_items'];
        unset($purchaseOrderData['transfer_items']);
        $purchaseOrderData['company_id'] = $companyId;

        $purchaseOrder->update($purchaseOrderData);

        foreach ($purchaseOrderItems as $purchaseOrderItem) {
            /** @var Product $product */
            $product = $products->firstWhere('id', $purchaseOrderItem['product_id']);

            $purchaseOrderItemQueries->addNew([
                'purchase_order_id' => $purchaseOrder->id,
                'product_id' => $purchaseOrderItem['product_id'],
                'quantity' => $purchaseOrderItem['quantity'],
                'purchase_cost' => (float) $product->purchase_cost,
                'unit_of_measure_derivative_id' => $purchaseOrderItem['unit_of_measure_derivative_id'] ?? null,
            ]);
        }
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function filterByCompanyAndLocation(int $companyId, int $locationId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId)
            ->where('location_id', $locationId);
    }

    public function existsByIdAndCompanyId(int $purchaseOrderId, int $companyId): bool
    {
        return PurchaseOrder::select('id')
            ->where('id', $purchaseOrderId)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function getByPurchaseOrderFulfillmentId(int $purchaseOrderFulfillmentId, int $companyId): PurchaseOrder
    {
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $productQueries = resolve(ProductQueries::class);

        return PurchaseOrder::query()
            ->select('id', 'company_id', 'location_id', 'external_location_id')
            ->with([
                'items:' . $purchaseOrderItemQueries->getColumnIdAndProductId(),
                'items.product:' . $productQueries->getIdAndUpc(),
            ])
            ->where('company_id', $companyId)
            ->whereHas('fulfillments', $purchaseOrderFulfillmentQueries->filterById($purchaseOrderFulfillmentId))
            ->firstOrFail();
    }

    public function getByPurchaseOrderFulfillmentIdAndLocation(
        int $purchaseOrderFulfillmentId,
        int $companyId,
        int $locationId,
    ): PurchaseOrder {
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderItemQueries = new PurchaseOrderItemQueries();
        $productQueries = new ProductQueries();

        return PurchaseOrder::query()
            ->select('id', 'company_id', 'location_id', 'external_location_id')
            ->where('company_id', $companyId)
            ->where('location_id', $locationId)
            ->with([
                'items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getIdAndUpc(),
            ])
            ->whereHas('fulfillments', $purchaseOrderFulfillmentQueries->filterById($purchaseOrderFulfillmentId))
            ->firstOrFail();
    }

    public function getPurchaseOrderNumber(int $companyId): Collection
    {
        return PurchaseOrder::query()
            ->select('id', 'company_id', 'order_type', 'status', 'order_number')
            ->where('company_id', $companyId)
            ->where('order_type', OrderTypes::SALES_ORDER->value)
            ->whereHas('fulfillments', function ($query): void {
                $query->select('id', 'purchase_order_id', 'purchase_order_invoice_id')
                    ->whereNull('purchase_order_invoice_id')
                    ->where('status', FulfillmentStatuses::CLOSED->value);
            })
            ->get();
    }

    public function getPurchaseOrderNumberByLocation(int $companyId, int $locationId): Collection
    {
        return PurchaseOrder::query()
            ->select('id', 'company_id', 'order_type', 'status', 'order_number')
            ->where('company_id', $companyId)
            ->where('location_id', $locationId)
            ->where('order_type', OrderTypes::SALES_ORDER->value)
            ->whereHas('fulfillments', function ($query): void {
                $query->select('id', 'purchase_order_id', 'purchase_order_invoice_id')
                    ->whereNull('purchase_order_invoice_id')
                    ->where('status', FulfillmentStatuses::CLOSED->value);
            })
            ->get();
    }

    public function getByIdLocationAndStatusAndOrderType(
        int $companyId,
        int $purchaseOrderId,
        int $locationId,
    ): PurchaseOrder {
        return PurchaseOrder::query()
            ->select('id', 'company_id', 'order_type', 'status', 'order_number', 'location_id')
            ->where('company_id', $companyId)
            ->where('location_id', $locationId)
            ->where('id', $purchaseOrderId)
            ->where('order_type', OrderTypes::SALES_ORDER->value)
            ->whereHas('fulfillments', function ($query): void {
                $query->select('id', 'purchase_order_id', 'purchase_order_invoice_id')
                    ->where('status', FulfillmentStatuses::CLOSED->value);
            })
            ->firstOrFail();
    }

    public function getByIdAndStatusAndOrderType(int $companyId, int $purchaseOrderId): PurchaseOrder
    {
        return PurchaseOrder::query()
            ->select('id', 'company_id', 'order_type', 'status', 'order_number', 'location_id')
            ->where('company_id', $companyId)
            ->where('id', $purchaseOrderId)
            ->where('order_type', OrderTypes::SALES_ORDER->value)
            ->whereHas('fulfillments', function ($query): void {
                $query->select('id', 'purchase_order_id', 'purchase_order_invoice_id')
                    ->where('status', FulfillmentStatuses::CLOSED->value);
            })
            ->firstOrFail();
    }

    public function getByIdWithItemsAndFulfillment(int $purchaseOrderId, int $companyId): PurchaseOrder
    {
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        return PurchaseOrder::query()
            ->select('id', 'company_id', 'location_id')
            ->with([
                'items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'fulfillments:' . $purchaseOrderFulfillmentQueries->getBasicColumns(),
            ])
            ->where('company_id', $companyId)
            ->where('id', $purchaseOrderId)
            ->firstOrFail();
    }

    public function loadRelations(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        $purchaseOrder->refresh();

        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);

        return $purchaseOrder->load(
            'items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
            'fulfillments:' . $purchaseOrderFulfillmentQueries->getBasicColumns(),
        );
    }

    public function loadRelationForPurchaseOrderInventory(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        $purchaseOrder->refresh();

        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        resolve(BatchQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return $purchaseOrder->load([
                'items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        }

        return $purchaseOrder->load([
            'items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
            'items.product:' . $productQueries->getBasicColumnNames(),
            'items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            'items.product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
            'items.product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
        ]);
    }

    public function getByIdForPrint(
        int $purchaseOrderId,
        int $companyId,
        ?int $locationId,
        ?string $locationType
    ): PurchaseOrder {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $mediaQueries = resolve(MediaQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $cityQueries = resolve(CityQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrder::query()
                ->select(
                    'id',
                    'external_purchase_order_id',
                    'parent_purchase_order_id',
                    'external_company_id',
                    'external_location_id',
                    'company_id',
                    'location_id',
                    'reference_number',
                    'remarks',
                    'require_date',
                    'attention',
                    'status',
                    'order_type',
                    'order_number',
                    'created_at',
                    'created_by_company_id'
                )
                ->with([
                    'externalLocation:' . $externalLocationQueries->getBasicColumnForPrint(),
                    'externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                    'externalCompany.media:' . $mediaQueries->getBasicColumnNames(),
                    'externalCompany.externalConnection:' . $externalConnectionQueries->getBasicColumnNames(),
                    'items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                    'items.product:' . $productQueries->getBasicColumnNames(),
                    'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'location:' . $this->getLocationColumnName(),
                    'location.city:' . $cityQueries->getBasicColumnNames(),
                    'company:' . $companyQueries->getBasicColumnNamesForPurchaseOrderInvoicePrint(),
                    'company.media:' . $mediaQueries->getBasicColumnNames(),
                ])
                ->where('company_id', $companyId)
                ->when($locationId, function ($query) use ($locationId): void {
                    $query->where('location_id', $locationId);
                })
                ->findOrFail($purchaseOrderId);
        }

        return PurchaseOrder::query()
            ->select(
                'id',
                'external_purchase_order_id',
                'parent_purchase_order_id',
                'external_company_id',
                'external_location_id',
                'company_id',
                'location_id',
                'reference_number',
                'remarks',
                'require_date',
                'attention',
                'status',
                'order_type',
                'order_number',
                'created_at',
                'created_by_company_id'
            )
            ->with([
                'externalLocation:' . $externalLocationQueries->getBasicColumnForPrint(),
                'externalCompany:' . $externalCompanyQueries->getBasicColumn(),
                'externalCompany.media:' . $mediaQueries->getBasicColumnNames(),
                'externalCompany.externalConnection:' . $externalConnectionQueries->getBasicColumnNames(),
                'items:' . $purchaseOrderItemQueries->getBasicColumnNames(),
                'items.product:' . $productQueries->getBasicColumnNames(),
                'items.product.color:' . $colorQueries->getBasicColumnNames(),
                'items.product.size:' . $sizeQueries->getBasicColumnNames(),
                'location:' . $this->getLocationColumnName(),
                'location.city:' . $cityQueries->getBasicColumnNames(),
                'company:' . $companyQueries->getBasicColumnNamesForPurchaseOrderInvoicePrint(),
                'company.media:' . $mediaQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->when($locationId, function ($query) use ($locationId): void {
                $query->where('location_id', $locationId);
            })
            ->findOrFail($purchaseOrderId);
    }

    public function allRequestStatusCount(array $filterData, int $companyId): Collection
    {
        return $this->getStatusCount($filterData, $companyId)->get();
    }

    public function getDashboardStatusCount(array $filterData, int $companyId): Collection
    {
        return PurchaseOrder::query()
            ->select('status', DB::raw('COUNT(id) as count'))
            ->where('company_id', $companyId)
            ->when((int) $filterData['order_type'], function ($query) use ($filterData): void {
                $query->where('order_type', (int) $filterData['order_type']);
            })
            ->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData): void {
                $query->where('location_id', (int) $filterData['location_id']);
            })
            ->groupBy('status')
            ->get();
    }

    private function getStatusCount(array $filterData, int $companyId): Builder
    {
        return PurchaseOrder::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('company_id', $companyId)
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->where(function ($query): void {
                        $query->where('order_type', OrderTypes::PURCHASE_REQUEST->value)
                            ->orWhere('order_type', OrderTypes::TRANSFER_REQUEST->value);
                    })
                        ->whereNot('status', Statuses::CLOSED->value);
                })
                    ->orWhere('order_type', OrderTypes::SALES_ORDER->value)
                    ->orWhere('order_type', OrderTypes::PURCHASE_ORDER->value);
            })
            ->groupBy('status')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['order_number', 'reference_number'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
                });
            })
            ->when((int) $filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', (int) $filterData['select_status']);
            })
            ->when((int) $filterData['order_type'], function ($query) use ($filterData): void {
                $query->where('order_type', (int) $filterData['order_type']);
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when((int) $filterData['location_id'], function ($query) use ($filterData): void {
                $query->where('location_id', (int) $filterData['location_id']);
            })
            ->when((int) $filterData['external_location_id'], function ($query) use ($filterData): void {
                $query->where('external_location_id', (int) $filterData['external_location_id']);
            })
            ->when($filterData['order_type'], function ($query) use ($filterData): void {
                if ((int) $filterData['order_type'] === OrderTypes::PURCHASE_REQUEST->value) {
                    $query->where('order_type', OrderTypes::PURCHASE_REQUEST->value)
                        ->whereIntegerInRaw('status', [
                            Statuses::DRAFT->value,
                            Statuses::OPENED->value,
                            Statuses::APPROVED->value,
                            Statuses::REJECTED->value,
                            Statuses::CANCELLED->value,
                            Statuses::CLOSED->value,
                            Statuses::PARTIAL_FULFILLMENT->value,
                            Statuses::FULFILLMENT_COMPLETED->value,
                        ]);
                }

                if ((int) $filterData['order_type'] === OrderTypes::TRANSFER_REQUEST->value) {
                    $query->where('order_type', OrderTypes::TRANSFER_REQUEST->value)
                        ->whereIntegerInRaw('status', [
                            Statuses::DRAFT->value,
                            Statuses::OPENED->value,
                            Statuses::APPROVED->value,
                            Statuses::REJECTED->value,
                            Statuses::CANCELLED->value,
                            Statuses::CLOSED->value,
                            Statuses::PARTIAL_FULFILLMENT->value,
                            Statuses::FULFILLMENT_COMPLETED->value,
                        ]);
                }
            });
    }

    public function getByDateAndLocationWithStockTransfer(array $filterData, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        return PurchaseOrder::query()
            ->select(
                'id',
                'external_purchase_order_id',
                'parent_purchase_order_id',
                'external_company_id',
                'external_location_id',
                'company_id',
                'location_id',
                'reference_number',
                'order_number',
                'external_order_number',
                'remarks',
                'attention',
                'require_date',
                'status',
                'order_type',
                'created_by_company_id',
                'created_at'
            )
            ->with([
                'items' => function ($query) use ($filterData, $masterProductQueries): void {
                    $query
                        ->select(
                            'id',
                            'purchase_order_id',
                            'external_purchase_order_item_id',
                            'product_id',
                            'quantity',
                            'rejected_quantity',
                            'transferred_quantity',
                            'price_per_unit',
                            'remarks',
                            'unit_of_measure_derivative_id',
                            'purchase_cost'
                        )
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
                                if (config('app.product_variant')) {
                                    $query->select('id')
                                        ->from('products')
                                        ->with('masterProduct:' . $masterProductQueries->getBasicColumnNames())
                                        ->whereHas('masterProduct', function ($query) use ($filterData): void {
                                            $query->where('article_number', $filterData['article_number']);
                                        });
                                } else {
                                    $query->select('id')
                                        ->from('products')
                                        ->where('article_number', $filterData['article_number']);
                                }
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
                'items.product' => function ($query) use ($productQueries): void {
                    $columns = explode(',', $productQueries->getBasicColumnNames());
                    $query->select(...$columns);
                },
                ...config('app.product_variant') ? [
                    'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                ] : [
                    'items.product.color:' . $colorQueries->getBasicColumnNames(),
                    'items.product.size:' . $sizeQueries->getBasicColumnNames(),
                ],
                'location:' . $this->getLocationColumnName(),
                'fulfillments:' . $purchaseOrderFulfillmentQueries->getBasicColumns(),
                'fulfillments.purchaseOrderInvoice:' . $purchaseOrderInvoiceQueries->getColumnForCustomReport(),
                'externalCompany:' . $externalCompanyQueries->getBasicColumnNames(),
                'externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
            ])
            ->whereHas('items', function ($query): void {
                $query->select('id', 'product_id')
                    ->whereHas('product', function ($query): void {
                        $query->select('id');
                    });
            })
            ->where('company_id', $companyId)
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
            ->whereHas('items', function ($query) use ($filterData, $masterProductQueries): void {
                $query->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                    $query->where('product_id', $filterData['product_id']);
                })
                    ->when(null !== $filterData['article_number'], function ($query) use (
                        $filterData,
                        $masterProductQueries
                    ): void {
                        $query->whereIn('product_id', function ($query) use ($filterData, $masterProductQueries): void {
                            if (config('app.product_variant')) {
                                $query->select('id')
                                    ->from('products')
                                    ->with('masterProduct:' . $masterProductQueries->getBasicColumnNames())
                                    ->whereHas('masterProduct', function ($query) use ($filterData): void {
                                        $query->where('article_number', $filterData['article_number']);
                                    });
                            } else {
                                $query->select('id')
                                    ->from('products')
                                    ->where('article_number', $filterData['article_number']);
                            }
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
            ->when(
                (int) $filterData['transfer_type'] === InterCompanyTransferType::SALES_ORDER->value,
                function ($query): void {
                    $query->where('order_type', OrderTypes::SALES_ORDER->value);
                }
            )
            ->when(
                (int) $filterData['transfer_type'] === InterCompanyTransferType::PURCHASE_ORDER->value,
                function ($query): void {
                    $query->where('order_type', OrderTypes::PURCHASE_ORDER->value);
                }
            )
            ->when(
                (int) $filterData['transfer_type'] === InterCompanyTransferType::PURCHASE_REQUEST->value,
                function ($query): void {
                    $query->where('order_type', OrderTypes::PURCHASE_REQUEST->value)
                        ->where('status', '!=', Statuses::CLOSED->value);
                }
            )
            ->when(
                (int) $filterData['transfer_type'] === InterCompanyTransferType::TRANSFER_REQUEST->value,
                function ($query): void {
                    $query->where('order_type', OrderTypes::TRANSFER_REQUEST->value)
                        ->where('status', '!=', Statuses::CLOSED->value);
                }
            )
            ->when((int) $filterData['external_location_id'], function ($query) use ($filterData): void {
                $query->where('external_location_id', (int) $filterData['external_location_id']);
            })
            ->when((int) $filterData['external_company_id'], function ($query) use ($filterData): void {
                $query->where('external_company_id', (int) $filterData['external_company_id']);
            })
            ->when((int) $filterData['location_id'], function ($query) use ($filterData): void {
                $query->where('location_id', (int) $filterData['location_id']);
            })
            ->get();
    }

    public function getByDateAndLocationWithStockTransferAndProducts(array $filterData, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        return PurchaseOrder::query()
            ->select(
                'id',
                'external_purchase_order_id',
                'parent_purchase_order_id',
                'external_company_id',
                'external_location_id',
                'company_id',
                'location_id',
                'reference_number',
                'order_number',
                'external_order_number',
                'remarks',
                'attention',
                'require_date',
                'status',
                'order_type',
                'created_by_company_id',
                'created_at'
            )
            ->with([
                'items' => function ($query) use ($filterData, $masterProductQueries): void {
                    $query
                        ->select(
                            'id',
                            'purchase_order_id',
                            'external_purchase_order_item_id',
                            'product_id',
                            'quantity',
                            'rejected_quantity',
                            'transferred_quantity',
                            'price_per_unit',
                            'remarks',
                            'unit_of_measure_derivative_id',
                            'purchase_cost'
                        )
                        ->whereHas('product', function ($query): void {
                            if (config('app.product_variant')) {
                                $query->whereHas('masterProduct', function ($query): void {
                                    $query->where('is_non_inventory', false);
                                });
                            } else {
                                $query->isInventoryProduct();
                            }
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
                                if (config('app.product_variant')) {
                                    $query->select('id')
                                        ->from('products')
                                        ->with('masterProduct:' . $masterProductQueries->getBasicColumnNames())
                                        ->whereHas('masterProduct', function ($query) use ($filterData): void {
                                            $query->where('article_number', $filterData['article_number']);
                                        });
                                } else {
                                    $query->whereIn('product_id', function ($query) use ($filterData): void {
                                        $query->select('id')
                                            ->from('products')
                                            ->where('article_number', $filterData['article_number']);
                                    });
                                }
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
                'items.product' => function ($query) use ($productQueries, $masterProductQueries): void {
                    $columns = explode(',', $productQueries->getBasicColumnNames());
                    $query->select(...$columns)
                        ->when(config('app.product_variant'), function ($query) use ($masterProductQueries): void {
                            $query->with('masterProduct:' . $masterProductQueries->getBasicColumnNames())
                                ->whereHas('masterProduct', function ($query): void {
                                    $query->where('is_non_inventory', false);
                                });
                        }, function ($query): void {
                            $query->isInventoryProduct();
                        });
                },
                ...config('app.product_variant') ? [
                    'items.product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'items.product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'items.product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'items.product.masterProduct.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                ] : [
                    'items.product.color:' . $colorQueries->getBasicColumnNames(),
                    'items.product.size:' . $sizeQueries->getBasicColumnNames(),
                    'items.product.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
                ],
                'items.derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
                'location:' . $this->getLocationColumnName(),
                'fulfillments:' . $purchaseOrderFulfillmentQueries->getBasicColumns(),
                'fulfillments.purchaseOrderInvoice:' . $purchaseOrderInvoiceQueries->getColumnForCustomReport(),
                'externalCompany:' . $externalCompanyQueries->getBasicColumnNames(),
                'externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
            ])
            ->whereHas('items', function ($query): void {
                $query->select('id', 'product_id')
                    ->whereHas('product', function ($query): void {
                        $query->select('id');
                    });
            })
            ->where('company_id', $companyId)
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
            ->whereHas('items', function ($query) use ($filterData, $masterProductQueries): void {
                $query->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
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
                            if (config('app.product_variant')) {
                                $query->select('id')
                                    ->from('products')
                                    ->with('masterProduct:' . $masterProductQueries->getBasicColumnNames())
                                    ->whereHas('masterProduct', function ($query) use ($filterData): void {
                                        $query->where('article_number', $filterData['article_number']);
                                    });
                            } else {
                                $query->whereIn('product_id', function ($query) use ($filterData): void {
                                    $query->select('id')
                                        ->from('products')
                                        ->where('article_number', $filterData['article_number']);
                                });
                            }
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
            ->when(
                (int) $filterData['transfer_type'] === InterCompanyTransferType::SALES_ORDER->value,
                function ($query): void {
                    $query->where('order_type', OrderTypes::SALES_ORDER->value);
                }
            )
            ->when(
                (int) $filterData['transfer_type'] === InterCompanyTransferType::PURCHASE_ORDER->value,
                function ($query): void {
                    $query->where('order_type', OrderTypes::PURCHASE_ORDER->value);
                }
            )
            ->when(
                (int) $filterData['transfer_type'] === InterCompanyTransferType::PURCHASE_REQUEST->value,
                function ($query): void {
                    $query->where('order_type', OrderTypes::PURCHASE_REQUEST->value)
                        ->where('status', '!=', Statuses::CLOSED->value);
                }
            )
            ->when(
                (int) $filterData['transfer_type'] === InterCompanyTransferType::TRANSFER_REQUEST->value,
                function ($query): void {
                    $query->where('order_type', OrderTypes::TRANSFER_REQUEST->value)
                        ->where('status', '!=', Statuses::CLOSED->value);
                }
            )
            ->when((int) $filterData['external_location_id'], function ($query) use ($filterData): void {
                $query->where('external_location_id', (int) $filterData['external_location_id']);
            })
            ->when((int) $filterData['external_company_id'], function ($query) use ($filterData): void {
                $query->where('external_company_id', (int) $filterData['external_company_id']);
            })
            ->when((int) $filterData['location_id'], function ($query) use ($filterData): void {
                $query->where('location_id', (int) $filterData['location_id']);
            })
            ->get();
    }

    public function isPurchaseOrderCancel(int $purchaseOrderId, int $companyId): bool
    {
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);

        return PurchaseOrder::query()
            ->select('id')
            ->where('company_id', $companyId)
            ->where('id', $purchaseOrderId)
            ->whereHas(
                'fulfillments',
                $purchaseOrderFulfillmentQueries->filterByPurchaseOrderIdAndStatusNotDraft($purchaseOrderId)
            )
            ->doesntExist();
    }

    private function getPurchaseOrders(array $filterData, int $companyId): Builder
    {
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $purchaseOrderTransactionQueries = resolve(PurchaseOrderTransactionQueries::class);
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);

        return PurchaseOrder::query()
            ->select(
                'id',
                'external_purchase_order_id',
                'parent_purchase_order_id',
                'external_company_id',
                'external_location_id',
                'company_id',
                'location_id',
                'reference_number',
                'remarks',
                'require_date',
                'attention',
                'status',
                'order_type',
                'order_number',
                'created_at',
                'created_by_company_id',
                'external_order_number'
            )
            ->with([
                'company:' . $companyQueries->getBasicColumnNames(),
                'externalCompany:' . $externalCompanyQueries->getBasicColumnNames(),
                'externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
                'parentPurchaseOrder:' . $this->getIdAndOrderNumberColumn(),
                'childPurchaseOrder:' . $this->getIdAndOrderNumberColumn(),
                'location:' . $this->getLocationColumnName(),
                'transactions:' . $purchaseOrderTransactionQueries->getBasicColumns(),
                'fulfillments' => $purchaseOrderFulfillmentQueries->fetchAggregateCountByStatus(),
            ])
            ->whereHas('externalCompany', function ($query): void {
                $query->whereNull('deleted_at');
            })
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->where(function ($query): void {
                        $query->where('order_type', OrderTypes::PURCHASE_REQUEST->value)
                            ->orWhere('order_type', OrderTypes::TRANSFER_REQUEST->value);
                    })
                        ->whereNot('status', Statuses::CLOSED->value);
                })
                    ->orWhere('order_type', OrderTypes::SALES_ORDER->value)
                    ->orWhere('order_type', OrderTypes::PURCHASE_ORDER->value);
            })
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['order_number', 'external_order_number', 'reference_number'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
                });
            })
            ->when((int) $filterData['select_status'], function ($query) use ($filterData): void {
                $query->where('status', $filterData['select_status']);
            })
            ->when((int) $filterData['location_id'], function ($query) use ($filterData): void {
                $query->where('location_id', (int) $filterData['location_id']);
            })
            ->when((int) $filterData['external_location_id'], function ($query) use ($filterData): void {
                $query->where('external_location_id', (int) $filterData['external_location_id']);
            })
            ->when((int) $filterData['order_type'], function ($query) use ($filterData): void {
                $query->where('order_type', $filterData['order_type']);
            })
            ->when($filterData['order_number'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('order_number', $filterData['order_number'])
                        ->orWhere('external_order_number', $filterData['order_number']);
                });
            })
            ->when($filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getByPurchaseOrderIdForCreateDo(int $purchaseOrderId, int $companyId): ?PurchaseOrder
    {
        $columns = explode(',', $this->getColumnsForDeliveryOrders());

        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrder::query()
                ->select(...$columns)
                ->with([
                    'items' => function ($query) use (
                        $purchaseOrderItemQueries,
                        $productQueries,
                        $masterProductQueries,
                        $productVariantValueQueries,
                        $attributeQueries,
                        $unitOfMeasureDerivativeQueries
                    ): void {
                        $query->select(explode(',', $purchaseOrderItemQueries->getBasicColumnNames()))
                            ->with([
                                'product:' . $productQueries->getBasicColumnNames(),
                                'product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                                'derivative:' . $unitOfMeasureDerivativeQueries->getNameColumn(),
                            ])
                            ->whereRaw(
                                'quantity > (COALESCE(transferred_quantity, 0) + COALESCE(rejected_quantity, 0))'
                            );
                    },
                ])
                ->where('company_id', $companyId)
                ->find($purchaseOrderId);
        }

        return PurchaseOrder::query()
            ->select(...$columns)
            ->with([
                'items' => function ($query) use (
                    $purchaseOrderItemQueries,
                    $productQueries,
                    $colorQueries,
                    $sizeQueries,
                    $unitOfMeasureDerivativeQueries
                ): void {
                    $query->select(explode(',', $purchaseOrderItemQueries->getBasicColumnNames()))
                        ->with([
                            'product:' . $productQueries->getBasicColumnNames(),
                            'product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                            'product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                            'derivative:' . $unitOfMeasureDerivativeQueries->getNameColumn(),
                        ])
                        ->whereRaw('quantity > (COALESCE(transferred_quantity, 0) + COALESCE(rejected_quantity, 0))');
                },
            ])
            ->where('company_id', $companyId)
            ->find($purchaseOrderId);
    }

    public function getByPurchaseOrderIdAndLocation(
        int $purchaseOrderId,
        int $companyId,
        int $locationId,
    ): ?PurchaseOrder {
        $columns = explode(',', $this->getColumnsForDeliveryOrders());

        $purchaseOrderItemQueries = resolve(PurchaseOrderItemQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrder::query()
                ->select(...$columns)
                ->with([
                    'items' => function ($query) use (
                        $purchaseOrderItemQueries,
                        $productQueries,
                        $masterProductQueries,
                        $productVariantValueQueries,
                        $attributeQueries,
                        $unitOfMeasureDerivativeQueries
                    ): void {
                        $query->select(explode(',', $purchaseOrderItemQueries->getBasicColumnNames()))
                            ->with([
                                'product:' . $productQueries->getBasicColumnNames(),
                                'product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                                'derivative:' . $unitOfMeasureDerivativeQueries->getNameColumn(),
                            ]);
                    },
                ])
                ->where($this->filterByCompanyAndLocation($companyId, $locationId))
                ->find($purchaseOrderId);
        }

        return PurchaseOrder::query()
            ->select(...$columns)
            ->with([
                'items' => function ($query) use (
                    $purchaseOrderItemQueries,
                    $productQueries,
                    $colorQueries,
                    $sizeQueries,
                    $unitOfMeasureDerivativeQueries
                ): void {
                    $query->select(explode(',', $purchaseOrderItemQueries->getBasicColumnNames()))
                        ->with([
                            'product:' . $productQueries->getBasicColumnNames(),
                            'product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                            'product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                            'derivative:' . $unitOfMeasureDerivativeQueries->getNameColumn(),
                        ]);
                },
            ])
            ->where($this->filterByCompanyAndLocation($companyId, $locationId))
            ->find($purchaseOrderId);
    }
}
