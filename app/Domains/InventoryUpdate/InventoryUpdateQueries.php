<?php

declare(strict_types=1);

namespace App\Domains\InventoryUpdate;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Common\Services\ProductVariantFilterService;
use App\Domains\Department\DepartmentQueries;
use App\Domains\GoodsReceivedNoteProduct\GoodsReceivedNoteProductQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\OrderItem\OrderItemQueries;
use App\Domains\OrderReturnItem\OrderReturnItemQueries;
use App\Domains\PartiallyReceiveFulfillmentItem\PartiallyReceiveFulfillmentItemQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\PurchaseOrderFulfillmentItem\PurchaseOrderFulfillmentItemQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughIncludeTypes;
use App\Domains\Size\SizeQueries;
use App\Domains\StockAdjustmentItem\StockAdjustmentItemQueries;
use App\Domains\StockTransferItem\StockTransferItemQueries;
use App\Domains\VoidSale\VoidSaleQueries;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\InventoryUpdate;
use App\Models\Model;
use App\Models\OrderItem;
use App\Models\OrderReturnItem;
use App\Models\PartiallyReceiveFulfillmentItem;
use App\Models\PurchaseOrderFulfillmentItem;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use App\Models\StockAdjustmentItem;
use App\Models\StockTransferItem;
use App\Models\VoidSale;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryUpdateQueries
{
    public function getPaginatedStockMovementsOfAProductForALocation(
        array $filterData,
        int $companyId
    ): LengthAwarePaginator {
        return $this->getStockMovementsOfAProductForLocationQuery($filterData, $companyId)->paginate(
            $filterData['per_page']
        );
    }

    public function getStockMovementsOfAProductForALocationForExport(
        array $filterData,
        int $companyId
    ): Collection {
        return $this->getStockMovementsOfAProductForLocationQuery($filterData, $companyId)->get();
    }

    public function getPaginatedStockMovementsOfAProductForLocationTypeStore(
        array $filterData,
        int $locationId,
    ): LengthAwarePaginator {
        return $this->getStockMovementsOfAProductForLocationTypeQuery($filterData, $locationId)->paginate(
            $filterData['per_page']
        );
    }

    public function addNew(
        int $productId,
        float $quantity,
        ?int $locationId,
        Model $affectedBy,
        User $user,
        float $closingStock,
        ?int $batchId,
        ?int $purchaseAmountId,
        ?string $happenedAt = null,
        ?string $notes = null,
        ?int $serialNumberId = null,
    ): void {
        InventoryUpdate::create([
            'product_id' => $productId,
            'batch_id' => $batchId,
            'purchase_amount_id' => $purchaseAmountId,
            'location_id' => $locationId,
            'affected_by_id' => $affectedBy->id,
            'affected_by_type' => ModelMapping::getCaseName($affectedBy::class),
            'quantity' => $quantity,
            'user_type' => ModelMapping::getCaseName($user::class),
            'user_id' => $user->id,
            'happened_at' => $happenedAt ?? now(),
            'closing_stock' => $closingStock,
            'notes' => $notes,
            'serial_number_id' => $serialNumberId,
        ]);
    }

    public function addNewForExternalPurchaseOrder(
        int $productId,
        float $quantity,
        ?int $locationId,
        Model $affectedBy,
        int $userId,
        string $userType,
        float $closingStock,
        ?int $batchId,
        ?int $purchaseAmountId,
        ?string $happenedAt = null,
        ?string $notes = null,
        ?int $serialNumberId = null,
    ): void {
        InventoryUpdate::create([
            'product_id' => $productId,
            'batch_id' => $batchId,
            'purchase_amount_id' => $purchaseAmountId,
            'location_id' => $locationId,
            'affected_by_id' => $affectedBy->id,
            'affected_by_type' => ModelMapping::getCaseName($affectedBy::class),
            'quantity' => $quantity,
            'user_type' => $userType,
            'user_id' => $userId,
            'happened_at' => $happenedAt ?? now(),
            'closing_stock' => $closingStock,
            'notes' => $notes,
            'serial_number_id' => $serialNumberId,
        ]);
    }

    public function searchLocationByNameAndCode(string $searchText): Closure
    {
        return fn ($query) => $query
            ->whereAny(['name', 'code'], 'LIKE', '%' . $searchText . '%');
    }

    public function getLocationColumnNames(): string
    {
        return 'id,name,code,company_id,type_id';
    }

    public function getStockMovementsOfProductsForALocationForPrint(array $filterData): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $goodsReceivedNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);
        $stockAdjustmentItem = resolve(StockAdjustmentItemQueries::class);
        $voidSaleQueries = resolve(VoidSaleQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);
        $orderReturnItemQueries = resolve(OrderReturnItemQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $partiallyReceiveFulfillmentItemQueries = resolve(PartiallyReceiveFulfillmentItemQueries::class);

        $relations = [
            'product:' . $productQueries->getBasicColumnNames(),
            'affectedBy' => function (MorphTo $morphTo) use (
                $stockTransferItemQueries,
                $saleItemQueries,
                $saleReturnItemQueries,
                $goodsReceivedNoteProductQueries,
                $stockAdjustmentItem,
                $voidSaleQueries,
                $purchaseOrderFulfillmentItemQueries,
                $orderItemQueries,
                $orderReturnItemQueries,
                $partiallyReceiveFulfillmentItemQueries
            ): void {
                $morphTo->constrain([
                    StockTransferItem::class => $stockTransferItemQueries->getSelectIdColumn(),
                    SaleItem::class => $saleItemQueries->getSelectIdColumn(),
                    SaleReturnItem::class => $saleReturnItemQueries->getSelectIdColumn(),
                    GoodsReceivedNoteProduct::class => $goodsReceivedNoteProductQueries->getSelectIdColumn(),
                    StockAdjustmentItem::class => $stockAdjustmentItem->getStockAdjustmentWithRelation(),
                    VoidSale::class => $voidSaleQueries->getSelectIdColumn(),
                    PurchaseOrderFulfillmentItem::class => $purchaseOrderFulfillmentItemQueries->getWithPurchaseOrderFulfillmentAndPurchaseOrder(),
                    PartiallyReceiveFulfillmentItem::class => $partiallyReceiveFulfillmentItemQueries->getPartiallyReceiveFulfillmentItemWithRelation(),
                    OrderItem::class => $orderItemQueries->getSelectIdColumn(),
                    OrderReturnItem::class => $orderReturnItemQueries->getSelectIdColumn(),
                ]);
            },
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'product.masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
                'product.brand:' . $brandQueries->getIdAndNameColumnNames(),
                'product.department:' . $departmentQueries->getBasicColumnNames(),
            ]);
        }

        return InventoryUpdate::query()
            ->with($relations)
            ->select(
                'id',
                'product_id',
                'affected_by_id',
                'affected_by_type',
                'location_id',
                'quantity',
                'happened_at',
                'closing_stock',
                'created_at',
                DB::raw("'normal_product' as source")
            )
            ->whereHas('product', function ($query): void {
                if (config('app.product_variant')) {
                    $query->whereHas('masterProduct', function ($query): void {
                        $query->select('id')->where('is_non_inventory', false);
                    });
                } else {
                    $query->select('id')->where('is_non_inventory', false);
                }
            })
            ->whereIntegerInRaw('location_id', $filterData['location_ids'])
            ->when(null !== $filterData['product_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('product_id', $filterData['product_ids']);
            })
            ->when(null !== $filterData['category_ids'], function ($query) use (
                $categoryQueries,
                $filterData
            ): void {
                $query->whereHas('product', function ($query) use ($categoryQueries, $filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData, $categoryQueries): void {
                            $query->select('id')
                                ->whereHas(
                                    'categories',
                                    $categoryQueries->filterByIdsAndCompany(
                                        $filterData['category_ids'],
                                        $filterData['company_id']
                                    )
                                );
                        });
                    } else {
                        $query->select('id')
                            ->whereHas(
                                'categories',
                                $categoryQueries->filterByIdsAndCompany(
                                    $filterData['category_ids'],
                                    $filterData['company_id']
                                )
                            );
                    }
                });
            })
            ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('product_id', $filterData['product_id']);
            })
            ->when(null !== $filterData['article_number'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                            $query->where('article_number', $filterData['article_number']);
                        });
                    } else {
                        $query->where('article_number', $filterData['article_number']);
                    }
                });
            })
            ->when(
                array_key_exists('product_collection_id', $filterData) && null !== $filterData['product_collection_id'],
                function ($query) use ($filterData): void {
                    $query->whereIn('product_id', function ($query) use ($filterData): void {
                        $query->select('product_id')
                            ->from('product_collection_products')
                            ->where('product_collection_id', (int) $filterData['product_collection_id']);
                    });
                }
            )
            ->when(null !== $filterData['brand_ids'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
                        });
                    } else {
                        $query->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
                    }
                });
            })
            ->when(null !== $filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
                        });
                    } else {
                        $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
                    }
                });
            })
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
            ->orderBy('happened_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getByProductIdAndLocationForStockCardPrint(array $filterData): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $goodsReceivedNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);
        $stockAdjustmentItemQueries = resolve(StockAdjustmentItemQueries::class);
        $voidSaleQueries = resolve(VoidSaleQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);
        $orderReturnItemQueries = resolve(OrderReturnItemQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantFilterService = resolve(ProductVariantFilterService::class);
        $relations = [
            'affectedBy' => function (MorphTo $morphTo) use (
                $saleItemQueries,
                $saleReturnItemQueries,
                $stockTransferItemQueries,
                $goodsReceivedNoteProductQueries,
                $stockAdjustmentItemQueries,
                $voidSaleQueries,
                $purchaseOrderFulfillmentItemQueries,
                $orderItemQueries,
                $orderReturnItemQueries
            ): void {
                $morphTo->constrain([
                    StockTransferItem::class => $stockTransferItemQueries->getStockTransferWithLocation(),
                    SaleItem::class => $saleItemQueries->getOfflineSaleWithRelation(),
                    SaleReturnItem::class => $saleReturnItemQueries->getOfflineSaleReturnWithRelation(),
                    GoodsReceivedNoteProduct::class => $goodsReceivedNoteProductQueries->getGoodsReceivedNoteForStockCardPrint(),
                    StockAdjustmentItem::class => $stockAdjustmentItemQueries->getStockAdjustmentForStockCardPrint(),
                    VoidSale::class => $voidSaleQueries->getSelectIdAndVoleSaleNumberColumns(),
                    PurchaseOrderFulfillmentItem::class => $purchaseOrderFulfillmentItemQueries->getWithPurchaseOrderFulfillmentAndPurchaseOrder(),
                    OrderItem::class => $orderItemQueries->getOfflineOrderWithRelation(),
                    OrderReturnItem::class => $orderReturnItemQueries->getOfflineOrderReturnWithRelation(),
                ]);
            },
            'product:' . $productQueries->getBasicColumnNames(),
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

        return InventoryUpdate::query()
            ->with($relations)
            ->select(
                'id',
                'product_id',
                'affected_by_id',
                'affected_by_type',
                'location_id',
                'quantity',
                'happened_at',
                'closing_stock',
                'created_at'
            )
            ->whereHas('product', $productVariantFilterService->filterIsNonInventoryOrSellingItem('is_non_inventory'))
            ->where('location_id', $filterData['location_id'])
            ->when(null !== $filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('product_id', $filterData['product_id']);
            })
            ->when(null !== $filterData['article_number'], function ($query) use (
                $filterData,
                $productVariantFilterService
            ): void {
                $query->whereIn(
                    'product_id',
                    $productVariantFilterService->filterByDepartmentAndBrandAndArticleNumber(
                        'article_number',
                        $filterData['article_number']
                    )
                );
            })
            ->when(
                array_key_exists('department_id', $filterData) && null !== $filterData['department_id'],
                function ($query) use ($filterData, $productVariantFilterService): void {
                    $query->whereIn(
                        'product_id',
                        $productVariantFilterService->filterByDepartmentAndBrandAndArticleNumber(
                            'department_id',
                            $filterData['department_id']
                        )
                    );
                }
            )
            ->when(
                array_key_exists('brand_id', $filterData) && null !== $filterData['brand_id'],
                function ($query) use ($filterData, $productVariantFilterService): void {
                    $query->whereIn(
                        'product_id',
                        $productVariantFilterService->filterByDepartmentAndBrandAndArticleNumber(
                            'brand_id',
                            $filterData['brand_id']
                        )
                    );
                }
            )
            ->when(
                array_key_exists('category_id', $filterData) && null !== $filterData['category_id'],
                function ($query) use ($filterData): void {
                    $query->whereIn('product_id', function ($query) use ($filterData): void {
                        if (config('app.product_variant')) {
                            $query->select('products.id')
                                ->from('products')
                                ->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                                ->join(
                                    'category_master_product',
                                    'master_products.id',
                                    '=',
                                    'category_master_product.master_product_id'
                                )
                                ->where('category_master_product.category_id', (int) $filterData['category_id']);
                        } else {
                            $query->select('product_id')
                            ->from('category_product')
                            ->where('category_id', (int) $filterData['category_id']);
                        }
                    });
                }
            )
            ->when(
                array_key_exists('product_collection_id', $filterData) && null !== $filterData['product_collection_id'],
                function ($query) use ($filterData): void {
                    $query->whereIn('product_id', function ($query) use ($filterData): void {
                        $query->select('product_id')
                            ->from('product_collection_products')
                            ->where('product_collection_id', (int) $filterData['product_collection_id']);
                    });
                }
            )
            ->where('happened_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]))
            ->orderBy('happened_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getStockMovementsOfAProductForALocationForExportInStoreManagerPanel(
        array $filterData,
        int $locationId,
    ): Collection {
        return $this->getStockMovementsOfAProductForLocationTypeQuery($filterData, $locationId)->get();
    }

    public function getPaginatedStockMovementsOfAProductForLocationTypeWarehouse(
        array $filterData,
        int $locationId,
    ): LengthAwarePaginator {
        return $this->getStockMovementsOfAProductForLocationTypeQuery(
            $filterData,
            $locationId,
        )->paginate($filterData['per_page']);
    }

    public function getStockMovementsOfAProductForALocationForExportInWarehouseManagerPanel(
        array $filterData,
        int $locationId,
    ): Collection {
        return $this->getStockMovementsOfAProductForLocationTypeQuery($filterData, $locationId)->get();
    }

    public function updateClosingStock(InventoryUpdate $inventoryUpdate, float $closingStock): void
    {
        $inventoryUpdate->closing_stock = $closingStock;
        $inventoryUpdate->save();
    }

    public function getRecordsAfterDateByLocationAndProduct(
        string $date,
        int $locationId,
        int $productId
    ): Collection {
        return InventoryUpdate::query()
            ->select('id', 'quantity', 'notes', 'closing_stock')
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->whereDate('happened_at', '>', $date)
            ->orderBy('happened_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getLatestClosingStockBy(string $date, int $locationId, int $productId): ?InventoryUpdate
    {
        return InventoryUpdate::query()
            ->select('id', 'closing_stock')
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->whereDate('happened_at', '<=', $date)
            ->latest('happened_at')
            ->latest('id')
            ->first();
    }

    public function updateClosingStockOfPreviousRecord(
        InventoryUpdate $inventoryUpdate,
        float $closingStock,
        int $stockTransferItemId,
    ): float {
        $originalClosingStock = $inventoryUpdate->closing_stock;
        $inventoryUpdate->closing_stock = $closingStock + $inventoryUpdate->quantity;
        $inventoryUpdate->notes = $inventoryUpdate->notes . ' The closing stock of this entry was updated from ' . $originalClosingStock . ' to ' . $inventoryUpdate->closing_stock . ' because of the received date effect of stock transfer item (id: ' . $stockTransferItemId . ') at date: ' . now()->format(
            'Y-m-d H:i:s'
        ) . '.';
        $inventoryUpdate->save();
        $inventoryUpdate->fresh();

        return $inventoryUpdate->closing_stock;
    }

    public function updateClosingStockOfPreviousRecordForPurchasePlan(
        InventoryUpdate $inventoryUpdate,
        float $closingStock,
        int $stockTransferItemId,
    ): float {
        $originalClosingStock = $inventoryUpdate->closing_stock;
        $inventoryUpdate->closing_stock = $closingStock + $inventoryUpdate->quantity;
        $inventoryUpdate->notes = $inventoryUpdate->notes . ' The closing stock of this entry was updated from ' . $originalClosingStock . ' to ' . $inventoryUpdate->closing_stock . ' because of the received date effect of external purchase order item (id: ' . $stockTransferItemId . ') at date: ' . now()->format(
            'Y-m-d H:i:s'
        ) . '.';
        $inventoryUpdate->save();
        $inventoryUpdate->fresh();

        return $inventoryUpdate->closing_stock;
    }

    public function getByProductIdAndLocationAndUpdateWithNewProductId(
        int $productId,
        int $newProductId,
        string $notes
    ): void {
        $inventoryUpdates = InventoryUpdate::query()
            ->select('id', 'product_id', 'notes')
            ->where('product_id', $productId)
            ->get();

        foreach ($inventoryUpdates as $inventoryUpdate) {
            $inventoryUpdate->product_id = $newProductId;
            $inventoryUpdate->notes = $inventoryUpdate->notes . ' ' . $notes;
            $inventoryUpdate->save();
        }
    }

    public function getInventoryUpdatesByProductIdAndLocation(int $productId, int $locationId): Collection
    {
        return InventoryUpdate::query()
            ->select(
                'id',
                'product_id',
                'affected_by_id',
                'affected_by_type',
                'location_id',
                'quantity',
                'happened_at',
                'closing_stock',
                'created_at'
            )
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->orderBy('happened_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    public function filterByFirstGrnForProductAgeingReport(array $filterData, int $minDays, int $maxDays): Closure
    {
        return fn ($query) => $query->select('product_id')
            ->from('inventory_updates')
            ->where('affected_by_type', ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->name)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('location_id', $filterData['location_ids']);
            })
            ->groupBy('product_id')
            ->when($maxDays > 0, function ($query) use ($maxDays): void {
                $query->havingRaw(
                    'MIN(happened_at) >= ?',
                    [CommonFunctions::addStartTime(now()->subDays($maxDays)->format('Y-m-d'))]
                );
            })
            ->when($minDays > 0, function ($query) use ($minDays): void {
                $query->havingRaw(
                    'MIN(happened_at) <= ?',
                    [CommonFunctions::addEndTime(now()->subDays($minDays)->format('Y-m-d'))]
                );
            })
            ->orderBy(DB::raw('MIN(happened_at)'), 'asc');
    }

    public function filterByFirstTransferInForProductAgeingReport(
        array $filterData,
        int $minDays,
        int $maxDays
    ): Closure {
        return fn ($query) => $query->select('product_id')
            ->from('inventory_updates')
            ->where('affected_by_type', ModelMapping::STOCK_TRANSFER_ITEM->name)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('location_id', $filterData['location_ids']);
            })
            ->when($maxDays > 0, function ($query) use ($maxDays): void {
                $query->havingRaw(
                    'MIN(happened_at) >= ?',
                    [CommonFunctions::addStartTime(now()->subDays($maxDays)->format('Y-m-d'))]
                );
            })
            ->when($minDays > 0, function ($query) use ($minDays): void {
                $query->havingRaw(
                    'MIN(happened_at) <= ?',
                    [CommonFunctions::addEndTime(now()->subDays($minDays)->format('Y-m-d'))]
                );
            })
            ->groupBy('product_id')
            ->orderBy(DB::raw('MIN(happened_at)'), 'asc');
    }

    public function getStockMovementsByLocationsAndProductIdsForPrint(array $filterData): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        $relations = [
            'product' => $productQueries->getSellingProductWithRelation(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'product.masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNamesForRegularSalesApi(),
                'product.brand:' . $brandQueries->getIdAndNameColumnNames(),
                'product.department:' . $departmentQueries->getBasicColumnNamesForHappyHours(),
            ]);
        }

        return InventoryUpdate::query()
            ->with($relations)
            ->select(
                'id',
                'product_id',
                'affected_by_id',
                'affected_by_type',
                'location_id',
                'quantity',
                'happened_at',
                'closing_stock',
                'created_at',
                DB::raw("'except_product' as source")
            )
            ->whereIntegerInRaw('location_id', $filterData['location_ids'])
            ->when(array_key_exists('product_ids', $filterData), function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('product_id', $filterData['product_ids']);
            })
            ->orderBy('happened_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    private function getStockMovementsOfAProductForLocationQuery(array $filterData, int $companyId): Builder
    {
        $productQueries = resolve(ProductQueries::class);
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $goodsReceivedNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);
        $stockAdjustmentItemQueries = resolve(StockAdjustmentItemQueries::class);
        $voidSaleQueries = resolve(VoidSaleQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $partiallyReceiveFulfillmentItemQueries = resolve(PartiallyReceiveFulfillmentItemQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);

        return InventoryUpdate::query()
            ->select(
                'id',
                'product_id',
                'location_id',
                'affected_by_id',
                'affected_by_type',
                'quantity',
                'user_id',
                'user_type',
                'happened_at',
                'closing_stock'
            )
            ->with([
                'location:' . $this->getLocationColumnNames(),
                'affectedBy' => function (MorphTo $morphTo) use (
                    $saleItemQueries,
                    $saleReturnItemQueries,
                    $goodsReceivedNoteProductQueries,
                    $stockTransferItemQueries,
                    $stockAdjustmentItemQueries,
                    $purchaseOrderFulfillmentItemQueries,
                    $voidSaleQueries,
                    $orderItemQueries,
                    $partiallyReceiveFulfillmentItemQueries,
                ): void {
                    $morphTo->constrain([
                        StockTransferItem::class => $stockTransferItemQueries->getStockTransferWithLocation(),
                        SaleItem::class => $saleItemQueries->getOfflineSaleWithRelation(),
                        SaleReturnItem::class => $saleReturnItemQueries->getOfflineSaleReturnWithRelation(),
                        GoodsReceivedNoteProduct::class => $goodsReceivedNoteProductQueries->getGoodsReceivedNoteWithRelation(),
                        StockAdjustmentItem::class => $stockAdjustmentItemQueries->getStockAdjustmentWithRelation(),
                        VoidSale::class => $voidSaleQueries->getSelectIdAndVoleSaleNumberColumns(),
                        PurchaseOrderFulfillmentItem::class => $purchaseOrderFulfillmentItemQueries->getWithPurchaseOrderFulfillmentAndPurchaseOrder(),
                        PartiallyReceiveFulfillmentItem::class => $partiallyReceiveFulfillmentItemQueries->getPartiallyReceiveFulfillmentItemWithRelation(),
                        OrderItem::class => $orderItemQueries->getOrderItemWithOrder(),
                    ]);
                },
            ])
            ->where('product_id', $filterData['product_id'])
            ->whereIntegerInRaw('location_id', $filterData['location_ids'])
            ->whereHas('product', $productQueries->filterByCompany($companyId))
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->orWhereHas('location', $this->searchLocationByNameAndCode($filterData['search_text']));
                });
            })
            ->orderBy('happened_at', 'desc')
            ->orderBy('id', 'desc');
    }

    private function getStockMovementsOfAProductForLocationTypeQuery(
        array $filterData,
        int $locationId,
    ): Builder {
        $stockTransferItemQueries = resolve(StockTransferItemQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $goodsReceivedNoteProductQueries = resolve(GoodsReceivedNoteProductQueries::class);
        $stockAdjustmentItemQueries = resolve(StockAdjustmentItemQueries::class);
        $voidSaleQueries = resolve(VoidSaleQueries::class);
        $purchaseOrderFulfillmentItemQueries = resolve(PurchaseOrderFulfillmentItemQueries::class);
        $orderItemQueries = resolve(OrderItemQueries::class);

        return InventoryUpdate::query()
            ->select(
                'id',
                'product_id',
                'location_id',
                'affected_by_id',
                'affected_by_type',
                'quantity',
                'user_id',
                'user_type',
                'happened_at',
                'closing_stock'
            )
            ->with([
                'location:' . $this->getLocationColumnNames(),
                'affectedBy' => function (MorphTo $morphTo) use (
                    $stockTransferItemQueries,
                    $saleItemQueries,
                    $saleReturnItemQueries,
                    $goodsReceivedNoteProductQueries,
                    $stockAdjustmentItemQueries,
                    $voidSaleQueries,
                    $purchaseOrderFulfillmentItemQueries,
                    $orderItemQueries
                ): void {
                    $morphTo->constrain([
                        StockTransferItem::class => $stockTransferItemQueries->getStockTransferWithLocation(),
                        SaleItem::class => $saleItemQueries->getOfflineSaleWithRelation(),
                        SaleReturnItem::class => $saleReturnItemQueries->getOfflineSaleReturnWithRelation(),
                        GoodsReceivedNoteProduct::class => $goodsReceivedNoteProductQueries->getGoodsReceivedNoteWithRelation(),
                        StockAdjustmentItem::class => $stockAdjustmentItemQueries->getStockAdjustmentWithRelation(),
                        VoidSale::class => $voidSaleQueries->getSelectIdAndVoleSaleNumberColumns(),
                        ModelMapping::MERGE_PRODUCT_TRANSACTION->name => function ($query): void {
                            $query->select('id');
                        },
                        PurchaseOrderFulfillmentItem::class => $purchaseOrderFulfillmentItemQueries->getWithPurchaseOrderFulfillmentAndPurchaseOrder(),
                        OrderItem::class => $orderItemQueries->getOrderItemWithOrder(),
                    ]);
                },
            ])
            ->where('product_id', $filterData['product_id'])
            ->where('location_id', $locationId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('happened_at', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->orderBy('happened_at', 'desc')
            ->orderBy('id', 'desc');
    }

    public function getBasicColumns(): string
    {
        return 'id,product_id,location_id,affected_by_type,affected_by_id,happened_at';
    }

    public function filterSellThroughDataBasedOnAffectedType(array $filterData): Closure
    {
        return fn ($query) => $query->where(function ($query) use ($filterData): void {
            $query->where(function ($query): void {
                $query->where(
                    'inventory_updates.affected_by_type',
                    '!=',
                    ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->name
                )->where(
                    'inventory_updates.affected_by_type',
                    '!=',
                    ModelMapping::STOCK_ADJUSTMENT_ITEM->name
                )->where(
                    'inventory_updates.affected_by_type',
                    '!=',
                    ModelMapping::STOCK_TRANSFER_ITEM->name
                )->where(
                    'inventory_updates.affected_by_type',
                    '!=',
                    ModelMapping::PURCHASE_ORDER_FULFILLMENT_ITEM->name
                );
            })
            ->orWhere(function ($query) use ($filterData): void {
                $query->when(
                    in_array(
                        SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value,
                        $filterData['include_by'],
                        false
                    ),
                    function ($query) use ($filterData): void {
                        $query->where(function ($query) use ($filterData): void {
                            $query->where(
                                'inventory_updates.affected_by_type',
                                ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->name
                            )
                                ->where('inventory_updates.quantity', '>', 0)
                                ->where(
                                    $this->locationWiseFilter(
                                        $filterData,
                                        'includes_by_goods_receive_note_in_location_ids',
                                    )
                                );
                        });
                    }
                )->when(
                    in_array(
                        SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value,
                        $filterData['include_by'],
                        false
                    ),
                    function ($query) use ($filterData): void {
                        $query->orWhere(function ($query) use ($filterData): void {
                            $query->where(
                                'inventory_updates.affected_by_type',
                                ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->name
                            )
                                ->where('inventory_updates.quantity', '<', 0)
                                ->where(
                                    $this->locationWiseFilter(
                                        $filterData,
                                        'includes_by_goods_receive_note_out_location_ids',
                                    )
                                );
                        });
                    }
                );
            })->orWhere(function ($query) use ($filterData): void {
                $query->when(
                    in_array(SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value, $filterData['include_by'], false),
                    function ($query) use ($filterData): void {
                        $query->where(function ($query) use ($filterData): void {
                            $query->where(
                                'inventory_updates.affected_by_type',
                                ModelMapping::STOCK_ADJUSTMENT_ITEM->name
                            )
                                ->where('inventory_updates.quantity', '>', 0)
                                ->where(
                                    $this->locationWiseFilter(
                                        $filterData,
                                        'includes_by_stock_adjustment_in_location_ids',
                                    )
                                );
                        });
                    }
                )->when(
                    in_array(
                        SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value,
                        $filterData['include_by'],
                        false
                    ),
                    function ($query) use ($filterData): void {
                        $query->orWhere(function ($query) use ($filterData): void {
                            $query->where(
                                'inventory_updates.affected_by_type',
                                ModelMapping::STOCK_ADJUSTMENT_ITEM->name
                            )
                                ->where('inventory_updates.quantity', '<', 0)
                                ->where(
                                    $this->locationWiseFilter(
                                        $filterData,
                                        'includes_by_stock_adjustment_out_location_ids',
                                    )
                                );
                        });
                    }
                );
            })->orWhere(function ($query) use ($filterData): void {
                $query->when(
                    in_array(SellThroughIncludeTypes::STOCK_TRANSFER_IN->value, $filterData['include_by'], false),
                    function ($query) use ($filterData): void {
                        $query->where(function ($query) use ($filterData): void {
                            $query->where(
                                'inventory_updates.affected_by_type',
                                ModelMapping::STOCK_TRANSFER_ITEM->name
                            )
                                ->where('inventory_updates.quantity', '>', 0)
                                ->where(
                                    $this->locationWiseFilter(
                                        $filterData,
                                        'includes_by_stock_transfer_in_location_ids',
                                    )
                                );
                        });
                    }
                )->when(
                    in_array(SellThroughIncludeTypes::STOCK_TRANSFER_OUT->value, $filterData['include_by'], false),
                    function ($query) use ($filterData): void {
                        $query->orWhere(function ($query) use ($filterData): void {
                            $query->where(
                                'inventory_updates.affected_by_type',
                                ModelMapping::STOCK_TRANSFER_ITEM->name
                            )
                                ->where('inventory_updates.quantity', '<', 0)
                                ->where(
                                    $this->locationWiseFilter(
                                        $filterData,
                                        'includes_by_stock_transfer_out_location_ids',
                                    )
                                );
                        });
                    }
                );
            })->orWhere(function ($query) use ($filterData): void {
                $query->when(
                    in_array(SellThroughIncludeTypes::DELIVERY_ORDER_IN->value, $filterData['include_by'], false),
                    function ($query) use ($filterData): void {
                        $query->where(function ($query) use ($filterData): void {
                            $query->where(
                                'inventory_updates.affected_by_type',
                                ModelMapping::PURCHASE_ORDER_FULFILLMENT_ITEM->name
                            )
                                ->where('inventory_updates.quantity', '>', 0)
                                ->where(
                                    $this->locationWiseFilter(
                                        $filterData,
                                        'includes_by_delivery_order_in_location_ids',
                                    )
                                );
                        });
                    }
                )->when(
                    in_array(SellThroughIncludeTypes::DELIVERY_ORDER_OUT->value, $filterData['include_by'], false),
                    function ($query) use ($filterData): void {
                        $query->orWhere(function ($query) use ($filterData): void {
                            $query->where(
                                'inventory_updates.affected_by_type',
                                ModelMapping::PURCHASE_ORDER_FULFILLMENT_ITEM->name
                            )
                                ->where('inventory_updates.quantity', '<', 0)
                                ->where(
                                    $this->locationWiseFilter(
                                        $filterData,
                                        'includes_by_delivery_order_out_location_ids',
                                    )
                                );
                        });
                    }
                );
            });
        });
    }

    public function getUniqueHappenedAt(): array
    {
        return InventoryUpdate::select(DB::raw('DISTINCT DATE(happened_at) as date'))->orderBy(
            'happened_at',
            'asc'
        )->pluck('date')->toArray();
    }

    public function getDataForSellThroughAggregate(string $date): Collection
    {
        return InventoryUpdate::select('product_id', 'quantity', 'location_id', 'affected_by_type')
            ->whereHas('product', function ($query): void {
                $query->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at');
            })
            ->whereDate('happened_at', '=', $date)
            ->get()
            ->groupBy(['product_id', 'location_id']);
    }

    public function getAffectedDatesForSellThroughAggregate(string $date): array
    {
        return InventoryUpdate::query()
            ->selectRaw('DISTINCT DATE(happened_at) as date')
            ->whereHas('product', function ($query): void {
                $query->where('products.is_non_selling_item', false)
                    ->whereNull('products.deleted_at');
            })
            ->whereDate('updated_at', '>=', $date)
            ->orderBy('date', 'asc')
            ->pluck('date')
            ->unique()
            ->toArray();
    }

    public function getLatestClosingStockForSellThroughAggregate(
        string $date,
        int $productId,
        int $locationId
    ): Collection {
        return InventoryUpdate::select('quantity')
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->whereDate('happened_at', '=', $date)
            ->orderByDesc('id')
            ->orderByDesc('happened_at')
            ->get();
    }

    public function getByLocationAndProductId(int $productId, int $locationId, string $type): ?InventoryUpdate
    {
        return InventoryUpdate::select(
            'id',
            'affected_by_type',
            DB::raw('COALESCE(happened_at, created_at) as happened_at')
        )
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('affected_by_type', $type)
            ->orderBy('happened_at', 'asc')
            ->first();
    }

    public function getYesterdayInventoryUpdateWithInventory(string $date): Collection
    {
        return InventoryUpdate::query()
            ->select('id', 'product_id', 'location_id')
            ->whereDate('happened_at', $date)
            ->groupBy(['product_id', 'location_id'])
            ->get();
    }

    private function locationWiseFilter(array $filterData, string $locationKey): Closure
    {
        return function ($query) use ($filterData, $locationKey): void {
            $query->when(
                array_key_exists($locationKey, $filterData) &&
                [] !== $filterData[$locationKey],
                function ($query) use ($filterData, $locationKey): void {
                    $query->whereIntegerInRaw('inventory_updates.location_id', $filterData[$locationKey]);
                }
            );
        };
    }

    public function getAllByCompanyId(int $companyId, ?array $dateRange, int $perPage = 1000): LengthAwarePaginator
    {
        $query = InventoryUpdate::query()
            ->select([
                'id',
                'product_id as product_variant_id',
                'location_id',
                DB::raw('DATE(happened_at) as happened_at_date'),
                DB::raw('SUM(closing_stock) as stock'),
            ])
            ->whereHas('location', function ($query) use ($companyId): void {
                $query->where('type_id', LocationTypes::STORE->value)
                      ->where('company_id', $companyId);
            })
            ->whereHas('product', function ($query) use ($companyId): void {
                $query->where('status', Statuses::ACTIVE->value)
                      ->where('type_id', ProductTypes::REGULAR_PRODUCT->value)
                      ->where('company_id', $companyId);
            });

        if ($dateRange && isset($dateRange['start_date'], $dateRange['end_date'])) {
            $query->whereBetween(DB::raw('DATE(happened_at)'), [$dateRange['start_date'], $dateRange['end_date']]);
        }

        return $query->groupBy(['product_id', 'location_id', 'happened_at'])
            ->paginate($perPage);
    }
}
