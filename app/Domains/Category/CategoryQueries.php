<?php

declare(strict_types=1);

namespace App\Domains\Category;

use App\CommonFunctions;
use App\Domains\Category\DataObjects\CategoryData;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Media\MediaQueries;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\Category;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CategoryQueries
{
    public function listQuery(int $companyId): Collection
    {
        return Category::query()
            ->select('id', 'name', 'code', 'parent_category_id', 'company_id')
            ->whereNull('parent_category_id')
            ->where('company_id', $companyId)
            ->with('children:id,name,code,parent_category_id,company_id')
            ->get();
    }

    public function getFirstForEcommerceSync(int $companyId, int $saleChannelId): ?Category
    {
        return Category::select('id')
            ->whereDoesntHave('categoryChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('is_available_in_ecommerce', true)
            ->where('company_id', $companyId)
            ->orderBy('id', 'asc')
            ->first();
    }

    public function getLastForEcommerceSync(int $companyId, int $saleChannelId): ?Category
    {
        return Category::select('id')
            ->whereDoesntHave('categoryChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('is_available_in_ecommerce', true)
            ->where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getCategoryEcommerceChannelByStartAndEndId(
        int $companyId,
        int $startId,
        int $endId,
        int $saleChannelId
    ): Collection {
        $mediaQueries = resolve(MediaQueries::class);

        return Category::select(
            'id',
            'company_id',
            'name',
            'parent_category_id',
            'status',
            'description',
            'is_available_in_ecommerce'
        )
            ->with('media:' . $mediaQueries->getBasicColumnNames())
            ->whereDoesntHave('categoryChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('is_available_in_ecommerce', true)
            ->where('company_id', $companyId)
            ->where('id', '>=', $startId)
            ->where('id', '<=', $endId)
            ->get();
    }

    public function addNew(CategoryData $categoryData, int $companyId): void
    {
        $data = $categoryData->all();
        $data['company_id'] = $companyId;

        unset($data['square_image']);
        unset($data['portrait_images']);
        unset($data['landscape_images']);
        DB::beginTransaction();
        try {
            $category = Category::create($data);

            $this->uploadSquareImage($category, $categoryData);
            $this->uploadPortraitImages($category, $categoryData);
            $this->uploadLandscapeImages($category, $categoryData);

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error([
                'error_name' => 'Category create error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);
        }
    }

    public function getById(int $categoryId, int $companyId): Category
    {
        $mediaQueries = resolve(MediaQueries::class);

        return Category::select(
            'id',
            'name',
            'code',
            'parent_category_id',
            'description',
            'status',
            'is_available_in_ecommerce',
            'is_display_on_menu'
        )
            ->with('media:' . $mediaQueries->getBasicColumnNames())
            ->where('company_id', $companyId)
            ->findOrFail($categoryId);
    }

    public function getCategoryById(int $categoryId): Category
    {
        $mediaQueries = resolve(MediaQueries::class);

        return Category::select(
            'id',
            'name',
            'is_available_in_ecommerce',
            'company_id',
            'status',
            'is_display_on_menu',
            'description'
        )
            ->with('media:' . $mediaQueries->getBasicColumnNames())
            ->findOrFail($categoryId);
    }

    public function getCategoryByIdForEcommerce(int $categoryId): Category
    {
        $mediaQueries = resolve(MediaQueries::class);

        return Category::select(
            'id',
            'name',
            'is_available_in_ecommerce',
            'company_id',
            'status',
            'is_display_on_menu',
            'description',
            'code',
            'is_display_on_menu',
        )
            ->with('media:' . $mediaQueries->getBasicColumnNames())
            ->findOrFail($categoryId);
    }

    public function getCategoryByIdAndCompanyId(int $categoryId, int $companyId): Category
    {
        return Category::select('name')
            ->onlyActive()
            ->where('company_id', $companyId)
            ->findOrFail($categoryId);
    }

    public function update(CategoryData $categoryData, int $categoryId, int $companyId): void
    {
        $category = $this->getById($categoryId, $companyId);
        $categoryDetails = $categoryData->all();
        unset($categoryDetails['square_image']);
        unset($categoryDetails['portrait_images']);
        unset($categoryDetails['landscape_images']);
        DB::beginTransaction();
        try {
            $category->update($categoryDetails);

            $this->setUpdatedAt($category);

            $this->uploadSquareImage($category, $categoryData);
            $this->uploadPortraitImages($category, $categoryData);
            $this->uploadLandscapeImages($category, $categoryData);
            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error([
                'error_name' => 'Category update error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);
        }
    }

    public function setUpdatedAt(Category $category): void
    {
        $category->touch();
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,code,is_available_in_ecommerce';
    }

    public function getBasicColumnNamesForProductCollection(): string
    {
        return 'id,name';
    }

    public function getBasicColumnNamesForPosMemberApi(): string
    {
        return 'id';
    }

    public function searchByName(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')->where('status', true)->where(
            'name',
            'like',
            '%' . $searchText . '%'
        );
    }

    public function searchByColumns(string $searchText): Closure
    {
        return fn ($query) => $query
            ->where('status', true)
            ->whereAny(['name', 'code'], 'LIKE', '%' . $searchText . '%');
    }

    public function getMainCategoriesWithBasicColumns(int $companyId): Collection
    {
        return Category::select('id', 'name')
            ->where('company_id', $companyId)
            ->whereNull('parent_category_id')
            ->onlyActive()
            ->get();
    }

    public function getChildCategoriesWithBasicColumns(int $categoryId, int $companyId): Collection
    {
        return Category::select('id', 'name')
            ->where('company_id', $companyId)
            ->where('parent_category_id', $categoryId)
            ->onlyActive()
            ->get();
    }

    public function existsByName(string $name, int $companyId): bool
    {
        return Category::whereCaseSensitive('name', $name)->onlyActive()->where('company_id', $companyId)->exists();
    }

    public function existsByNameAndCompanyId(string $name, int $companyId): bool
    {
        return Category::whereCaseSensitive('name', $name)->where('company_id', $companyId)->exists();
    }

    public function getIdByName(string $name, int $companyId): int
    {
        return Category::select('id')
            ->onlyActive()
            ->whereCaseSensitive('name', $name)
            ->where('company_id', $companyId)
            ->firstOrFail()
            ->id;
    }

    public function createOrGetByName(string $name, int $companyId): int
    {
        return Category::firstOrCreate([
            'name' => $name,
            'company_id' => $companyId,
        ])->getKey();
    }

    public function createSubCategoryOrGetByName(string $name, int $categoryId, int $companyId): int
    {
        return Category::firstOrCreate([
            'name' => $name,
            'company_id' => $companyId,
        ], [
            'parent_category_id' => $categoryId,
        ])->getKey();
    }

    public function createSubsubCategoryOrGetByName(string $name, int $subCategoryId, int $companyId): int
    {
        return Category::firstOrCreate([
            'name' => $name,
            'company_id' => $companyId,
        ], [
            'parent_category_id' => $subCategoryId,
        ])->getKey();
    }

    public function getIdByNameWithoutCompanyId(string $name): ?Category
    {
        return Category::select('id')
            ->whereCaseSensitive('name', $name)
            ->first();
    }

    public function doAllParentCategoriesExist(int $companyId, array $categoryIds): bool
    {
        $totalRecords = Category::whereIntegerInRaw('id', $categoryIds)
            ->onlyActive()
            ->whereNull('parent_category_id')
            ->where('company_id', $companyId)
            ->count();

        return count($categoryIds) === $totalRecords;
    }

    public function doAllCategoriesExist(int $companyId, array $categoryIds): bool
    {
        $totalRecords = Category::whereIntegerInRaw('id', $categoryIds)->onlyActive()->where(
            'company_id',
            $companyId
        )->count();

        return count($categoryIds) === $totalRecords;
    }

    public function filterByIdAndCompany(int $companyId, int $categoryId): Closure
    {
        return fn ($query) => $query->select('id')->where('status', true)->where(
            'company_id',
            $companyId
        )->where('id', $categoryId);
    }

    public function filterByIdsAndCompany(array $categoryIds, int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('status', true)->where(
            'company_id',
            $companyId
        )->whereIntegerInRaw('id', $categoryIds);
    }

    public function filterById(int $categoryId): Closure
    {
        return fn ($query) => $query->select('id')->where('status', true)->where('id', $categoryId);
    }

    public function filterByIds(array $categoryIds): Closure
    {
        return fn ($query) => $query->select('id')->where('status', true)->whereIntegerInRaw('id', $categoryIds);
    }

    public function getByIds(array $categoryIds): Collection
    {
        return Category::select('name')
            ->onlyActive()
            ->whereIntegerInRaw('id', $categoryIds)
            ->get();
    }

    public function getFilteredCategoriesByCompanyId(string $searchText, int $companyId): Collection
    {
        return Category::select('id', 'name')
            ->onlyActive()
            ->where('name', 'like', '%' . $searchText . '%')
            ->where('company_id', $companyId)
            ->limit(5)
            ->get();
    }

    public function getByCompanyId(int $companyId): Collection
    {
        return Category::select('id', 'name')
            ->onlyActive()
            ->where('company_id', $companyId)
            ->get();
    }

    public function getByCompanyIdForPos(int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        return Category::select('id', 'name', 'status')
            ->where('company_id', $companyId)
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            }, function ($query): void {
                $query->onlyActive();
            })
            ->get();
    }

    public function getParentByCompanyId(int $companyId): Collection
    {
        return Category::select('id', 'name')
            ->onlyActive()
            ->whereNull('parent_category_id')
            ->where('company_id', $companyId)
            ->get();
    }

    public function filterByParentCategory(): Closure
    {
        return fn ($query) => $query->where('status', true)->whereNull('parent_category_id');
    }

    public function getSaleItemsTotalSum(string $startDate, string $endDate): Collection
    {
        return Category::select(
            'categories.id',
            'categories.name',
            'categories.company_id',
            'counters.location_id',
            'sales.counter_update_id',
            'counter_updates.opened_by_pos_at',
            'counter_updates.created_at',
            DB::raw('SUM(sale_items.total_price_paid) as total_amount'),
            DB::raw('SUM(sale_items.quantity) as total_units_sold')
        )
            ->leftJoin('category_product', 'categories.id', '=', 'category_product.category_id')
            ->leftJoin('products', 'category_product.product_id', '=', 'products.id')
            ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->leftJoin('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
            ->where('sales.happened_at', '>=', $startDate)
            ->where('sales.happened_at', '<=', $endDate)
            ->whereNull('categories.parent_category_id')
            ->where('categories.status', true)
            ->groupBy('categories.id')
            ->groupBy('counters.location_id')
            ->groupBy('sales.counter_update_id')
            ->get();
    }

    public function getSaleReturnItemsTotalSum(string $startDate, string $endDate): Collection
    {
        return Category::query()
            ->leftJoin('category_product', 'categories.id', '=', 'category_product.category_id')
            ->leftJoin('products', 'category_product.product_id', '=', 'products.id')
            ->leftJoin('sale_return_items', 'products.id', '=', 'sale_return_items.product_id')
            ->leftJoin('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->leftJoin('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
            ->leftJoin('counters as c', 'counter_updates.counter_id', '=', 'c.id')
            ->select(
                'categories.id',
                'categories.name',
                'categories.company_id',
                'c.location_id as location_id',
                'sale_returns.counter_update_id',
                'counter_updates.opened_by_pos_at',
                'counter_updates.created_at',
                DB::raw('SUM(sale_return_items.total_price_paid) AS total_return_sale_amount'),
                DB::raw('SUM(sale_return_items.quantity) AS total_return_units')
            )
            ->where('categories.status', true)
            ->where('sale_returns.happened_at', '>=', $startDate)
            ->where('sale_returns.happened_at', '<=', $endDate)
            ->whereNull('categories.parent_category_id')
            ->groupBy('categories.id')
            ->groupBy('location_id')
            ->groupBy('sale_returns.counter_update_id')
            ->get();
    }

    public function getCachedCategoriesSalesForChart(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string|array $date,
        bool $refresh = false
    ): Collection {
        if (is_array($date)) {
            $cacheKey = null !== $locationId ? 'cache-category-sales-' . $locationId . $brandId . $date[0] . $date[1] : 'cache-category-sales-' . $date[0] . $date[1] . $brandId;
        } else {
            $cacheKey = null !== $locationId ? 'cache-category-sales-' . $locationId . $brandId . $date : 'cache-category-sales-' . $date . $brandId;
        }

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => Category::query()
                ->onlyActive()
                ->select(
                    'categories.id',
                    'categories.name',
                    'category_sale_total.sales_count',
                    DB::raw(
                        '(COALESCE(category_sale_total.total_paid_amount, 0) - COALESCE(category_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(category_sale_total.units_sold, 0) - COALESCE(category_return_total.return_units, 0)) as total_units_sold'
                    ),
                )
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->join('category_product', 'products.id', '=', 'category_product.product_id')
                        ->join('categories', 'category_product.category_id', '=', 'categories.id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                            $query->where('products.brand_id', $brandId);
                        })
                        ->when($date, function ($query) use ($date): void {
                            if (is_array($date)) {
                                $query->where(
                                    'counter_updates.opened_by_pos_at',
                                    '>=',
                                    CommonFunctions::addStartTime($date[0])
                                )
                                    ->where(
                                        'counter_updates.opened_by_pos_at',
                                        '<=',
                                        CommonFunctions::addEndTime($date[1])
                                    );
                            }

                            if (is_string($date)) {
                                $query->where(
                                    'counter_updates.opened_by_pos_at',
                                    '>=',
                                    CommonFunctions::addStartTime($date)
                                )
                                    ->where(
                                        'counter_updates.opened_by_pos_at',
                                        '<=',
                                        CommonFunctions::addEndTime($date)
                                    );
                            }
                        })
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->select(
                            'categories.id as category_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('category_id'),
                    'category_sale_total',
                    'category_sale_total.category_id',
                    '=',
                    'categories.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->join('category_product', 'products.id', '=', 'category_product.product_id')
                        ->join('categories', 'category_product.category_id', '=', 'categories.id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                            $query->where('products.brand_id', $brandId);
                        })
                        ->when($date, function ($query) use ($date): void {
                            if (is_array($date)) {
                                $query->where(
                                    'counter_updates.opened_by_pos_at',
                                    '>=',
                                    CommonFunctions::addStartTime($date[0])
                                )
                                    ->where(
                                        'counter_updates.opened_by_pos_at',
                                        '<=',
                                        CommonFunctions::addEndTime($date[1])
                                    );
                            }

                            if (is_string($date)) {
                                $query->where(
                                    'counter_updates.opened_by_pos_at',
                                    '>=',
                                    CommonFunctions::addStartTime($date)
                                )
                                    ->where(
                                        'counter_updates.opened_by_pos_at',
                                        '<=',
                                        CommonFunctions::addEndTime($date)
                                    );
                            }
                        })
                        ->select(
                            'categories.id as category_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('category_id'),
                    'category_return_total',
                    'category_return_total.category_id',
                    '=',
                    'categories.id'
                )
                ->whereNotNull('category_sale_total.total_paid_amount')
                ->orWhereNotNull('category_sale_total.units_sold')
                ->orWhereNotNull('category_sale_total.sales_count')
                ->orWhereNotNull('category_return_total.return_amount')
                ->orWhereNotNull('category_return_total.return_units')
                ->having('total_sales', '>', 0)
                ->having('total_units_sold', '>', 0)
                ->orderByDesc('total_sales')
                ->get()
        );
    }

    public function getCachedSeasonalTopFiveCategoriesSalesForChart(
        array $filterData,
        int $companyId,
        bool $refresh = false,
        array $ids = [],
    ): Collection {
        $cacheKey = null !== $filterData['location_id'] ? 'cache-seasonal-category-sales-' . $filterData['location_id'] . $filterData['brand_id'] . $filterData['start_date'] . $filterData['end_date'] : 'cache-category-sales-' . $filterData['start_date'] . $filterData['end_date'] . $filterData['brand_id'];

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => Category::query()
                ->onlyActive()
                ->select(
                    'categories.id',
                    'categories.name',
                    'categories.code',
                    'category_sale_total.sales_count',
                    DB::raw(
                        '(COALESCE(category_sale_total.total_paid_amount, 0) - COALESCE(category_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(category_sale_total.units_sold, 0) - COALESCE(category_return_total.return_units, 0)) as total_units_sold'
                    ),
                )
                ->when([] !== $ids, function ($query) use ($ids): void {
                    $query->whereIntegerInRaw('id', $ids);
                })
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->join('category_product', 'products.id', '=', 'category_product.product_id')
                        ->join('categories', 'category_product.category_id', '=', 'categories.id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when((int) $filterData['brand_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['brand_id']);
                        })
                        ->when($filterData['start_date'] && $filterData['end_date'], function ($query) use (
                            $filterData
                        ): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['start_date'])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['end_date'])
                                );
                        })
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->select(
                            'categories.id as category_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('category_id'),
                    'category_sale_total',
                    'category_sale_total.category_id',
                    '=',
                    'categories.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->join('category_product', 'products.id', '=', 'category_product.product_id')
                        ->join('categories', 'category_product.category_id', '=', 'categories.id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when((int) $filterData['brand_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['brand_id']);
                        })
                        ->when($filterData['start_date'] && $filterData['end_date'], function ($query) use (
                            $filterData
                        ): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['start_date'])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['end_date'])
                                );
                        })
                        ->select(
                            'categories.id as category_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('category_id'),
                    'category_return_total',
                    'category_return_total.category_id',
                    '=',
                    'categories.id'
                )
                ->whereNotNull('category_sale_total.total_paid_amount')
                ->orWhereNotNull('category_sale_total.units_sold')
                ->orWhereNotNull('category_sale_total.sales_count')
                ->orWhereNotNull('category_return_total.return_amount')
                ->orWhereNotNull('category_return_total.return_units')
                ->orderByDesc('total_sales')
                ->take(5)
                ->get()
        );
    }

    public function getWithBasicColumns(int $companyId): Collection
    {
        return Category::select('id', 'name', 'parent_category_id')->onlyActive()->where(
            'company_id',
            $companyId
        )->get();
    }

    public function getAll(int $companyId): Collection
    {
        return Category::select('id', 'name')->where('company_id', $companyId)->get();
    }

    public function updateProductIdsInCategoryProductPivot(int $oldProductId, int $newProductId): void
    {
        DB::table('category_product')
            ->where('product_id', $oldProductId)
            ->update([
                'product_id' => $newProductId,
            ]);
    }

    public function existsByCode(string $code, int $companyId): bool
    {
        return Category::whereCaseSensitive('code', $code)->where('company_id', $companyId)->exists();
    }

    public function getCategoriesByCompanyId(array $filterData, int $companyId): LengthAwarePaginator
    {
        $mediaQueries = resolve(MediaQueries::class);

        return Category::query()
            ->onlyActive()
            ->select('id', 'name', 'code', 'parent_category_id', 'updated_at', 'created_at')
            ->with('media:' . $mediaQueries->getBasicColumnNames())
            ->where('company_id', $companyId)
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getCategorySalesSummary(array $filterData, int $companyId): Collection
    {
        return Category::query()
            ->onlyActive()
            ->select(
                'categories.id',
                'categories.name',
                DB::raw(
                    '(COALESCE(category_sale_total.total_paid_amount, 0) - COALESCE(category_return_total.return_amount, 0)) as total_sales'
                ),
                DB::raw(
                    '(COALESCE(category_sale_total.units_sold, 0) - COALESCE(category_return_total.return_units, 0)) as total_units_sold'
                )
            )
            ->leftJoinSub(
                DB::table('sales')
                    ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->join('category_product', 'products.id', '=', 'category_product.product_id')
                    ->join('categories', 'category_product.category_id', '=', 'categories.id')
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::COLORS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.color_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::BRANDS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::DEPARTMENTS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.department_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::COLOR_GROUPS->value,
                        function ($query) use ($filterData): void {
                            $query->join('colors', 'products.color_id', '=', 'colors.id')
                                ->where('colors.group_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::SIZES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.size_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::STYLES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.style_id', $filterData['id']);
                        }
                    )
                    ->where('locations.company_id', $companyId)
                    ->where(
                        'counter_updates.opened_by_pos_at',
                        '>=',
                        CommonFunctions::addStartTime($filterData['date'])
                    )
                    ->where(
                        'counter_updates.opened_by_pos_at',
                        '<=',
                        CommonFunctions::addEndTime($filterData['date'])
                    )
                    ->when(array_key_exists('locationId', $filterData), function ($query) use ($filterData): void {
                        $query->where('locations.id', $filterData['locationId']);
                    })
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->select(
                        'categories.id as category_id',
                        DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                        DB::raw('SUM(sale_items.quantity) as units_sold')
                    )
                    ->groupBy('category_id'),
                'category_sale_total',
                'category_sale_total.category_id',
                '=',
                'categories.id'
            )
            ->leftJoinSub(
                DB::table('sale_returns')
                    ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                    ->join('category_product', 'products.id', '=', 'category_product.product_id')
                    ->join('categories', 'category_product.category_id', '=', 'categories.id')
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::COLORS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.color_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::BRANDS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::DEPARTMENTS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.department_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::COLOR_GROUPS->value,
                        function ($query) use ($filterData): void {
                            $query->join('colors', 'products.color_id', '=', 'colors.id')
                                ->where('colors.group_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::SIZES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.size_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::STYLES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.style_id', $filterData['id']);
                        }
                    )
                    ->where('locations.company_id', $companyId)
                    ->when(array_key_exists('locationId', $filterData), function ($query) use ($filterData): void {
                        $query->where('locations.id', $filterData['locationId']);
                    })
                    ->where(
                        'counter_updates.opened_by_pos_at',
                        '>=',
                        CommonFunctions::addStartTime($filterData['date'])
                    )
                    ->where(
                        'counter_updates.opened_by_pos_at',
                        '<=',
                        CommonFunctions::addEndTime($filterData['date'])
                    )
                    ->select(
                        'categories.id as category_id',
                        DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                        DB::raw('SUM(sale_return_items.quantity) as return_units')
                    )
                    ->groupBy('category_id'),
                'category_return_total',
                'category_return_total.category_id',
                '=',
                'categories.id'
            )
            ->whereNotNull('category_sale_total.total_paid_amount')
            ->orWhereNotNull('category_sale_total.units_sold')
            ->orWhereNotNull('category_return_total.return_amount')
            ->orWhereNotNull('category_return_total.return_units')
            ->orderByDesc('total_sales')
            ->get();
    }

    public function getIdByNameAndCompanyId(string $name, int $companyId): int
    {
        return Category::firstOrCreate([
            'name' => $name,
            'company_id' => $companyId,
        ])->id;
    }

    public function removeSquareImage(int $categoryId, int $companyId): void
    {
        $category = Category::query()
            ->select('id')
            ->where('company_id', $companyId)
            ->findOrFail($categoryId);

        $category->clearMediaCollection('square_image');
    }

    public function removeImage(int $categoryId, int $mediaId, int $companyId, string $mediaName): void
    {
        $category = Category::select('id')->where('company_id', $companyId)->findOrFail($categoryId);

        // We are using directly getMedia function instead of getDiskBasedFirstMedia method because here we are not playing with the file we are just deleting a record. And, Spatie media library will taken care of it.
        $media = $category->getMedia($mediaName)->find($mediaId);

        if ($media) {
            $media->delete();
        }
    }

    public function getCategoriesForBulkUpdate(int $companyId): Collection
    {
        return Category::select(
            'id',
            'name',
            'code',
            'description',
            'status',
            'is_available_in_ecommerce',
            'is_display_on_menu',
        )
            ->where('company_id', $companyId)
            ->get();
    }

    public function codeTakenByAnotherCategory(string $code, string $name, int $companyId): bool
    {
        return Category::whereNotCaseSensitive('name', $name)
            ->whereCaseSensitive('code', $code)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function updateByName(array $categoryData, string $name, int $companyId): void
    {
        unset($categoryData['square_image']);
        unset($categoryData['portrait_images']);
        unset($categoryData['landscape_images']);

        $category = Category::select('id')
            ->where('name', $name)
            ->where('company_id', $companyId)
            ->first();

        if ($category instanceof Category) {
            $category->update($categoryData);
        }
    }

    private function uploadSquareImage(Category $category, CategoryData $categoryData): void
    {
        if ($categoryData->square_image instanceof UploadedFile) {
            $category->addMedia($categoryData->square_image)->toMediaCollection('square_image');
        }
    }

    private function uploadPortraitImages(Category $category, CategoryData $categoryData): void
    {
        if (! $categoryData->portrait_images) {
            return;
        }

        foreach ($categoryData->portrait_images as $image) {
            if ($image instanceof UploadedFile) {
                $category->addMedia($image)->toMediaCollection('portrait_images');
            }
        }
    }

    private function uploadLandscapeImages(Category $category, CategoryData $categoryData): void
    {
        if (! $categoryData->landscape_images) {
            return;
        }

        foreach ($categoryData->landscape_images as $image) {
            if ($image instanceof UploadedFile) {
                $category->addMedia($image)->toMediaCollection('landscape_images');
            }
        }
    }

    public function updateIsAvailableInEcommerce(Category $category): void
    {
        $category->is_available_in_ecommerce = true;
        $category->save();
    }

    public function refresh(Category $category): Category
    {
        $category->refresh();

        return $category;
    }

    public function getCategoryNameForFilter(array $categoryIds): string
    {
        $categoryData = [];
        $category = Category::select('name')
            ->whereIntegerInRaw('id', values: $categoryIds)
            ->get();

        if ($category->isNotEmpty()) {
            $categoryData = $category->pluck('name')->toArray();
        }

        return implode(', ', $categoryData);
    }

    public function getECommerceCategories(): array
    {
        return Category::select('id', 'name')
        ->where('is_available_in_ecommerce', 1)
        ->get()
        ->toArray();
    }
}
