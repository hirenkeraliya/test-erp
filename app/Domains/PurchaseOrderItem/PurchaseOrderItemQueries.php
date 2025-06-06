<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderItem;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalLocation\ExternalLocationQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\PurchaseOrder\Enums\InterCompanyTransferType;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\PurchaseOrderInvoice\Enums\InvoiceStatuses;
use App\Domains\PurchaseOrderInvoice\PurchaseOrderInvoiceQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\PurchaseOrderItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PurchaseOrderItemQueries
{
    public function addNew(array $purchaseOrderItemDate): PurchaseOrderItem
    {
        return PurchaseOrderItem::create($purchaseOrderItemDate);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,purchase_order_id,parent_purchase_order_item_id,product_id,quantity,rejected_quantity,transferred_quantity,price_per_unit,remarks,unit_of_measure_derivative_id,external_purchase_order_item_id';
    }

    public function getColumnIdAndProductId(): string
    {
        return 'id,purchase_order_id,product_id';
    }

    public function getColumnForPrintInvoice(): string
    {
        return 'id,purchase_order_id,product_id,purchase_cost';
    }

    public function getByPurchaseOrderId(int $purchaseOrderId, int $companyId): Collection
    {
        return $this->commonGetByPurchaseOrderId($purchaseOrderId, $companyId)->get();
    }

    public function getById(int $purchaseOrderItemId): PurchaseOrderItem
    {
        return PurchaseOrderItem::query()
            ->select(
                'id',
                'purchase_order_id',
                'product_id',
                'quantity',
                'rejected_quantity',
                'transferred_quantity',
                'price_per_unit',
                'remarks',
            )
            ->findOrFail($purchaseOrderItemId);
    }

    public function updateExternalPurchaseOrderItemId(
        int $purchaseOrderItemId,
        int $externalPurchaseOrderItemId
    ): void {
        $purchaseOrderItem = $this->getById($purchaseOrderItemId);
        $purchaseOrderItem->external_purchase_order_item_id = $externalPurchaseOrderItemId;
        $purchaseOrderItem->save();
    }

    public function updateTransferredQuantity(
        PurchaseOrderItem $purchaseOrderItem,
        float $transferredQuantity
    ): void {
        $purchaseOrderItem->transferred_quantity += $transferredQuantity;
        $purchaseOrderItem->save();
    }

    public function decreaseTransferredQuantity(
        PurchaseOrderItem $purchaseOrderItem,
        float $transferredQuantity
    ): void {
        $purchaseOrderItem->transferred_quantity -= $transferredQuantity;
        $purchaseOrderItem->save();
    }

    public function addTransferredQuantity(
        PurchaseOrderItem $purchaseOrderItem,
        float $transferredQuantity
    ): void {
        $purchaseOrderItem->transferred_quantity = $transferredQuantity;
        $purchaseOrderItem->save();
    }

    public function getByIds(array $purchaseOrderItemIds): Collection
    {
        return PurchaseOrderItem::query()
            ->select(
                'id',
                'purchase_order_id',
                'product_id',
                'quantity',
                'rejected_quantity',
                'transferred_quantity',
                'price_per_unit',
                'remarks',
            )
            ->whereInCaseSensitive('id', $purchaseOrderItemIds)
            ->get();
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);

        $purchaseOrderProducts = PurchaseOrderItem::query()
            ->select('id', 'purchase_order_id', 'product_id')
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId))
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($purchaseOrderProducts as $purchaseOrderProduct) {
            $purchaseOrderProduct->product_id = $newProductId;
            $purchaseOrderProduct->save();
        }
    }

    public function getPaginatedByPurchaseOrderId(array $filterData, int $companyId): LengthAwarePaginator
    {
        $productQueries = resolve(ProductQueries::class);

        return $this->commonGetByPurchaseOrderId((int) $filterData['id'], $companyId)
            ->whereHas('product', $productQueries->searchByNameAndUpc($filterData['search_text']))
            ->paginate($filterData['per_page']);
    }

    public function getByDateAndLocationWithProduct(array $filterData, int $companyId): Collection
    {
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalLocationQueries = resolve(ExternalLocationQueries::class);
        $purchaseOrderFulfillmentQueries = resolve(PurchaseOrderFulfillmentQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $purchaseOrderInvoiceQueries = resolve(PurchaseOrderInvoiceQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $relations = [
            'product' => function ($query) use ($productQueries): void {
                $columns = explode(',', $productQueries->getBasicColumnNames());
                $query->select(...$columns)
                    ->isInventoryProduct();
            },
            'purchaseOrderFulFillmentsItems:' . $purchaseOrderFulfillmentItemQueries->getBasicColumnForReport(),
            'purchaseOrderFulFillmentsItems.purchaseOrderFulfillment:' . $purchaseOrderFulfillmentQueries->getBasicColumns(),
            'purchaseOrderFulFillmentsItems.purchaseOrderFulfillment.purchaseOrderInvoice:' . $purchaseOrderInvoiceQueries->getColumnForCustomReport(),
            'purchaseOrder:' . $purchaseOrderQueries->getBasicColumn(),
            'purchaseOrder.location',
            'product.unitOfMeasure:' . $unitOfMeasureQueries->getBasicColumnNames(),
            'derivative:' . $unitOfMeasureDerivativeQueries->getBasicColumnNames(),
            'purchaseOrder.externalCompany:' . $externalCompanyQueries->getBasicColumnNames(),
            'purchaseOrder.externalLocation:' . $externalLocationQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return PurchaseOrderItem::query()
            ->select(
                'id',
                'external_purchase_order_item_id',
                'product_id',
                'purchase_order_id',
                'unit_of_measure_derivative_id',
                'quantity',
                'transferred_quantity',
                'purchase_cost'
            )
            ->with($relations)
            ->whereHas('product', function ($query): void {
                if (config('app.product_variant')) {
                    $query->select('id', 'master_product_id')
                        ->whereHas('masterProduct', function ($query): void {
                            $query->where('is_non_selling_item', false);
                        });
                } else {
                    $query->select('id')
                        ->where('is_non_selling_item', false);
                }
            })
            ->whereHas('purchaseOrder', function ($query) use (
                $companyId,
                $filterData,
                $purchaseOrderQueries
            ): void {
                $query->where($purchaseOrderQueries->filterByCompany($companyId))
                    ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                    ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
                    ->when(
                        (int) $filterData['transfer_type'] === InterCompanyTransferType::TRANSFER_REQUEST->value,
                        function ($query): void {
                            $query->where('order_type', OrderTypes::TRANSFER_REQUEST->value);
                        }
                    )
                    ->when(
                        (int) $filterData['transfer_type'] === InterCompanyTransferType::PURCHASE_REQUEST->value,
                        function ($query): void {
                            $query->where('order_type', OrderTypes::PURCHASE_REQUEST->value);
                        }
                    )
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
                    ->when((int) $filterData['external_location_id'], function ($query) use ($filterData): void {
                        $query->where('external_location_id', (int) $filterData['external_location_id']);
                    })
                    ->when((int) $filterData['external_company_id'], function ($query) use ($filterData): void {
                        $query->where('external_company_id', (int) $filterData['external_company_id']);
                    })
                    ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                        $query->where('product_id', $filterData['product_id']);
                    })
                    ->when(null !== $filterData['article_number'], function ($query) use ($filterData): void {
                        $query->whereIn('product_id', function ($query) use ($filterData): void {
                            if (config('app.product_variant')) {
                                $query->select('products.id')
                                    ->from('products')
                                    ->join(
                                        'master_products',
                                        'products.master_product_id',
                                        '=',
                                        'master_products.id'
                                    )
                                    ->where('master_products.article_number', $filterData['article_number']);
                            } else {
                                $query->select('products.id')
                                    ->from('products')
                                    ->where('article_number', $filterData['article_number']);
                            }
                        });
                    })
                    ->when(null !== $filterData['product_collection_id'], function ($query) use (
                        $filterData
                    ): void {
                        $query->whereIn('product_id', function ($query) use ($filterData): void {
                            $query->select('product_id')
                                ->from('product_collection_products')
                                ->where('product_collection_id', (int) $filterData['product_collection_id']);
                        });
                    })
                    ->when((int) $filterData['location_id'], function ($query) use ($filterData): void {
                        $query->where('location_id', (int) $filterData['location_id']);
                    });
            })
            ->get();
    }

    public function updatePurchaseCostOfDraftStatus(int $purchaseOrderId): void
    {
        $productQueries = resolve(ProductQueries::class);

        PurchaseOrderItem::select('id', 'purchase_order_id', 'product_id')
            ->where('purchase_order_id', $purchaseOrderId)
            ->with('product:'.$productQueries->getPurchaseCostColumn())
            ->whereHas('purchaseOrderFulFillmentsItems', function ($query): void {
                $query->select('id', 'purchase_order_item_id')
                    ->whereHas('purchaseOrderFulfillment', function ($query): void {
                        $query->select('id', 'purchase_order_fulfillment_id')
                            ->whereHas('purchaseOrderInvoice', function ($query): void {
                                $query->select('id', 'purchase_order_invoice_id')
                                    ->where('status', InvoiceStatuses::DRAFT->value);
                            });
                    });
            })
            ->get()
            ->each(function ($purchaseOrderItem): void {
                if ($purchaseOrderItem->product) {
                    $purchaseOrderItem->purchase_cost = $purchaseOrderItem->product->purchase_cost;
                    $purchaseOrderItem->save();
                }
            });
    }

    private function commonGetByPurchaseOrderId(int $purchaseOrderId, int $companyId): Builder
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $purchaseOrderQueries = resolve(PurchaseOrderQueries::class);
        $unitOfMeasureDerivativeQueries = resolve(UnitOfMeasureDerivativeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        if (config('app.product_variant')) {
            return PurchaseOrderItem::query()
                ->select(
                    'id',
                    'purchase_order_id',
                    'product_id',
                    'quantity',
                    'rejected_quantity',
                    'transferred_quantity',
                    'price_per_unit',
                    'unit_of_measure_derivative_id',
                    'remarks',
                )
                ->with(
                    'product:' . $productQueries->getBasicColumnNames(),
                    'product.masterProduct:' . $masterProductQueries->getBasicColumnsForInventory(),
                    'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                    'derivative:' . $unitOfMeasureDerivativeQueries->getNameColumn(),
                )
                ->where('purchase_order_id', $purchaseOrderId)
                ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId));
        }

        return PurchaseOrderItem::query()
            ->select(
                'id',
                'purchase_order_id',
                'product_id',
                'quantity',
                'rejected_quantity',
                'transferred_quantity',
                'price_per_unit',
                'unit_of_measure_derivative_id',
                'remarks',
            )
            ->with(
                'product:' . $productQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'derivative:' . $unitOfMeasureDerivativeQueries->getNameColumn(),
            )
            ->where('purchase_order_id', $purchaseOrderId)
            ->whereHas('purchaseOrder', $purchaseOrderQueries->filterByCompany($companyId));
    }

    public function delete(PurchaseOrderItem $purchaseOrderItem): void
    {
        $purchaseOrderItem->delete();
    }
}
