<?php

declare(strict_types=1);

namespace App\Domains\Inventory;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\AutomatedNotification\AutomatedNotificationQueries;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\City\CityQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Inventory\Enums\Types;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\SellingTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Tag\TagQueries;
use App\Domains\TransitStock\TransitStockQueries;
use App\Models\AutomatedNotification;
use App\Models\Inventory;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryQueries
{
    public function fetchOrCreate(int $locationId, int $productId): Inventory
    {
        return Inventory::firstOrCreate([
            'location_id' => $locationId,
            'product_id' => $productId,
        ], [
            'stock' => 0,
            'reserved_stock' => 0,
        ]);
    }

    public function getProductCountOutOfStock(int $locationId, int $companyId): int
    {
        return Inventory::query()
            ->whereHas('product', function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->where('status', Statuses::ACTIVE->value);
            })
            ->where('location_id', $locationId)
            ->where('stock', '<=', 0)
            ->count();
    }

    public function getFirstForEcommerceSync(int $companyId, int $saleChannelId, int $locationId): ?Inventory
    {
        return Inventory::select('id', 'product_id')
            ->whereHas('productChannelReferences', function ($query) use ($saleChannelId, $companyId): void {
                $query->select('id', 'product_id')
                    ->where('sale_channel_id', $saleChannelId)
                    ->whereHas('product', function ($subQuery) use ($companyId): void {
                        $subQuery->where('company_id', $companyId);
                    });
            })
            ->where('location_id', $locationId)
            ->orderBy('id', 'asc')
            ->first();
    }

    public function getLastForEcommerceSync(int $companyId, int $saleChannelId, int $locationId): ?Inventory
    {
        return Inventory::select('id', 'product_id')
            ->whereHas('productChannelReferences', function ($query) use ($saleChannelId, $companyId): void {
                $query->select('id', 'product_id')
                    ->where('sale_channel_id', $saleChannelId)
                    ->whereHas('product', function ($subQuery) use ($companyId): void {
                        $subQuery->where('company_id', $companyId);
                    });
            })
            ->where('location_id', $locationId)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getInventoryEcommerceChannelByStartAndEndId(
        int $companyId,
        int $startId,
        int $endId,
        int $saleChannelId,
        int $locationId
    ): Collection {
        return Inventory::select('id', 'product_id', 'location_id', 'stock')
            ->whereHas('productChannelReferences', function ($query) use ($saleChannelId, $companyId): void {
                $query->where('sale_channel_id', $saleChannelId)
                    ->whereHas('product', function ($subQuery) use ($companyId): void {
                        $subQuery->where('company_id', $companyId);
                    });
            })
            ->where('location_id', $locationId)
            ->where('id', '>=', $startId)
            ->where('id', '<=', $endId)
            ->get();
    }

    public function getInventoryBy(int $locationId, int $productId): Inventory
    {
        return Inventory::query()
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->firstOrFail();
    }

    public function getInventoryById(int $inventoryId): Inventory
    {
        return Inventory::query()
            ->select('id', 'location_id', 'product_id', 'stock', 'reserved_stock')
            ->lockForUpdate()
            ->findOrFail($inventoryId);
    }

    public function getInventoriesByLocation(int $locationId, array $filterData): LengthAwarePaginator
    {
        return Inventory::select('id', 'product_id', 'stock', 'reserved_stock', 'updated_at', 'created_at')
            ->where('location_id', $locationId)
            ->whereHas('product', function ($query): void {
                $query->select('id')
                    ->isAvailableInEcommerce();
            })
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('product_id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getInventoriesByProductIds(int $locationId, array $productIds): Collection
    {
        return Inventory::select('id', 'product_id', 'stock', 'reserved_stock')
            ->where('location_id', $locationId)
            ->whereIntegerInRaw('product_id', $productIds)
            ->lockForUpdate()
            ->get();
    }

    public function getInventoriesWithProductByProductIds(int $locationId, array $productIds): Collection
    {
        $productQueries = resolve(ProductQueries::class);

        return Inventory::select('id', 'product_id', 'stock', 'reserved_stock')
            ->with('product:' . $productQueries->getIdAndUpc())
            ->where('location_id', $locationId)
            ->whereIntegerInRaw('product_id', $productIds)
            ->get();
    }

    public function getInventoriesWithProductByProductUpcs(int $locationId, array $upcs): Collection
    {
        $productQueries = resolve(ProductQueries::class);

        return Inventory::select('id', 'product_id', 'stock', 'reserved_stock')
            ->with('product:' . $productQueries->getIdAndUpc())
            ->where('location_id', $locationId)
            ->whereIn('product_id', function ($query) use ($upcs): void {
                $query->select('id')
                    ->from('products')
                    ->whereIn('upc', $upcs);
            })
            ->get();
    }

    public function refreshInventory(Inventory $inventory): Inventory
    {
        return $inventory->refresh();
    }

    public function increaseStock(Inventory $inventory, float $stock): float
    {
        $inventory->increment('stock', $stock);

        return (float) $this->refreshInventory($inventory)->stock;
    }

    public function increaseOnlyReservedStockAndGetSumOfReservedAndStock(Inventory $inventory, float $stock): float
    {
        $inventory->increment('reserved_stock', $stock);

        $inventory = $this->refreshInventory($inventory);

        return (float) $inventory->stock + $inventory->reserved_stock;
    }

    public function decreaseStock(Inventory $inventory, string|float $stock): float
    {
        $inventory->decrement('stock', (float) $stock);

        return (float) $this->refreshInventory($inventory)->stock;
    }

    public function increaseStockAndReservedStockAndDeleteOldInventoryData(
        Inventory $inventory,
        Inventory $oldInventory,
        float $stock,
        float $reservedStock
    ): void {
        $inventory->increment('stock', $stock);
        $inventory->increment('reserved_stock', $reservedStock);

        $oldInventory->delete();
    }

    public function updateProductId(Inventory $oldInventory, int $newProductId): void
    {
        $oldInventory->product_id = $newProductId;
        $oldInventory->save();
    }

    public function increaseReservedStock(Inventory $inventory, float $stock): void
    {
        $inventory->decrement('stock', $stock);
        $inventory->increment('reserved_stock', $stock);
    }

    public function decreaseReservedStock(Inventory $inventory, float $stock): Inventory
    {
        $inventory->decrement('reserved_stock', $stock);

        return $this->refreshInventory($inventory);
    }

    public function revertReservedStock(Inventory $inventory, float $stock): void
    {
        $inventory->increment('stock', $stock);
        $inventory->decrement('reserved_stock', $stock);
    }

    public function getByProductIdWithInventoryUnits(int $productId): Collection
    {
        $inventoryUnitQueries = new InventoryUnitQueries();

        return Inventory::select('id', 'product_id', 'location_id', 'stock', 'reserved_stock')
            ->where('product_id', $productId)
            ->with('inventoryUnits:' . $inventoryUnitQueries->getBasicColumnNames())
            ->get();
    }

    public function getById(int $inventoryId): Inventory
    {
        return Inventory::select('id', 'location_id', 'product_id', 'updated_at', 'stock')
            ->findOrFail($inventoryId);
    }

    public function getByProductIdsAndLocationWithInventoryUnits(int $locationId, array $productIds): Collection
    {
        $inventoryUnitQueries = new InventoryUnitQueries();

        return Inventory::select('id', 'product_id', 'location_id', 'stock', 'reserved_stock')
            ->with('inventoryUnits:' . $inventoryUnitQueries->getBasicColumnNames())
            ->where('location_id', $locationId)
            ->whereIntegerInRaw('product_id', $productIds)
            ->get();
    }

    public function getByProductIdsAndLocation(int $locationId, array $productIds): Collection
    {
        return Inventory::select('id', 'product_id', 'location_id', 'stock', 'reserved_stock')
            ->where('location_id', $locationId)
            ->whereIntegerInRaw('product_id', $productIds)
            ->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,product_id,stock,reserved_stock,location_id';
    }

    public function getColumnForReservedStock(): string
    {
        return 'id,product_id,location_id,stock,reserved_stock';
    }

    public function inventoryReportsList(array $filterData, int $companyId): LengthAwarePaginator
    {
        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        $relations = [
            'location:' . $this->getMorphLocationBasicColumns(),
            'product:' . $productQueries->getColumnsForInventoryReports(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.masterProduct.brand:' . $brandQueries->getIdAndNameColumnNames(),
                'product.masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.brand:' . $brandQueries->getBasicColumnNames(),
                'product.categories:' . $categoryQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return $this->commonInventoryReportListQuery($filterData, $companyId)
            ->with($relations)
            ->withSum('transitStocks', 'quantity')
            ->paginate($filterData['per_page']);
    }

    public function getFilteredTotalsForInventoryReport(array $filterData, int $companyId): array
    {
        $inventoryReportTotalCountQuery = $this->inventoryReportTotalCountQuery($filterData, $companyId);
        $totalCount = $inventoryReportTotalCountQuery->first();

        return [
            /* @phpstan-ignore-next-line */
            'total_available_stock' => $totalCount?->available_stock,
            /* @phpstan-ignore-next-line */
            'total_current_stock' => $totalCount?->current_stock,
            /* @phpstan-ignore-next-line */
            'total_reserved_stock' => $totalCount?->reserve_stock,
            /* @phpstan-ignore-next-line */
            'total_transit_stock' => $totalCount?->transit_stock,
        ];
    }

    public function getMorphLocationBasicColumns(): string
    {
        return 'id,name,type_id';
    }

    public function getMorphLocationBasicColumnsForEcommerce(): string
    {
        return 'id,name,address_line_1,address_line_2,city_id,area_code';
    }

    public function getMorphLocationColumnsForApi(): string
    {
        return 'id,name,code';
    }

    public function getColumnForBatchExpiryReport(): string
    {
        return 'id,location_id';
    }

    public function getColumnLocation(): string
    {
        return 'id,location_id';
    }

    public function searchLocationByName(string $searchText): Closure
    {
        return fn ($query) => $query->where('name', 'like', '%' . $searchText . '%');
    }

    public function updateStockBy(int $locationId, int $productId, float $closingStock): int
    {
        $inventory = $this->fetchOrCreate($locationId, $productId);

        $inventory->stock = $closingStock - $inventory->reserved_stock;
        $inventory->save();

        return $inventory->id;
    }

    public function inventoryListsForExport(array $filterData, int $companyId): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $transitStockQueries = resolve(TransitStockQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        $relations = [
            'location:' . $this->getMorphLocationBasicColumns(),
            'product:' . $productQueries->getColumnsForInventoryReports(),
            'transitStocks:' . $transitStockQueries->getQuantityColumn(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.masterProduct.brand:' . $brandQueries->getIdAndNameColumnNames(),
                'product.masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                'product.masterProduct.tags:' . $tagQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.brand:' . $brandQueries->getBasicColumnNames(),
                'product.categories:' . $categoryQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
                'product.tags:' . $tagQueries->getBasicColumnNames(),
            ]);
        }

        return $this->commonInventoryReportListQuery($filterData, $companyId)
            ->with($relations)
            ->withSum('transitStocks', 'quantity')
            ->get();
    }

    public function filterByLocationTypeName(string $searchText): Closure
    {
        return fn ($query) => $query->where(function ($query) use ($searchText): void {
            $query->whereIn('location_id', function ($query) use ($searchText): void {
                $query->select('id')
                    ->from('locations')
                    ->where('type_id', LocationTypes::STORE->value)
                    ->where('name', 'like', '%' . $searchText . '%');
            })
                ->whereIntegerInRaw('location_id', function ($query) use ($searchText): void {
                    $query->select('id')
                        ->from('locations')
                        ->where('type_id', LocationTypes::WAREHOUSE->value)
                        ->where('name', 'like', '%' . $searchText . '%');
                });
        });
    }

    public function getInventoryStocksForApplication(
        array $filterData,
        ?int $typeId,
        int $companyId,
        int $productId
    ): LengthAwarePaginator {
        return Inventory::query()
            ->select('id', 'stock', 'reserved_stock', 'location_id', 'product_id')
            ->with('location:' . $this->getMorphLocationBasicColumns())
            ->whereHas('location', function ($query) use ($companyId, $typeId): void {
                $query->where('company_id', $companyId)->where('type_id', $typeId);
            })
            ->where('product_id', $productId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('stock', 'like', '%' . $filterData['search_text'] . '%')
                        ->orWhereHas('location', $this->searchLocationByNameAndCode($filterData['search_text']));
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function searchLocationByNameAndCode(string $searchText): Closure
    {
        return fn ($query) => $query
            ->whereAny(['name', 'code'], 'LIKE', '%' . $searchText . '%');
    }

    public function getNoStockItems(array $filterData, int $companyId, bool $refresh = false): int
    {
        $cacheKey = 'no-Stock-Items-' . $companyId . '-' . $filterData['location_id'];

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return (int) Cache::remember(
            $cacheKey,
            900,
            fn (): int => $this->getStockCountQuery($filterData, $companyId)
                ->where('stock', 0)
                ->count()
        );
    }

    public function getNegativeStockItems(array $filterData, int $companyId, bool $refresh = false): int
    {
        $cacheKey = 'negative-Stock-Items-' . $companyId . '-' . $filterData['location_id'];

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return (int) Cache::remember(
            $cacheKey,
            900,
            fn (): int => $this->getStockCountQuery($filterData, $companyId)
                ->where('stock', '<', 0)
                ->count()
        );
    }

    public function getCompanyLowStockItems(array $filterData, int $companyId, bool $refresh = false): float|int
    {
        $automatedNotificationQueries = resolve(AutomatedNotificationQueries::class);
        $automatedNotification = $automatedNotificationQueries->getLowStockNotificationByCompanyIdAndType($companyId);

        if (! $automatedNotification) {
            return 0;
        }

        $cacheKey = 'company-low-Stock-Items-' . $companyId . '-' . $filterData['location_id'];

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        $count = Cache::remember(
            $cacheKey,
            900,
            fn () => DB::table('inventories as inv')
                ->selectRaw('COUNT(inv.id) as total_count')
                ->join('products', function ($query) use ($companyId): void {
                    $query->on('products.id', '=', 'inv.product_id')
                        ->where('products.company_id', $companyId)
                        ->where('products.status', Statuses::ACTIVE->value)
                        ->where('products.is_non_inventory', false);
                })
                ->when($filterData['location_id'] > 0, function ($query) use ($filterData): void {
                    $query->where('inv.location_id', $filterData['location_id']);
                })
                ->where('inv.stock', '>', 0)
                ->where('inv.stock', '<=', $automatedNotification->low_stock_alert_threshold)
                ->whereNotIn('inv.id', function ($query) use ($companyId): void {
                    $query->select('inv.id')
                        ->from('inventories as inv')
                        ->join('automated_notification_products as anp', function ($join) use ($companyId): void {
                            $join->on('anp.location_id', '=', 'inv.location_id')
                                ->on('anp.product_id', '=', 'inv.product_id')
                                ->whereIn('anp.automated_notification_id', function ($query) use ($companyId): void {
                                    $query->select('id')
                                        ->from('automated_notifications')
                                        ->where('company_id', $companyId)
                                        ->where('type_id', AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value);
                                });
                        });
                })
                ->whereNotIn('inv.id', function ($query) use ($companyId): void {
                    $query->select('inv.id')
                        ->from('inventories as inv')
                        ->join('automated_notification_stores as ans', function ($join) use ($companyId): void {
                            $join->on('ans.location_id', '=', 'inv.location_id')
                                ->whereIn('ans.automated_notification_id', function ($query) use ($companyId): void {
                                    $query->select('id')
                                    ->from('automated_notifications')
                                    ->where('company_id', $companyId)
                                    ->where('type_id', AutomatedNotificationTypes::LOW_STOCK_LOCATION->value);
                                });
                        })
                        ->whereNotIn('inv.id', function ($subQuery) use ($companyId): void {
                            $subQuery->select('inv.id')
                                ->from('inventories as inv')
                                ->join('automated_notification_products as anp', function ($join) use (
                                    $companyId
                                ): void {
                                    $join->on('anp.location_id', '=', 'inv.location_id')
                                        ->on('anp.product_id', '=', 'inv.product_id')
                                        ->whereIn('anp.automated_notification_id', function ($query) use (
                                            $companyId
                                        ): void {
                                            $query->select('id')
                                                ->from('automated_notifications')
                                                ->where('company_id', $companyId)
                                                ->where(
                                                    'type_id',
                                                    AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value
                                                );
                                        });
                                });
                        });
                })
            ->first()
        );

        /* @phpstan-ignore-next-line */
        return null !== $count ? (int) $count->total_count : 0;
    }

    public function getLocationLowStockItems(array $filterData, int $companyId, bool $refresh = false): float|int
    {
        $cacheKey = 'location-low-Stock-Items-' . $companyId . '-' . $filterData['location_id'];

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        $count = Cache::remember(
            $cacheKey,
            900,
            fn () => DB::table('inventories as inv')
                ->selectRaw('COUNT(inv.id) as total_count')
                ->join('products', function ($query) use ($companyId): void {
                    $query->on('products.id', '=', 'inv.product_id')
                        ->where('products.company_id', $companyId)
                        ->where('products.status', Statuses::ACTIVE->value)
                        ->where('products.is_non_inventory', false);
                })
                ->when($filterData['location_id'] > 0, function ($query) use ($filterData): void {
                    $query->where('inv.location_id', $filterData['location_id']);
                })
                ->join('automated_notification_stores as ans', function ($join): void {
                    $join->on('ans.location_id', '=', 'inv.location_id');
                })
                ->where('inv.stock', '>', 0)
                ->whereColumn('inv.stock', '<=', 'ans.low_stock_alert_threshold')
                ->whereIn('ans.automated_notification_id', function ($query) use ($companyId): void {
                    $query->select('id')
                        ->from('automated_notifications')
                        ->where('company_id', $companyId)
                        ->where('type_id', AutomatedNotificationTypes::LOW_STOCK_LOCATION->value);
                })
                ->whereNotIn('inv.id', function ($query) use ($companyId): void {
                    $query->select('inv.id')
                        ->from('inventories as inv')
                        ->join('automated_notification_products as anp', function ($join) use ($companyId): void {
                            $join->on('anp.location_id', '=', 'inv.location_id')
                                ->on('anp.product_id', '=', 'inv.product_id')
                                ->whereIn('anp.automated_notification_id', function ($query) use ($companyId): void {
                                    $query->select('id')
                                        ->from('automated_notifications')
                                        ->where('company_id', $companyId)
                                        ->where('type_id', AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value);
                                });
                        });
                })
                ->first()
        );

        /* @phpstan-ignore-next-line */
        return null !== $count ? (int) $count->total_count : 0;
    }

    public function getProductLowStockItems(array $filterData, int $companyId, bool $refresh = false): float|int
    {
        $cacheKey = 'product-low-Stock-Items-' . $companyId . '-' . $filterData['location_id'];

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        $count = Cache::remember(
            $cacheKey,
            900,
            fn () => DB::table('inventories as inv')
                ->selectRaw('COUNT(inv.id) as total_count')
                ->join('products', function ($query) use ($companyId): void {
                    $query->on('products.id', '=', 'inv.product_id')
                        ->where('products.company_id', $companyId)
                        ->where('products.status', Statuses::ACTIVE->value)
                        ->where('products.is_non_inventory', false);
                })
                ->when($filterData['location_id'] > 0, function ($query) use ($filterData): void {
                    $query->where('inv.location_id', $filterData['location_id']);
                })
                ->join('automated_notification_products as anp', function ($join) use ($companyId): void {
                    $join->on('anp.location_id', '=', 'inv.location_id')
                        ->on('anp.product_id', '=', 'inv.product_id')
                        ->whereIn('anp.automated_notification_id', function ($query) use ($companyId): void {
                            $query->select('id')
                                ->from('automated_notifications')
                                ->where('company_id', $companyId)
                                ->where('type_id', AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value);
                        });
                })
                ->where('inv.stock', '>', 0)
                ->whereColumn('inv.stock', '<=', 'anp.low_stock_alert_threshold')
                ->first()
        );

        /* @phpstan-ignore-next-line */
        return null !== $count ? (int) $count->total_count : 0;
    }

    public function getByProductId(int $productId): Collection
    {
        return Inventory::query()
            ->with(
                [
                    'inventoryUnits',
                    'reservedStocksWithDeleted',
                    'transitStocksWithDeleted',
                    'saleItemUnits',
                    'stockTransferItemUnitsWithDeleted',
                    'orderItemUnits',
                    'purchaseOrderFulfillmentItemUnits',
                ]
            )
            ->where('product_id', $productId)
            ->get();
    }

    public function getInventoriesByProductId(int $productId): Collection
    {
        return Inventory::query()
            ->select('id', 'product_id', 'location_id', 'stock', 'reserved_stock')
            ->where('product_id', $productId)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getInventoryStock(int $productId, int $locationId): ?Inventory
    {
        return Inventory::query()
            ->select('id', 'stock')
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->first();
    }

    public function updateStock(Inventory $inventory, float $stock): void
    {
        $inventory->stock = $stock;
        $inventory->save();
    }

    public function getInventoryByProductIdWithLocation(array $filterData, int $companyId): Collection
    {
        return Inventory::query()->select('id', 'product_id', 'location_id', 'stock')
            ->whereHas('product', function ($query) use ($companyId): void {
                $query->select('id')
                    ->where('company_id', $companyId)
                    ->where('status', Statuses::ACTIVE->value);
            })
            ->with('location:' . $this->getMorphLocationColumnsForApi())
            ->where('product_id', $filterData['product_id'])
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            })
            ->get();
    }

    public function getStoresHavingInventoriesByProductIds(int $companyId, array $cartDetails): Collection
    {
        $stockForEachProductCondition = function ($query) use ($cartDetails): void {
            foreach ($cartDetails as $key => $cartDetail) {
                $conditionConnectorType = 0 === $key ? 'where' : 'orWhere';

                $query->{$conditionConnectorType}(function ($query) use ($cartDetail): void {
                    $query->where('product_id', $cartDetail['product_id'])
                        ->where('stock', '>=', $cartDetail['quantity']);
                });
            }
        };

        $cityQueries = resolve(CityQueries::class);

        return Inventory::query()
            ->select('id', 'product_id', 'location_id', 'created_at', 'updated_at')
            ->with(
                'location:' . $this->getMorphLocationBasicColumnsForEcommerce(),
                'location.city:' . $cityQueries->getBasicColumnNames(),
            )
            ->whereHas('product', function ($query) use ($companyId): void {
                $query->select('id')
                    ->where('company_id', $companyId)
                    ->where('status', Statuses::ACTIVE->value);
            })
            ->where($stockForEachProductCondition)
            ->where('stock', '>', 0)
            ->groupBy('location_id')
            ->get();
    }

    public function getInventoryByLocationAndType(int $locationId): Closure
    {
        return fn ($query) => $query->select('id', 'product_id', 'location_id', 'stock')
            ->where('location_id', $locationId);
    }

    public function getInventoryByLocationAndTypeWithStockType(
        array $filteredData,
        int $locationId,
        int $companyId,
        ?AutomatedNotification $automatedNotification
    ): Closure {
        return fn ($query) => $query->select(
            'id',
            'product_id',
            'location_id',
            'stock',
            DB::raw('(CASE
                    WHEN inventories.stock <= 0 THEN "no stock"
                    WHEN inventories.id IN (' .
                        $this->getRawSqlFromClosure(
                            $this->getLowStockInventoryIdQueryForProduct($filteredData, $companyId)
                        ).
                    ') OR inventories.id IN (' .
                        $this->getRawSqlFromClosure(
                            $this->getLowStockInventoryIdQueryForStore($filteredData, $companyId)
                        ).
                    ') ' . ($automatedNotification instanceof AutomatedNotification ?
                        'OR inventories.id IN (' .
                            $this->getRawSqlFromClosure(
                                $this->getLowStockInventoryIdQueryForCompany(
                                    $automatedNotification,
                                    $filteredData,
                                    $companyId
                                )
                            ).
                        ')' : '') .
                    ' THEN "low stock"
                    ELSE "in stock"
                END) AS stock_label')
        )
        ->where('location_id', $locationId);
    }

    public function getActiveProductsByUpcAndStoreCode(array $productUpcWithStoreCode, int $companyId): Collection
    {
        $preparedProducts = collect();
        $productQueries = resolve(ProductQueries::class);

        $chunkProductsData = array_chunk($productUpcWithStoreCode, 1);

        foreach ($chunkProductsData as $chunkProductData) {
            foreach ($chunkProductData as $data) {
                $products = Inventory::query()
                    ->select('id', 'product_id', 'location_id', 'stock')
                    ->with([
                        'product:' . $productQueries->getColumnsForPromoterCommissionReport(),
                        'location:' . $this->getMorphLocationColumnsForApi(),
                    ])
                    ->whereHas('product', function ($query) use ($companyId, $data): void {
                        $query->select('id', 'name', 'upc')
                            ->where('company_id', $companyId)
                            ->where('status', Statuses::ACTIVE->value)
                            ->whereCaseSensitive('upc', $data['upc']);
                    })
                    ->whereHas('location', function ($query) use ($companyId, $data): void {
                        $query->select('id', 'name', 'code')
                            ->where('company_id', $companyId)
                            ->whereCaseSensitive('code', $data['code']);
                    })
                    ->get();

                $preparedProducts->push($products);
            }
        }

        return $preparedProducts->collapse();
    }

    public function getInventoryByStoreAndProduct(
        int $locationId,
        int $productId,
        int $companyId,
        int $lowStockAlertThreshold
    ): ?Inventory {
        $productQueries = resolve(ProductQueries::class);

        return Inventory::query()
            ->select('id', 'product_id', 'location_id')
            ->with([
                'product:' . $productQueries->getIdANdNameColumns(),
                'location:' . $this->getMorphLocationBasicColumns(),
            ])
            ->whereHas('product', function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->where('status', Statuses::ACTIVE->value);
            })
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('stock', '>', 0)
            ->where('stock', '<=', $lowStockAlertThreshold)
            ->first();
    }

    public function getIdColumn(): string
    {
        return 'id';
    }

    public function getProductsCountWithExcludeInventoryAndProduct(
        int $companyId,
        int $lowStockAlertThreshold,
        int $locationId,
        array $excludeProductIds,
        array $excludeInventoryIds
    ): Collection {
        return Inventory::query()
            ->select('id', 'product_id')
            ->whereHas('product', function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->where('status', Statuses::ACTIVE->value);
            })
            ->whereNotIn('id', $excludeInventoryIds)
            ->where('location_id', $locationId)
            ->where('stock', '>', 0)
            ->where('stock', '<=', $lowStockAlertThreshold)
            ->whereNotIn('product_id', $excludeProductIds)
            ->get();
    }

    private function getStockCountQuery(array $filterData, int $companyId): Builder
    {
        return Inventory::select('id')
            ->when($filterData['location_id'], function ($query) use ($filterData): void {
                $query->where('location_id', $filterData['location_id']);
            })
            ->whereHas('product', function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->where('status', Statuses::ACTIVE->value)
                    ->where('is_non_inventory', false);
            });
    }

    public function getLowStockInventoryIdQueryForProduct(array $filterData, int $companyId): Closure
    {
        return fn ($query) => $query
            ->select('inventories.id')
            ->from('inventories')
            ->join('products', function ($query) use ($companyId): void {
                $query->on('products.id', '=', 'inventories.product_id')
                    ->where('products.company_id', $companyId)
                    ->where('products.status', Statuses::ACTIVE->value)
                    ->where('products.is_non_inventory', false);
            })
            ->join('automated_notification_products as anp', function ($join): void {
                $join->on('anp.location_id', '=', 'inventories.location_id')
                        ->on('anp.product_id', '=', 'inventories.product_id');
            })
            ->whereIn('anp.automated_notification_id', function ($query) use ($companyId): void {
                $query->select('id')
                    ->from('automated_notifications')
                    ->where('company_id', $companyId)
                    ->where('type_id', AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value);
            })
            ->where('inventories.stock', '>', 0)
            ->where('inventories.stock', '<=', DB::raw('anp.low_stock_alert_threshold'))
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('inventories.location_id', (array) $filterData['location_ids']);
            });
    }

    public function getLowStockInventoryIdQueryForStore(array $filterData, int $companyId): Closure
    {
        return fn ($query) => $query
            ->select('inventories.id')
            ->from('inventories')
            ->join('products', function ($query) use ($companyId): void {
                $query->on('products.id', '=', 'inventories.product_id')
                    ->where('products.company_id', $companyId)
                    ->where('products.status', Statuses::ACTIVE->value)
                    ->where('products.is_non_inventory', false);
            })
            ->join('automated_notification_stores as ans', function ($join): void {
                $join->on('ans.location_id', '=', 'inventories.location_id');
            })
            ->where('inventories.stock', '>', 0)
            ->where('inventories.stock', '<=', DB::raw('ans.low_stock_alert_threshold'))
            ->whereIn('ans.automated_notification_id', function ($query) use ($companyId): void {
                $query->select('id')
                    ->from('automated_notifications')
                    ->where('company_id', $companyId)
                    ->where('type_id', AutomatedNotificationTypes::LOW_STOCK_LOCATION->value);
            })
            ->whereNotIn('inventories.id', function ($query) use ($companyId): void {
                $query->select('inventories.id')
                    ->from('inventories')
                    ->join('automated_notification_products as anp', function ($join): void {
                        $join->on('anp.location_id', '=', 'inventories.location_id')
                                ->on('anp.product_id', '=', 'inventories.product_id');
                    })
                    ->whereIn('anp.automated_notification_id', function ($query) use ($companyId): void {
                        $query->select('id')
                            ->from('automated_notifications')
                            ->where('company_id', $companyId)
                            ->where('type_id', AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value);
                    });
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('inventories.location_id', (array) $filterData['location_ids']);
            });
    }

    public function getLowStockInventoryIdQueryForCompany(
        AutomatedNotification $automatedNotification,
        array $filterData,
        int $companyId
    ): Closure {
        return fn ($query) => $query
            ->select('inventories.id')
            ->from('inventories')
            ->join('products', function ($query) use ($companyId): void {
                $query->on('products.id', '=', 'inventories.product_id')
                    ->where('products.company_id', $companyId)
                    ->where('products.status', Statuses::ACTIVE->value)
                    ->where('products.is_non_inventory', false);
            })
            ->where('inventories.stock', '>', 0)
            ->where('inventories.stock', '<=', $automatedNotification->low_stock_alert_threshold)
            ->whereNotIn('inventories.id', function ($query) use ($companyId): void {
                $query->select('inventories.id')
                ->from('inventories')
                ->join('automated_notification_products as anp', function ($join): void {
                    $join->on('anp.location_id', '=', 'inventories.location_id')
                            ->on('anp.product_id', '=', 'inventories.product_id');
                })
                ->whereIn('anp.automated_notification_id', function ($query) use ($companyId): void {
                    $query->select('id')
                        ->from('automated_notifications')
                        ->where('company_id', $companyId)
                        ->where('type_id', AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value);
                });
            })
            ->whereNotIn('inventories.id', function ($query) use ($companyId): void {
                $query->select('inventories.id')
                    ->from('inventories')
                    ->join('automated_notification_stores as ans', function ($join): void {
                        $join->on('ans.location_id', '=', 'inventories.location_id');
                    })
                    ->whereIn('ans.automated_notification_id', function ($query) use ($companyId): void {
                        $query->select('id')
                            ->from('automated_notifications')
                            ->where('company_id', $companyId)
                            ->where('type_id', AutomatedNotificationTypes::LOW_STOCK_LOCATION->value);
                    })
                    ->whereNotIn('inventories.id', function ($query) use ($companyId): void {
                        $query->select('inventories.id')
                            ->from('inventories')
                            ->join('automated_notification_products as anp', function ($join): void {
                                $join->on('anp.location_id', '=', 'inventories.location_id')
                                    ->on('anp.product_id', '=', 'inventories.product_id');
                            })
                            ->whereIn('anp.automated_notification_id', function ($query) use ($companyId): void {
                                $query->select('id')
                                ->from('automated_notifications')
                                ->where('company_id', $companyId)
                                ->where('type_id', AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value);
                            });
                    });
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('inventories.location_id', (array) $filterData['location_ids']);
            });
    }

    private function commonInventoryReportListQuery(array $filterData, int $companyId): Builder
    {
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $automatedNotificationQueries = resolve(AutomatedNotificationQueries::class);
        $automatedNotification = $automatedNotificationQueries->getLowStockNotificationByCompanyIdAndType($companyId);

        return Inventory::select(
            'inventories.id',
            'inventories.product_id',
            'inventories.location_id',
            'inventories.stock as available_stock',
            'inventories.reserved_stock',
            'inventories.created_at',
            'inventories.updated_at'
        )
            ->selectRaw('(stock + reserved_stock) as current_stock')
            ->whereHas('product', function ($query) use ($companyId, $filterData): void {
                $query->select('products.id')
                    ->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id')
                    ->where('products.company_id', $companyId)
                    ->when(config('app.product_variant'), function ($query): void {
                        $query->where('master_products.is_non_inventory', false);
                    }, function ($query): void {
                        $query->where('products.is_non_inventory', false);
                    })
                    ->when(
                        isset($filterData['selling_type']) && (int) $filterData['selling_type'] === SellingTypes::SELLING->value,
                        function ($query): void {
                            $column = config(
                                'app.product_variant'
                            ) ? 'master_products.is_non_selling_item' : 'products.is_non_selling_item';
                            $query->where($column, false);
                        }
                    )
                    ->when(
                        isset($filterData['selling_type']) && (int) $filterData['selling_type'] === SellingTypes::NON_SELLING->value,
                        function ($query): void {
                            $column = config(
                                'app.product_variant'
                            ) ? 'master_products.is_non_selling_item' : 'products.is_non_selling_item';
                            $query->where($column, true);
                        }
                    )
                    ->when(ProductStatuses::ACTIVE->value === $filterData['status'], function ($query): void {
                        $query->where('products.status', Statuses::ACTIVE->value);
                    })
                    ->when(ProductStatuses::ARCHIVED->value === $filterData['status'], function ($query): void {
                        $query->where('products.status', Statuses::ARCHIVED->value);
                    });
            })
            ->whereIn('product_id', function ($query) use ($companyId): void {
                $query->select('id')
                    ->from('products')
                    ->where('products.company_id', $companyId);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData, $productQueries): void {
                $query->where(function ($query) use ($productQueries, $filterData): void {
                    $query->whereIn(
                        'inventories.product_id',
                        $productQueries->searchByCompoundProductNameUpcAndSku($filterData['search_text'])
                    )
                        ->orWhere($this->filterByLocationTypeName($filterData['search_text']));
                });
            })
            ->when((int) $filterData['stock_type'] === Types::NO_STOCK->value, function ($query): void {
                $query->where('inventories.stock', 0);
            })
            ->when((int) $filterData['stock_type'] === Types::NEGATIVE_STOCK->value, function ($query): void {
                $query->where('inventories.stock', '<', 0);
            })
            ->when(
                (int) $filterData['stock_type'] === Types::LOW_STOCK_PRODUCT->value,
                function ($query) use ($companyId, $filterData): void {
                    $query
                        ->whereIn(
                            'inventories.id',
                            $this->getLowStockInventoryIdQueryForProduct($filterData, $companyId)
                        );
                }
            )
            ->when(
                (int) $filterData['stock_type'] === Types::LOW_STOCK_LOCATION->value,
                function ($query) use ($companyId, $filterData): void {
                    $query
                        ->whereIn(
                            'inventories.id',
                            $this->getLowStockInventoryIdQueryForStore($filterData, $companyId)
                        );
                }
            )
            ->when(
                (int) $filterData['stock_type'] === Types::LOW_STOCK_COMPANY->value,
                function ($query) use ($companyId, $filterData, $automatedNotification): void {
                    $query
                        ->when(
                            null !== $automatedNotification,
                            function ($query) use ($companyId, $filterData, $automatedNotification): void {
                                $query->whereIn(
                                    'inventories.id',
                                    $this->getLowStockInventoryIdQueryForCompany(
                                        /* @phpstan-ignore-next-line */
                                        $automatedNotification,
                                        $filterData,
                                        $companyId
                                    )
                                );
                            }
                        );
                }
            )
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('location_id', (array) $filterData['location_ids']);
            })
            ->when($filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('inventories.product_id', (int) $filterData['product_id']);
            })
            ->when(null !== $filterData['region_ids'] && [] !== $filterData['region_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereHas('location', function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('region_id', $filterData['region_ids']);
                });
            })
            ->when($filterData['category_id'], function ($query) use ($filterData, $categoryQueries): void {
                if (config('app.product_variant')) {
                    $query->whereHas('product.masterProduct', function ($query) use (
                        $categoryQueries,
                        $filterData
                    ): void {
                        $query->select('id')
                            ->whereHas('categories', $categoryQueries->filterById((int) $filterData['category_id']));
                    });
                } else {
                    $query->whereHas('product', function ($query) use ($categoryQueries, $filterData): void {
                        $query->select('id')
                            ->whereHas('categories', $categoryQueries->filterById((int) $filterData['category_id']));
                    });
                }
            })
            ->when($filterData['article_numbers'], function ($query) use ($filterData): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->select('products.id')
                            ->from('products')
                            ->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                            ->whereIn('master_products.article_number', $filterData['article_numbers']);
                    } else {
                        $query->select('id')
                            ->from('products')
                            ->whereIn('article_number', $filterData['article_numbers']);
                    }
                });
            })
            ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->select('products.id')
                            ->from('products')
                            ->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                            ->whereIntegerInRaw('master_products.department_id', $filterData['department_ids']);
                    } else {
                        $query->select('products.id')
                            ->from('products')
                            ->whereIntegerInRaw('department_id', $filterData['department_ids']);
                    }
                });
            })
            ->when($filterData['brand_id'], function ($query) use ($filterData): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->select('products.id')
                            ->from('products')
                            ->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                            ->where('master_products.brand_id', $filterData['brand_id']);
                    } else {
                        $query->select('products.id')
                            ->from('products')
                            ->where('brand_id', $filterData['brand_id']);
                    }
                });
            })
            ->when($filterData['color_id'] && ! config('app.product_variant'), function ($query) use (
                $filterData
            ): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    $query->select('products.id')
                        ->from('products')
                        ->where('color_id', $filterData['color_id']);
                });
            })
            ->when($filterData['size_id'] && ! config('app.product_variant'), function ($query) use (
                $filterData
            ): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    $query->select('products.id')
                        ->from('products')
                        ->where('size_id', $filterData['size_id']);
                });
            })
            ->when($filterData['style_ids'] && ! config('app.product_variant'), function ($query) use (
                $filterData
            ): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    $query->select('products.id')
                        ->from('products')
                        ->where('style_id', $filterData['style_ids']);
                });
            })
            ->when($filterData['tag_ids'], function ($query) use ($filterData): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->select('master_product_tag.master_product_id')
                            ->from('master_product_tag')
                            ->whereIntegerInRaw('tag_id', $filterData['tag_ids']);
                    } else {
                        $query->select('product_tag.product_id')
                            ->from('product_tag')
                            ->whereIntegerInRaw('tag_id', $filterData['tag_ids']);
                    }
                });
            })
            ->when($filterData['product_collection_id'], function ($query) use ($filterData): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    $query->select('product_collection_products.product_id')
                        ->from('product_collection_products')
                        ->where('product_collection_id', (int) $filterData['product_collection_id']);
                });
            })
            ->when(
                array_key_exists('attributes', $filterData) && [] !== $filterData['attributes'] && config(
                    'app.product_variant'
                ),
                function ($query) use ($filterData): void {
                    $query->whereIn('product_id', function ($query) use ($filterData): void {
                        $query->select('pv.product_id')
                            ->from('product_variant_values as pv')
                            ->whereIn('pv.value', $filterData['attributes'])
                            ->groupBy('pv.product_id')
                            ->havingRaw('COUNT(DISTINCT pv.value) = ?', [count($filterData['attributes'])]);
                    });
                }
            )
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('item_name' === $filterData['sort_by']) {
                    $query->join('products', 'products.id', '=', 'inventories.product_id')
                        ->orderBy('products.name', $filterData['sort_direction']);
                }

                if (! config('app.product_variant')) {
                    if ('color' === $filterData['sort_by']) {
                        $query->join('products', 'products.id', '=', 'inventories.product_id')
                            ->join('colors', 'colors.id', '=', 'products.color_id')
                            ->orderBy('colors.name', $filterData['sort_direction']);
                    }

                    if ('size' === $filterData['sort_by']) {
                        $query->join('products', 'products.id', '=', 'inventories.product_id')
                            ->join('sizes', 'sizes.id', '=', 'products.size_id')
                            ->orderBy('sizes.name', $filterData['sort_direction']);
                    }
                }

                if ('stock' === $filterData['sort_by']) {
                    $query->orderBy('inventories.stock', $filterData['sort_direction']);
                }

                if ('reserved_stock' === $filterData['sort_by']) {
                    $query->orderBy('inventories.reserved_stock', $filterData['sort_direction']);
                }

                if ('current_stock' === $filterData['sort_by']) {
                    $query->orderBy('current_stock', $filterData['sort_direction']);
                }

                if ('transit_stock' === $filterData['sort_by']) {
                    $query->orderBy('transit_stocks_sum_quantity', $filterData['sort_direction']);
                }
            });
    }

    private function inventoryReportTotalCountQuery(array $filterData, int $companyId): QueryBuilder
    {
        $productQueries = resolve(ProductQueries::class);
        $automatedNotificationQueries = resolve(AutomatedNotificationQueries::class);
        $automatedNotification = $automatedNotificationQueries->getLowStockNotificationByCompanyIdAndType($companyId);

        return DB::table('inventories')
            ->select(
                DB::raw('SUM(stock) as available_stock'),
                DB::raw('SUM(reserved_stock) as reserve_stock'),
                DB::raw('COALESCE(SUM(transit_stocks.quantity), 0) as transit_stock'),
                DB::raw('SUM(inventories.reserved_stock) + SUM(inventories.stock) as current_stock')
            )
            ->leftJoin(
                DB::raw(
                    '(SELECT inventory_id, SUM(quantity) as quantity FROM transit_stocks WHERE transit_stocks.deleted_at IS NULL GROUP BY inventory_id) as transit_stocks'
                ),
                'transit_stocks.inventory_id',
                '=',
                'inventories.id'
            )
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id')
            ->where('products.company_id', $companyId)
            ->whereNull('products.deleted_at')
            ->when(config('app.product_variant'), function ($query): void {
                $query->where('master_products.is_non_inventory', false);
            }, function ($query): void {
                $query->where('products.is_non_inventory', false);
            })
            ->when(
                isset($filterData['selling_type']) && (int) $filterData['selling_type'] === SellingTypes::NON_SELLING->value,
                function ($query): void {
                    $column = config(
                        'app.product_variant'
                    ) ? 'master_products.is_non_selling_item' : 'products.is_non_selling_item';
                    $query->where($column, true);
                }
            )
            ->when(
                isset($filterData['selling_type']) && (int) $filterData['selling_type'] === SellingTypes::SELLING->value,
                function ($query): void {
                    $column = config(
                        'app.product_variant'
                    ) ? 'master_products.is_non_selling_item' : 'products.is_non_selling_item';
                    $query->where($column, false);
                }
            )
            ->when(ProductStatuses::ACTIVE->value === $filterData['status'], function ($query): void {
                $query->where('products.status', Statuses::ACTIVE->value);
            })
            ->when(ProductStatuses::ARCHIVED->value === $filterData['status'], function ($query): void {
                $query->where('products.status', ProductStatuses::ARCHIVED->value);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData, $productQueries): void {
                $query->where(function ($query) use ($productQueries, $filterData): void {
                    $query->whereIn(
                        'inventories.product_id',
                        $productQueries->searchByCompoundProductNameUpcAndSku($filterData['search_text'])
                    )
                        ->orWhere($this->filterByLocationTypeName($filterData['search_text']));
                });
            })
            ->when((int) $filterData['stock_type'] === Types::NO_STOCK->value, function ($query): void {
                $query->where('inventories.stock', 0);
            })
            ->when((int) $filterData['stock_type'] === Types::NEGATIVE_STOCK->value, function ($query): void {
                $query->where('inventories.stock', '<', 0);
            })
            ->when(
                (int) $filterData['stock_type'] === Types::LOW_STOCK_PRODUCT->value,
                function ($query) use ($companyId, $filterData): void {
                    $query
                        ->whereIn(
                            'inventories.id',
                            $this->getLowStockInventoryIdQueryForProduct($filterData, $companyId)
                        );
                }
            )
            ->when(
                (int) $filterData['stock_type'] === Types::LOW_STOCK_LOCATION->value,
                function ($query) use ($companyId, $filterData): void {
                    $query
                        ->whereIn(
                            'inventories.id',
                            $this->getLowStockInventoryIdQueryForStore($filterData, $companyId)
                        );
                }
            )
            ->when(
                (int) $filterData['stock_type'] === Types::LOW_STOCK_COMPANY->value,
                function ($query) use ($companyId, $filterData, $automatedNotification): void {
                    $query
                        ->when(
                            null !== $automatedNotification,
                            function ($query) use ($companyId, $filterData, $automatedNotification): void {
                                $query->whereIn(
                                    'inventories.id',
                                    $this->getLowStockInventoryIdQueryForCompany(
                                        /* @phpstan-ignore-next-line */
                                        $automatedNotification,
                                        $filterData,
                                        $companyId
                                    )
                                );
                            }
                        );
                }
            )
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('inventories.location_id', (array) $filterData['location_ids']);
            })
            ->when($filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('inventories.product_id', (int) $filterData['product_id']);
            })
            ->when(null !== $filterData['region_ids'], function ($query) use ($filterData): void {
                $query->join('locations', 'locations.id', '=', 'inventories.location_id')
                    ->whereIntegerInRaw('locations.region_id', $filterData['region_ids']);
            })
            ->when($filterData['category_id'], function ($query) use ($filterData): void {
                if (config('app.product_variant')) {
                    $query->join(
                        'category_master_product',
                        'master_products.id',
                        '=',
                        'category_master_product.master_product_id'
                    )
                        ->where('category_master_product.category_id', $filterData['category_id']);
                } else {
                    $query->join('category_product', 'products.id', '=', 'category_product.product_id')
                        ->where('category_product.category_id', $filterData['category_id']);
                }
            })
            ->when($filterData['article_numbers'], function ($query) use ($filterData): void {
                $query->whereIn('inventories.product_id', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->select('products.id')
                            ->from('products')
                            ->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                            ->whereIn('master_products.article_number', $filterData['article_numbers']);
                    } else {
                        $query->select('id')
                            ->from('products')
                            ->whereIn('article_number', $filterData['article_numbers']);
                    }
                });
            })
            ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereIn('inventories.product_id', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->select('products.id')
                            ->from('products')
                            ->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                            ->whereIntegerInRaw('master_products.department_id', $filterData['department_ids']);
                    } else {
                        $query->select('products.id')
                            ->from('products')
                            ->whereIntegerInRaw('department_id', $filterData['department_ids']);
                    }
                });
            })
            ->when($filterData['brand_id'], function ($query) use ($filterData): void {
                $query->whereIn('inventories.product_id', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->select('products.id')
                            ->from('products')
                            ->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                            ->where('master_products.brand_id', $filterData['brand_id']);
                    } else {
                        $query->select('products.id')
                            ->from('products')
                            ->where('brand_id', $filterData['brand_id']);
                    }
                });
            })
            ->when($filterData['color_id'] && ! config('app.product_variant'), function ($query) use (
                $filterData
            ): void {
                $query->whereIn('inventories.product_id', function ($query) use ($filterData): void {
                    $query->select('products.id')
                        ->from('products')
                        ->where('color_id', $filterData['color_id']);
                });
            })
            ->when($filterData['size_id'] && ! config('app.product_variant'), function ($query) use (
                $filterData
            ): void {
                $query->whereIn('inventories.product_id', function ($query) use ($filterData): void {
                    $query->select('products.id')
                        ->from('products')
                        ->where('size_id', $filterData['size_id']);
                });
            })
            ->when($filterData['style_ids'] && ! config('app.product_variant'), function ($query) use (
                $filterData
            ): void {
                $query->whereIn('inventories.product_id', function ($query) use ($filterData): void {
                    $query->select('products.id')
                        ->from('products')
                        ->where('style_id', $filterData['style_ids']);
                });
            })
            ->when($filterData['tag_ids'], function ($query) use ($filterData): void {
                $query->whereIn('inventories.product_id', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->select('master_product_tag.master_product_id')
                            ->from('master_product_tag')
                            ->whereIntegerInRaw('tag_id', $filterData['tag_ids']);
                    } else {
                        $query->select('product_tag.product_id')
                            ->from('product_tag')
                            ->whereIntegerInRaw('tag_id', $filterData['tag_ids']);
                    }
                });
            })
            ->when(
                array_key_exists('attributes', $filterData) && [] !== $filterData['attributes'] && config(
                    'app.product_variant'
                ),
                function ($query) use ($filterData): void {
                    $query->whereIn('inventories.product_id', function ($query) use ($filterData): void {
                        $query->select('pv.product_id')
                            ->from('product_variant_values as pv')
                            ->whereIn('pv.value', $filterData['attributes'])
                            ->groupBy('pv.product_id')
                            ->havingRaw('COUNT(DISTINCT pv.value) = ?', [count($filterData['attributes'])]);
                    });
                }
            )
            ->when($filterData['product_collection_id'], function ($query) use ($filterData): void {
                $query->whereIn('inventories.product_id', function ($query) use ($filterData): void {
                    $query->select('product_collection_products.product_id')
                        ->from('product_collection_products')
                        ->where('product_collection_id', (int) $filterData['product_collection_id']);
                });
            });
    }

    public function getInventoryByProductAndLocationWithReservedStock(int $productId, int $locationId): float
    {
        return (float) Inventory::query()
            ->select('id', DB::raw('(stock + reserved_stock) as stock'))
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->latest()
            ->first()
            ?->stock;
    }

    public function exportInventoryRecords(array $filterData, int $companyId, int $skip, int $limit): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $transitStockQueries = resolve(TransitStockQueries::class);

        return $this->commonInventoryReportListQuery($filterData, $companyId)
            ->with([
                'location:' . $this->getMorphLocationBasicColumns(),
                'product:' . $productQueries->getColumnsForInventoryReports(),
                'product.brand:' . $brandQueries->getBasicColumnNames(),
                'product.categories:' . $categoryQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
                'product.tags:' . $tagQueries->getBasicColumnNames(),
                'transitStocks:' . $transitStockQueries->getQuantityColumn(),
            ])
            ->withSum('transitStocks', 'quantity')
            ->skip($skip)
            ->limit($limit)
            ->get();
    }

    public function getInventoriesExportCount(array $filterData, int $companyId): int
    {
        return $this->commonInventoryReportListQuery($filterData, $companyId)->count();
    }

    private function getRawSqlFromClosure(Closure $closure): string
    {
        $query = Inventory::query();
        $closure($query);

        return $this->queryToRawSql($query);
    }

    private function queryToRawSql(Builder $query): string
    {
        $sql = $query->toSql();
        foreach ($query->getBindings() as $binding) {
            if (is_bool($binding)) {
                $value = $binding ? '1' : '0';
            } else {
                $value = (is_numeric($binding) || null === $binding) ? (string) $binding : "'" . addslashes(
                    $binding
                ) . "'";
            }

            $replacedSql = preg_replace('/\?/', $value, $sql, 1);
            if (null === $replacedSql) {
                throw new RuntimeException('An error occurred while processing the SQL query.');
            }

            $sql = $replacedSql;
        }

        return $sql;
    }

    public function getAllByCompanyId(int $companyId, int $perPage = 1000): LengthAwarePaginator
    {
        return Inventory::query()
            ->select(['id', 'product_id as product_variant_id', 'location_id', 'stock'])
            ->whereHas(
                'location',
                fn ($query) => $query
                ->where('type_id', LocationTypes::STORE->value)
                ->where('company_id', $companyId)
            )
            ->whereHas(
                'product',
                fn ($query) => $query
                ->where('status', Statuses::ACTIVE->value)
                ->where('type_id', ProductTypes::REGULAR_PRODUCT->value)
                ->where('company_id', $companyId)
            )
            ->paginate($perPage);
    }
}
