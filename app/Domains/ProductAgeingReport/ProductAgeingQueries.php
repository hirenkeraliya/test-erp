<?php

declare(strict_types=1);

namespace App\Domains\ProductAgeingReport;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductAgeingReport\Enums\AgeCategories;
use App\Domains\ProductAgeingReport\Enums\AgeOfProductTypes;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Tag\TagQueries;
use App\Models\ProductAgeing;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductAgeingQueries
{
    public function getPaginatedProductsAgeingReportByMonthAndYear(
        array $filterData,
        int $companyId
    ): LengthAwarePaginator {
        return $this->commonQueriesForReportByMonthAndYear($filterData, $companyId)
            ->paginate($filterData['per_page']);
    }

    public function addNew(array $productAgeingData, int $locationId, int $productId, string $productCreatedAt): void
    {
        ProductAgeing::create([
            'product_id' => $productId,
            'location_id' => $locationId,
            'product_created_at' => $productCreatedAt,
            'last_selling_date' => $productAgeingData['last_selling_date'] ?? null,
            'quantity_sold' => $productAgeingData['quantity_sold'] ?? null,
            'quantity_remaining' => $productAgeingData['quantity_remaining'] ?? null,
            'first_month_sold' => $productAgeingData['first_month_sold'] ?? null,
            'second_month_sold' => $productAgeingData['second_month_sold'] ?? null,
            'third_month_sold' => $productAgeingData['third_month_sold'] ?? null,
            'fourth_month_sold' => $productAgeingData['fourth_month_sold'] ?? null,
            'fifth_month_sold' => $productAgeingData['fifth_month_sold'] ?? null,
            'sixth_month_sold' => $productAgeingData['sixth_month_sold'] ?? null,
            'seventh_month_sold' => $productAgeingData['seventh_month_sold'] ?? null,
            'eighth_month_sold' => $productAgeingData['eighth_month_sold'] ?? null,
            'ninth_month_sold' => $productAgeingData['ninth_month_sold'] ?? null,
            'tenth_month_sold' => $productAgeingData['tenth_month_sold'] ?? null,
            'eleventh_month_sold' => $productAgeingData['eleventh_month_sold'] ?? null,
            'twelfth_month_sold' => $productAgeingData['twelfth_month_sold'] ?? null,
            'first_transfer_in' => $productAgeingData['first_transfer_in'] ?? null,
            'first_goods_received_note' => $productAgeingData['first_goods_received_note'] ?? null,
        ]);
    }

    public function update(array $productAgeingData, int $locationId, int $productId): void
    {
        $productAgeing = ProductAgeing::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->first();

        if (! $productAgeing instanceof ProductAgeing) {
            return;
        }

        if (array_key_exists('product_created_at', $productAgeingData)) {
            $productAgeing->product_created_at = $productAgeingData['product_created_at'];
        }

        $productAgeing->last_selling_date = $productAgeingData['last_selling_date'];
        $productAgeing->quantity_sold = $productAgeingData['quantity_sold'];
        $productAgeing->quantity_remaining = $productAgeingData['quantity_remaining'];
        $productAgeing->first_month_sold = $productAgeingData['first_month_sold'];
        $productAgeing->second_month_sold = $productAgeingData['second_month_sold'];
        $productAgeing->third_month_sold = $productAgeingData['third_month_sold'];
        $productAgeing->fourth_month_sold = $productAgeingData['fourth_month_sold'];
        $productAgeing->fifth_month_sold = $productAgeingData['fifth_month_sold'];
        $productAgeing->sixth_month_sold = $productAgeingData['sixth_month_sold'];
        $productAgeing->seventh_month_sold = $productAgeingData['seventh_month_sold'];
        $productAgeing->eighth_month_sold = $productAgeingData['eighth_month_sold'];
        $productAgeing->ninth_month_sold = $productAgeingData['ninth_month_sold'];
        $productAgeing->tenth_month_sold = $productAgeingData['tenth_month_sold'];
        $productAgeing->eleventh_month_sold = $productAgeingData['eleventh_month_sold'];
        $productAgeing->twelfth_month_sold = $productAgeingData['twelfth_month_sold'];
        $productAgeing->first_transfer_in = $productAgeingData['first_transfer_in'];
        $productAgeing->first_goods_received_note = $productAgeingData['first_goods_received_note'];
        $productAgeing->save();
    }

    public function getPaginatedProductsAgeingReport(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->commonQueriesForProductAgeingReport($filterData, $companyId)
            ->paginate($filterData['per_page']);
    }

    public function getProductsAgeingReportForExport(array $filterData, int $companyId): Collection
    {
        return $this->commonQueriesForProductAgeingReport($filterData, $companyId)
            ->get();
    }

    public function getProductAgeingExportCount(array $filterData, int $companyId): int
    {
        return $this->commonQueriesForProductAgeingReport($filterData, $companyId)
            ->count();
    }

    public function exportProductAgeingRecords(array $filterData, int $companyId, int $skip, int $limit): Collection
    {
        return $this->commonQueriesForProductAgeingReport($filterData, $companyId)
            ->skip($skip)
            ->limit($limit)
            ->get();
    }

    public function getProductsAgeingReportForConsolidate(array $filterData, int $companyId): ?ProductAgeing
    {
        return ProductAgeing::query()
            ->select(
                'product_id',
                DB::raw('CASE
                        WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN DATEDIFF(NOW(), first_goods_received_note)
                        WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN DATEDIFF(NOW(), first_transfer_in)
                        ELSE DATEDIFF(NOW(), product_created_at)
                    END AS age_category'),
                DB::raw(
                    'SUM(CASE WHEN DATEDIFF(NOW(), CASE WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN first_goods_received_note WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN first_transfer_in ELSE product_created_at END) <= 30 THEN 1 ELSE 0 END) AS age_category_0_30'
                ),
                DB::raw(
                    'SUM(CASE WHEN DATEDIFF(NOW(), CASE WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN first_goods_received_note WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN first_transfer_in ELSE product_created_at END) BETWEEN 31 AND 60 THEN 1 ELSE 0 END) AS age_category_31_60'
                ),
                DB::raw(
                    'SUM(CASE WHEN DATEDIFF(NOW(), CASE WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN first_goods_received_note WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN first_transfer_in ELSE product_created_at END) BETWEEN 61 AND 90 THEN 1 ELSE 0 END) AS age_category_61_90'
                ),
                DB::raw(
                    'SUM(CASE WHEN DATEDIFF(NOW(), CASE WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN first_goods_received_note WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN first_transfer_in ELSE product_created_at END) BETWEEN 91 AND 180 THEN 1 ELSE 0 END) AS age_category_91_180'
                ),
                DB::raw(
                    'SUM(CASE WHEN DATEDIFF(NOW(), CASE WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN first_goods_received_note WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN first_transfer_in ELSE product_created_at END) BETWEEN 181 AND 360 THEN 1 ELSE 0 END) AS age_category_181_360'
                ),
                DB::raw(
                    'SUM(CASE WHEN DATEDIFF(NOW(), CASE WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN first_goods_received_note WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN first_transfer_in ELSE product_created_at END) BETWEEN 361 AND 720 THEN 1 ELSE 0 END) AS age_category_361_720'
                ),
                DB::raw(
                    'SUM(CASE WHEN DATEDIFF(NOW(), CASE WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN first_goods_received_note WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN first_transfer_in ELSE product_created_at END) BETWEEN 721 AND 1080 THEN 1 ELSE 0 END) AS age_category_721_1080'
                ),
                DB::raw(
                    'SUM(CASE WHEN DATEDIFF(NOW(), CASE WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN first_goods_received_note WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN first_transfer_in ELSE product_created_at END) >= 1081 THEN 1 ELSE 0 END) AS age_category_1081_0'
                ),
            )
            ->selectRaw('SUM(quantity_sold) AS quantity_sold')
            ->selectRaw('SUM(quantity_remaining) AS quantity_remaining')
            ->where($this->filtersData($filterData, $companyId))
            ->join('products', 'products.id', '=', 'product_ageings.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->orderBy('product_id', 'desc')
            ->first();
    }

    public function getProductAgeingByMonthAndYearExportCount(array $filterData, int $companyId): int
    {
        return $this->commonQueriesForReportByMonthAndYear($filterData, $companyId)
            ->count();
    }

    public function exportProductAgeingByMonthAndYearRecords(
        array $filterData,
        int $companyId,
        int $skip,
        int $limit
    ): Collection {
        return $this->commonQueriesForReportByMonthAndYear($filterData, $companyId)
            ->skip($skip)
            ->limit($limit)
            ->get();
    }

    public function getProductsAgeingReportByMonthAndYearForExport(array $filterData, int $companyId): Collection
    {
        return $this->commonQueriesForReportByMonthAndYear($filterData, $companyId)
            ->get();
    }

    public function updateQuantityRemaining(float $quantityRemaining, int $locationId, int $productId): void
    {
        $productAgeing = ProductAgeing::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->first();

        if (! $productAgeing instanceof ProductAgeing) {
            return;
        }

        $productAgeing->quantity_remaining = $quantityRemaining;
        $productAgeing->save();
    }

    private function commonQueriesForReportByMonthAndYear(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        $relations = [
            'product:' . $productQueries->getBasicColumnsForProductAgeing(),
            'location:' . $locationQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return ProductAgeing::query()
            ->select(
                'product_id',
                'location_id',
                'product_created_at',
                'last_selling_date',
                'first_transfer_in',
                'first_goods_received_note',
                'quantity_sold',
                'quantity_remaining',
                'first_month_sold',
                'second_month_sold',
                'third_month_sold',
                'fourth_month_sold',
                'fifth_month_sold',
                'sixth_month_sold',
                'seventh_month_sold',
                'eighth_month_sold',
                'ninth_month_sold',
                'tenth_month_sold',
                'eleventh_month_sold',
                'twelfth_month_sold',
                DB::raw('CASE
                        WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN DATEDIFF(NOW(), first_goods_received_note)
                        WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN DATEDIFF(NOW(), first_transfer_in)
                        ELSE DATEDIFF(NOW(), product_created_at)
                    END AS age_category'),
            )
            ->with($relations)
            ->where($this->filtersData($filterData, $companyId))
            ->join('products', 'products.id', '=', 'product_ageings.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            }, function ($query): void {
                $query->leftJoin('colors', 'colors.id', '=', 'products.color_id')
                    ->leftJoin('sizes', 'sizes.id', '=', 'products.size_id');
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('product' === $filterData['sort_by']) {
                    $query->orderBy('products.name', $filterData['sort_direction']);
                }

                if ('upc' === $filterData['sort_by']) {
                    $query->orderBy('products.upc', $filterData['sort_direction']);
                }

                if ('article_number' === $filterData['sort_by']) {
                    $query->orderBy('products.article_number', $filterData['sort_direction']);
                }

                if ('color' === $filterData['sort_by']) {
                    $query->orderBy('colors.name', $filterData['sort_direction']);
                }

                if ('size' === $filterData['sort_by']) {
                    $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                }

                if ('created_at' === $filterData['sort_by']) {
                    if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::CREATED_AT->value) {
                        $query->orderBy('product_created_at', $filterData['sort_direction']);
                    }

                    if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value) {
                        $query->orderBy('first_goods_received_note', $filterData['sort_direction']);
                    }

                    if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_TRANSFER_IN->value) {
                        $query->orderBy('first_transfer_in', $filterData['sort_direction']);
                    }
                }

                if ('last_selling_date' === $filterData['sort_by']) {
                    $query->orderBy('last_selling_date', $filterData['sort_direction']);
                }

                if ('quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('quantity_sold', $filterData['sort_direction']);
                }

                if ('quantity_remaining' === $filterData['sort_by']) {
                    $query->orderBy('quantity_remaining', $filterData['sort_direction']);
                }

                if ('first_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('first_month_sold', $filterData['sort_direction']);
                }

                if ('second_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('second_month_sold', $filterData['sort_direction']);
                }

                if ('third_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('third_month_sold', $filterData['sort_direction']);
                }

                if ('fourth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('fourth_month_sold', $filterData['sort_direction']);
                }

                if ('fifth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('fifth_month_sold', $filterData['sort_direction']);
                }

                if ('sixth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('sixth_month_sold', $filterData['sort_direction']);
                }

                if ('seventh_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('seventh_month_sold', $filterData['sort_direction']);
                }

                if ('eighth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('eighth_month_sold', $filterData['sort_direction']);
                }

                if ('ninth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('ninth_month_sold', $filterData['sort_direction']);
                }

                if ('tenth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('tenth_month_sold', $filterData['sort_direction']);
                }

                if ('eleventh_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('eleventh_month_sold', $filterData['sort_direction']);
                }

                if ('twelfth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('twelfth_month_sold', $filterData['sort_direction']);
                }

                if ('age_of_the_product' === $filterData['sort_by']) {
                    $query->orderBy('age_category', $filterData['sort_direction']);
                }

                if ('age_category' === $filterData['sort_by']) {
                    $query->orderBy('age_category', $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('product_id', 'desc');
            });
    }

    public function getConsolidateProductsAgeingReportByMonthAndYear(array $filterData, int $companyId): ?ProductAgeing
    {
        return ProductAgeing::query()
            ->select(
                'product_id',
                'location_id',
                'product_created_at',
                'last_selling_date',
                'first_transfer_in',
                'first_goods_received_note',
                'quantity_sold',
                'quantity_remaining',
                'first_month_sold',
                'second_month_sold',
                'third_month_sold',
                'fourth_month_sold',
                'fifth_month_sold',
                'sixth_month_sold',
                'seventh_month_sold',
                'eighth_month_sold',
                'ninth_month_sold',
                'tenth_month_sold',
                'eleventh_month_sold',
                'twelfth_month_sold',
                DB::raw('CASE
                        WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN DATEDIFF(NOW(), first_goods_received_note)
                        WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN DATEDIFF(NOW(), first_transfer_in)
                        ELSE DATEDIFF(NOW(), product_created_at)
                    END AS age_category'),
                DB::raw(
                    'SUM(CASE WHEN DATEDIFF(NOW(), CASE WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN first_goods_received_note WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN first_transfer_in ELSE product_created_at END) <= 30 THEN 1 ELSE 0 END) AS age_category_0_30'
                ),
                DB::raw(
                    'SUM(CASE WHEN DATEDIFF(NOW(), CASE WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN first_goods_received_note WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN first_transfer_in ELSE product_created_at END) BETWEEN 31 AND 60 THEN 1 ELSE 0 END) AS age_category_31_60'
                ),
                DB::raw(
                    'SUM(CASE WHEN DATEDIFF(NOW(), CASE WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN first_goods_received_note WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN first_transfer_in ELSE product_created_at END) BETWEEN 61 AND 90 THEN 1 ELSE 0 END) AS age_category_61_90'
                ),
                DB::raw(
                    'SUM(CASE WHEN DATEDIFF(NOW(), CASE WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN first_goods_received_note WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN first_transfer_in ELSE product_created_at END) BETWEEN 91 AND 180 THEN 1 ELSE 0 END) AS age_category_91_180'
                ),
                DB::raw(
                    'SUM(CASE WHEN DATEDIFF(NOW(), CASE WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN first_goods_received_note WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN first_transfer_in ELSE product_created_at END) BETWEEN 181 AND 360 THEN 1 ELSE 0 END) AS age_category_181_360'
                ),
                DB::raw(
                    'SUM(CASE WHEN DATEDIFF(NOW(), CASE WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN first_goods_received_note WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN first_transfer_in ELSE product_created_at END) BETWEEN 361 AND 720 THEN 1 ELSE 0 END) AS age_category_361_720'
                ),
                DB::raw(
                    'SUM(CASE WHEN DATEDIFF(NOW(), CASE WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN first_goods_received_note WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN first_transfer_in ELSE product_created_at END) BETWEEN 721 AND 1080 THEN 1 ELSE 0 END) AS age_category_721_1080'
                ),
                DB::raw(
                    'SUM(CASE WHEN DATEDIFF(NOW(), CASE WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN first_goods_received_note WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN first_transfer_in ELSE product_created_at END) >= 1081 THEN 1 ELSE 0 END) AS age_category_1081_0'
                ),
            )
            ->selectRaw('SUM(quantity_sold) AS quantity_sold')
            ->selectRaw('SUM(quantity_remaining) AS quantity_remaining')
            ->selectRaw('SUM(first_month_sold) AS first_month_sold')
            ->selectRaw('SUM(second_month_sold) AS second_month_sold')
            ->selectRaw('SUM(third_month_sold) AS third_month_sold')
            ->selectRaw('SUM(fourth_month_sold) AS fourth_month_sold')
            ->selectRaw('SUM(fifth_month_sold) AS fifth_month_sold')
            ->selectRaw('SUM(sixth_month_sold) AS sixth_month_sold')
            ->selectRaw('SUM(seventh_month_sold) AS seventh_month_sold')
            ->selectRaw('SUM(eighth_month_sold) AS eighth_month_sold')
            ->selectRaw('SUM(ninth_month_sold) AS ninth_month_sold')
            ->selectRaw('SUM(tenth_month_sold) AS tenth_month_sold')
            ->selectRaw('SUM(eleventh_month_sold) AS eleventh_month_sold')
            ->selectRaw('SUM(twelfth_month_sold) AS twelfth_month_sold')
            ->where($this->filtersData($filterData, $companyId))
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('product' === $filterData['sort_by']) {
                    $query->orderBy('products.name', $filterData['sort_direction']);
                }

                if ('upc' === $filterData['sort_by']) {
                    $query->orderBy('products.upc', $filterData['sort_direction']);
                }

                if ('article_number' === $filterData['sort_by']) {
                    $query->orderBy('products.article_number', $filterData['sort_direction']);
                }

                if ('color' === $filterData['sort_by']) {
                    $query->orderBy('colors.name', $filterData['sort_direction']);
                }

                if ('size' === $filterData['sort_by']) {
                    $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                }

                if ('created_at' === $filterData['sort_by']) {
                    if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::CREATED_AT->value) {
                        $query->orderBy('product_created_at', $filterData['sort_direction']);
                    }

                    if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value) {
                        $query->orderBy('first_goods_received_note', $filterData['sort_direction']);
                    }

                    if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_TRANSFER_IN->value) {
                        $query->orderBy('first_transfer_in', $filterData['sort_direction']);
                    }
                }

                if ('last_selling_date' === $filterData['sort_by']) {
                    $query->orderBy('last_selling_date', $filterData['sort_direction']);
                }

                if ('first_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('first_month_sold', $filterData['sort_direction']);
                }

                if ('second_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('second_month_sold', $filterData['sort_direction']);
                }

                if ('third_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('third_month_sold', $filterData['sort_direction']);
                }

                if ('fourth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('fourth_month_sold', $filterData['sort_direction']);
                }

                if ('fifth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('fifth_month_sold', $filterData['sort_direction']);
                }

                if ('sixth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('sixth_month_sold', $filterData['sort_direction']);
                }

                if ('seventh_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('seventh_month_sold', $filterData['sort_direction']);
                }

                if ('eighth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('eighth_month_sold', $filterData['sort_direction']);
                }

                if ('ninth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('ninth_month_sold', $filterData['sort_direction']);
                }

                if ('tenth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('tenth_month_sold', $filterData['sort_direction']);
                }

                if ('eleventh_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('eleventh_month_sold', $filterData['sort_direction']);
                }

                if ('twelfth_month_quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('twelfth_month_sold', $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('product_id', 'desc');
            })
            ->first();
    }

    public function getPaginatedProductsAgeingReportByArticleNumber(
        array $filterData,
        int $companyId
    ): LengthAwarePaginator {
        return $this->commonQueriesForProductAgeingReportByArticleNumber($filterData, $companyId)
            ->paginate($filterData['per_page']);
    }

    public function getProductsAgeingReportByArticleNumberForExport(array $filterData, int $companyId): Collection
    {
        return $this->commonQueriesForProductAgeingReportByArticleNumber($filterData, $companyId)
            ->get();
    }

    public function getProductAgeingExportCountByArticleNumber(array $filterData, int $companyId): int
    {
        return ProductAgeing::query()
            ->join('products', 'products.id', '=', 'product_ageings.product_id')
            ->where($this->filtersDataByArticleNumber($filterData, $companyId))
            ->distinct('products.article_number')
            ->count('products.article_number');
    }

    public function exportProductAgeingRecordsForArticleNumber(
        array $filterData,
        int $companyId,
        int $skip,
        int $limit
    ): Collection {
        return $this->commonQueriesForProductAgeingReportByArticleNumber($filterData, $companyId)
            ->skip($skip)
            ->limit($limit)
            ->get();
    }

    public function getProductsAgeingReportForConsolidateByArticleNumber(
        array $filterData,
        int $companyId
    ): ?ProductAgeing {
        return ProductAgeing::query()
            ->select('product_id')
            ->selectRaw('SUM(quantity_sold) AS quantity_sold')
            ->selectRaw('SUM(quantity_remaining) AS quantity_remaining')
            ->where($this->filtersDataByArticleNumber($filterData, $companyId))
            ->orderBy('product_id', 'desc')
            ->join('products', 'products.id', '=', 'product_ageings.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->first();
    }

    public function getPaginatedProductsAgeingReportByUpc(
        array $filterData,
        int $companyId
    ): LengthAwarePaginator {
        return $this->commonQueriesForProductAgeingReportByUpc($filterData, $companyId)
            ->paginate($filterData['per_page']);
    }

    public function getProductsAgeingReportByUpcForExport(array $filterData, int $companyId): Collection
    {
        return $this->commonQueriesForProductAgeingReportByUpc($filterData, $companyId)
            ->get();
    }

    public function getProductAgeingExportCountByUpc(array $filterData, int $companyId): int
    {
        return ProductAgeing::query()
          ->join('products', 'products.id', '=', 'product_ageings.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
          ->where($this->filtersDataByUpc($filterData, $companyId))
          ->distinct('products.upc')
          ->count('products.upc');
    }

    public function exportProductAgeingRecordsForUpc(
        array $filterData,
        int $companyId,
        int $skip,
        int $limit
    ): Collection {
        return $this->commonQueriesForProductAgeingReportByUpc($filterData, $companyId)
            ->skip($skip)
            ->limit($limit)
            ->get();
    }

    public function getProductsAgeingReportForConsolidateByUpc(array $filterData, int $companyId): ?ProductAgeing
    {
        return ProductAgeing::query()
            ->select('product_id')
            ->selectRaw('SUM(quantity_sold) AS quantity_sold')
            ->selectRaw('SUM(quantity_remaining) AS quantity_remaining')
            ->where($this->filtersDataByUpc($filterData, $companyId))
            ->orderBy('product_id', 'desc')
            ->join('products', 'products.id', '=', 'product_ageings.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->first();
    }

    protected function filtersDataByUpc(array $filterData, int $companyId): Closure
    {
        $productQueries = new ProductQueries();
        $categoryQueries = new CategoryQueries();
        $tagQueries = new TagQueries();

        return fn ($query) => $query
            ->whereHas('product', function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->onlyActive()
                    ->where('is_non_selling_item', false);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('location_id', (array) $filterData['location_ids']);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData, $productQueries): void {
                $query->whereHas('product', function ($query) use ($filterData, $productQueries): void {
                    $query->where($productQueries->searchByCompoundNameForReport($filterData))
                        ->orWhere('article_number', $filterData['search_text']);
                });
            })
            ->when($filterData['article_numbers'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    $query->whereIn('article_number', $filterData['article_numbers']);
                });
            })
            ->when($filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('product_id', $filterData['product_id']);
            })
            ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
                        });
                    } else {
                        $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                    }
                });
            })
            ->when($filterData['color_ids'] && config('app.product_variant') === false, function ($query) use (
                $filterData
            ): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                });
            })
            ->when($filterData['size_ids'] && config('app.product_variant') === false, function ($query) use (
                $filterData
            ): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                });
            })
            ->when(
                isset($filterData['attributes']) && $filterData['attributes'] && config('app.product_variant') === true,
                function ($query) use ($filterData): void {
                    $query->whereHas('product', function ($query) use ($filterData): void {
                        $query->whereHas('productVariantValues', function ($subQuery) use ($filterData): void {
                            $subQuery->whereIn('value', $filterData['attributes']);
                        });
                    });
                }
            )
            ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
                        });
                    } else {
                        $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                    }
                });
            })
            ->when($filterData['category_ids'], function ($query) use ($filterData, $categoryQueries): void {
                $query->whereHas('product', function ($query) use ($filterData, $categoryQueries): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData, $categoryQueries): void {
                            $query->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']));
                        });
                    } else {
                        $query->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']));
                    }
                });
            })
            ->when($filterData['tag_ids'], function ($query) use ($filterData, $tagQueries): void {
                $query->whereHas('product', function ($query) use ($filterData, $tagQueries): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData, $tagQueries): void {
                            $query->whereHas('tags', $tagQueries->filterByIds($filterData['tag_ids']));
                        });
                    } else {
                        $query->whereHas('tags', $tagQueries->filterByIds($filterData['tag_ids']));
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
            )
            ->when(
                array_key_exists(
                    'last_selling_date_range',
                    $filterData
                ) && [] !== $filterData['last_selling_date_range'],
                function ($query) use ($filterData): void {
                    $query->where(
                        'last_selling_date',
                        '>=',
                        CommonFunctions::addStartTime($filterData['last_selling_date_range'][0])
                    )
                    ->where(
                        'last_selling_date',
                        '<=',
                        CommonFunctions::addEndTime($filterData['last_selling_date_range'][1])
                    );
                }
            );
    }

    protected function filtersDataByArticleNumber(array $filterData, int $companyId): Closure
    {
        $productQueries = new ProductQueries();
        $categoryQueries = new CategoryQueries();
        $tagQueries = new TagQueries();

        return fn ($query) => $query
            ->whereHas('product', function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->onlyActive()
                    ->where('is_non_selling_item', false);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('location_id', (array) $filterData['location_ids']);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData, $productQueries): void {
                $query->whereHas('product', function ($query) use ($filterData, $productQueries): void {
                    $query->where($productQueries->searchByCompoundNameForReport($filterData))
                        ->orWhere('article_number', $filterData['search_text']);
                });
            })
            ->when($filterData['article_numbers'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    $query->whereIn('article_number', $filterData['article_numbers']);
                });
            })
            ->when($filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('product_id', $filterData['product_id']);
            })
            ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
                        });
                    } else {
                        $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                    }
                });
            })
            ->when($filterData['color_ids'] && config('app.product_variant') === false, function ($query) use (
                $filterData
            ): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                });
            })
            ->when($filterData['size_ids'] && config('app.product_variant') === false, function ($query) use (
                $filterData
            ): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                });
            })
            ->when(
                isset($filterData['attributes']) && $filterData['attributes'] && config('app.product_variant') === true,
                function ($query) use ($filterData): void {
                    $query->whereHas('product', function ($query) use ($filterData): void {
                        $query->whereHas('productVariantValues', function ($subQuery) use ($filterData): void {
                            $subQuery->whereIn('value', $filterData['attributes']);
                        });
                    });
                }
            )
            ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
                        });
                    } else {
                        $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                    }
                });
            })
            ->when($filterData['category_ids'], function ($query) use ($filterData, $categoryQueries): void {
                $query->whereHas('product', function ($query) use ($filterData, $categoryQueries): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData, $categoryQueries): void {
                            $query->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']));
                        });
                    } else {
                        $query->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']));
                    }
                });
            })
            ->when($filterData['tag_ids'], function ($query) use ($filterData, $tagQueries): void {
                $query->whereHas('product', function ($query) use ($filterData, $tagQueries): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData, $tagQueries): void {
                            $query->whereHas('tags', $tagQueries->filterByIds($filterData['tag_ids']));
                        });
                    } else {
                        $query->whereHas('tags', $tagQueries->filterByIds($filterData['tag_ids']));
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
            )
            ->when(
                array_key_exists(
                    'last_selling_date_range',
                    $filterData
                ) && [] !== $filterData['last_selling_date_range'],
                function ($query) use ($filterData): void {
                    $query->where(
                        'last_selling_date',
                        '>=',
                        CommonFunctions::addStartTime($filterData['last_selling_date_range'][0])
                    )
                    ->where(
                        'last_selling_date',
                        '<=',
                        CommonFunctions::addEndTime($filterData['last_selling_date_range'][1])
                    );
                }
            );
    }

    protected function filtersData(array $filterData, int $companyId): Closure
    {
        $productQueries = new ProductQueries();
        $categoryQueries = new CategoryQueries();
        $tagQueries = new TagQueries();

        $commonCondition = function ($query, array $filterData, $ageCategory): void {
            [$startDays, $endDays] = AgeCategories::getDays((int) $ageCategory);
            if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::CREATED_AT->value) {
                if ($endDays > 0) {
                    $query->where(
                        'product_created_at',
                        '>=',
                        CommonFunctions::addStartTime(now()->subDays($endDays)->format('Y-m-d'))
                    );
                }

                if ($startDays > 0) {
                    $query->where(
                        'product_created_at',
                        '<=',
                        CommonFunctions::addEndTime(now()->subDays($startDays)->format('Y-m-d'))
                    );
                }
            }

            if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value) {
                if ($endDays > 0) {
                    $query->where(
                        'first_goods_received_note',
                        '>=',
                        CommonFunctions::addStartTime(now()->subDays($endDays)->format('Y-m-d'))
                    );
                }

                if ($startDays > 0) {
                    $query->where(
                        'first_goods_received_note',
                        '<=',
                        CommonFunctions::addEndTime(now()->subDays($startDays)->format('Y-m-d'))
                    );
                }
            }

            if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_TRANSFER_IN->value) {
                if ($endDays > 0) {
                    $query->where(
                        'first_transfer_in',
                        '>=',
                        CommonFunctions::addStartTime(now()->subDays($endDays)->format('Y-m-d'))
                    );
                }

                if ($startDays > 0) {
                    $query->where(
                        'first_transfer_in',
                        '<=',
                        CommonFunctions::addEndTime(now()->subDays($startDays)->format('Y-m-d'))
                    );
                }
            }
        };

        return fn ($query) => $query
            ->whereHas('product', function ($query) use ($companyId): void {
                $query->where('company_id', $companyId)
                    ->onlyActive()
                    ->where('is_non_selling_item', false);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('location_id', (array) $filterData['location_ids']);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData, $productQueries): void {
                $query->whereHas('product', function ($query) use ($filterData, $productQueries): void {
                    $query->where($productQueries->searchByCompoundNameForReport($filterData));
                });
            })
            ->when($filterData['article_numbers'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    $query->whereIn('article_number', $filterData['article_numbers']);
                });
            })
            ->when($filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('product_id', $filterData['product_id']);
            })
            ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
                        });
                    } else {
                        $query->whereIntegerInRaw('products.brand_id', $filterData['brand_ids']);
                    }
                });
            })
            ->when($filterData['color_ids'] && config('app.product_variant') === false, function ($query) use (
                $filterData
            ): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('products.color_id', $filterData['color_ids']);
                });
            })
            ->when($filterData['size_ids'] && config('app.product_variant') === false, function ($query) use (
                $filterData
            ): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('products.size_id', $filterData['size_ids']);
                });
            })
            ->when(
                isset($filterData['attributes']) && $filterData['attributes'] && config('app.product_variant') === true,
                function ($query) use ($filterData): void {
                    $query->whereHas('product', function ($query) use ($filterData): void {
                        $query->whereHas('productVariantValues', function ($subQuery) use ($filterData): void {
                            $subQuery->whereIn('value', $filterData['attributes']);
                        });
                    });
                }
            )
            ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                            $query->whereIntegerInRaw('department_id', $filterData['department_ids']);
                        });
                    } else {
                        $query->whereIntegerInRaw('products.department_id', $filterData['department_ids']);
                    }
                });
            })
            ->when($filterData['category_ids'], function ($query) use ($filterData, $categoryQueries): void {
                $query->whereHas('product', function ($query) use ($filterData, $categoryQueries): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData, $categoryQueries): void {
                            $query->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']));
                        });
                    } else {
                        $query->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']));
                    }
                });
            })
            ->when($filterData['tag_ids'], function ($query) use ($filterData, $tagQueries): void {
                $query->whereHas('product', function ($query) use ($filterData, $tagQueries): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData, $tagQueries): void {
                            $query->whereHas('tags', $tagQueries->filterByIds($filterData['tag_ids']));
                        });
                    } else {
                        $query->whereHas('tags', $tagQueries->filterByIds($filterData['tag_ids']));
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
            )
            ->when(
                array_key_exists(
                    'last_selling_date_range',
                    $filterData
                ) && [] !== $filterData['last_selling_date_range'],
                function ($query) use ($filterData): void {
                    $query->where(
                        'last_selling_date',
                        '>=',
                        CommonFunctions::addStartTime($filterData['last_selling_date_range'][0])
                    )
                    ->where(
                        'last_selling_date',
                        '<=',
                        CommonFunctions::addEndTime($filterData['last_selling_date_range'][1])
                    );
                }
            )
            ->when((int) $filterData['age_category_id'] > 0, function ($query) use (
                $filterData,
                $commonCondition
            ): void {
                $commonCondition($query, $filterData, $filterData['age_category_id']);
            })
            ->when((int) $filterData['age_category_id'] === 0, function ($query) use ($filterData): void {
                if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value) {
                    $query->whereNotNull('first_goods_received_note');
                }

                if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_TRANSFER_IN->value) {
                    $query->whereNotNull('first_transfer_in');
                }
            });
    }

    private function commonQueriesForProductAgeingReport(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $inventoryUpdateQueries = new InventoryUpdateQueries();
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        $relations = [
            'product:' . $productQueries->getBasicColumnsForProductAgeing(),
            'product.inventoryUpdates:' . $inventoryUpdateQueries->getBasicColumns(),
            'location:' . $locationQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return ProductAgeing::query()
            ->select(
                'product_id',
                'location_id',
                'product_created_at',
                'last_selling_date',
                'first_transfer_in',
                'first_goods_received_note',
                'quantity_sold',
                'quantity_remaining',
                'first_month_sold',
                'second_month_sold',
                'third_month_sold',
                'fourth_month_sold',
                'fifth_month_sold',
                'sixth_month_sold',
                'seventh_month_sold',
                'eighth_month_sold',
                'ninth_month_sold',
                'tenth_month_sold',
                'eleventh_month_sold',
                'twelfth_month_sold',
                DB::raw('CASE
                        WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value . ' THEN DATEDIFF(NOW(), first_goods_received_note)
                        WHEN ' . (int) $filterData['age_of_product_type'] . ' = ' . AgeOfProductTypes::FIRST_TRANSFER_IN->value . ' THEN DATEDIFF(NOW(), first_transfer_in)
                        ELSE DATEDIFF(NOW(), product_created_at)
                    END AS age_category'),
            )
            ->with($relations)
            ->where($this->filtersData($filterData, $companyId))
            ->join('products', 'products.id', '=', 'product_ageings.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            }, function ($query): void {
                $query->leftJoin('colors', 'colors.id', '=', 'products.color_id')
                      ->leftJoin('sizes', 'sizes.id', '=', 'products.size_id');
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('product' === $filterData['sort_by']) {
                    $query->orderBy('products.name', $filterData['sort_direction']);
                }

                if ('upc' === $filterData['sort_by']) {
                    $query->orderBy('products.upc', $filterData['sort_direction']);
                }

                if ('article_number' === $filterData['sort_by']) {
                    $query->orderBy('products.article_number', $filterData['sort_direction']);
                }

                if ('color' === $filterData['sort_by']) {
                    $query->orderBy('colors.name', $filterData['sort_direction']);
                }

                if ('size' === $filterData['sort_by']) {
                    $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                }

                if ('created_at' === $filterData['sort_by']) {
                    if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::CREATED_AT->value) {
                        $query->orderBy('product_created_at', $filterData['sort_direction']);
                    }

                    if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_GOODS_RECEIVED_NOTE->value) {
                        $query->orderBy('first_goods_received_note', $filterData['sort_direction']);
                    }

                    if ((int) $filterData['age_of_product_type'] === AgeOfProductTypes::FIRST_TRANSFER_IN->value) {
                        $query->orderBy('first_transfer_in', $filterData['sort_direction']);
                    }
                }

                if ('last_selling_date' === $filterData['sort_by']) {
                    $query->orderBy('last_selling_date', $filterData['sort_direction']);
                }

                if ('quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('quantity_sold', $filterData['sort_direction']);
                }

                if ('quantity_remaining' === $filterData['sort_by']) {
                    $query->orderBy('quantity_remaining', $filterData['sort_direction']);
                }

                if ('age_of_the_product' === $filterData['sort_by']) {
                    $query->orderBy('age_category', $filterData['sort_direction']);
                }

                if ('age_category' === $filterData['sort_by']) {
                    $query->orderBy('age_category', $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('product_id', 'desc');
            });
    }

    private function commonQueriesForProductAgeingReportByArticleNumber(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();
        $inventoryUpdateQueries = new InventoryUpdateQueries();

        return ProductAgeing::query()
            ->select(
                'product_id',
                'product_created_at',
                DB::raw('MAX(last_selling_date) as last_selling_date'),
                DB::raw('MAX(first_transfer_in) as first_transfer_in'),
                DB::raw('MAX(first_goods_received_note) as first_goods_received_note'),
                DB::raw('SUM(quantity_sold) as quantity_sold'),
                DB::raw('SUM(quantity_remaining) as quantity_remaining'),
                'first_month_sold',
                'second_month_sold',
                'third_month_sold',
                'fourth_month_sold',
                'fifth_month_sold',
                'sixth_month_sold',
                'seventh_month_sold',
                'eighth_month_sold',
                'ninth_month_sold',
                'tenth_month_sold',
                'eleventh_month_sold',
                'twelfth_month_sold',
                DB::raw('DATEDIFF(NOW(), product_created_at) as age_category_based_on_created_at'),
                DB::raw(
                    'IF(MAX(first_goods_received_note) IS NULL, NULL, DATEDIFF(NOW(), MAX(first_goods_received_note))) as age_category_based_on_first_goods_received_note'
                ),
                DB::raw(
                    'IF(MAX(first_transfer_in) IS NULL, NULL, DATEDIFF(NOW(), MAX(first_transfer_in))) as age_category_based_on_first_transfer_in'
                )
            )
            ->with([
                'product:' . $productQueries->getBasicColumnsForProductAgeing(),
                'product.inventoryUpdates:' . $inventoryUpdateQueries->getBasicColumns(),
            ])
            ->join('products', 'products.id', '=', 'product_ageings.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->where($this->filtersDataByArticleNumber($filterData, $companyId))
            ->groupBy('products.article_number')
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('product' === $filterData['sort_by']) {
                    $query->orderBy('products.name', $filterData['sort_direction']);
                }

                if ('upc' === $filterData['sort_by']) {
                    $query->orderBy('products.upc', $filterData['sort_direction']);
                }

                if ('article_number' === $filterData['sort_by']) {
                    $query->orderBy('products.article_number', $filterData['sort_direction']);
                }

                if ('color' === $filterData['sort_by']) {
                    $query->orderBy('colors.name', $filterData['sort_direction']);
                }

                if ('size' === $filterData['sort_by']) {
                    $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                }

                if ('last_selling_date' === $filterData['sort_by']) {
                    $query->orderBy('last_selling_date', $filterData['sort_direction']);
                }

                if ('first_transfer_in_date' === $filterData['sort_by']) {
                    $query->orderBy('first_transfer_in', $filterData['sort_direction']);
                }

                if ('first_grn_date' === $filterData['sort_by']) {
                    $query->orderBy('first_goods_received_note', $filterData['sort_direction']);
                }

                if ('created_at' === $filterData['sort_by']) {
                    $query->orderBy('product_created_at', $filterData['sort_direction']);
                }

                if ('quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('quantity_sold', $filterData['sort_direction']);
                }

                if ('quantity_remaining' === $filterData['sort_by']) {
                    $query->orderBy('quantity_remaining', $filterData['sort_direction']);
                }

                if ('age_of_the_product' === $filterData['sort_by']) {
                    $query->orderBy('age_category_based_on_created_at', $filterData['sort_direction']);
                }

                if ('age_of_the_product_first_grn' === $filterData['sort_by']) {
                    $query->orderBy('age_category_based_on_first_goods_received_note', $filterData['sort_direction']);
                }

                if ('age_of_the_product_first_transfer_in' === $filterData['sort_by']) {
                    $query->orderBy('age_category_based_on_first_transfer_in', $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('product_id', 'desc');
            });
    }

    private function commonQueriesForProductAgeingReportByUpc(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();
        $inventoryUpdateQueries = new InventoryUpdateQueries();
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        $relations = [
            'product:' . $productQueries->getBasicColumnsForProductAgeing(),
            'product.inventoryUpdates:' . $inventoryUpdateQueries->getBasicColumns(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return ProductAgeing::query()
            ->select(
                'product_id',
                'product_created_at',
                DB::raw('MAX(last_selling_date) as last_selling_date'),
                DB::raw('MAX(first_transfer_in) as first_transfer_in'),
                DB::raw('MAX(first_goods_received_note) as first_goods_received_note'),
                DB::raw('SUM(quantity_sold) as quantity_sold'),
                DB::raw('SUM(quantity_remaining) as quantity_remaining'),
                'first_month_sold',
                'second_month_sold',
                'third_month_sold',
                'fourth_month_sold',
                'fifth_month_sold',
                'sixth_month_sold',
                'seventh_month_sold',
                'eighth_month_sold',
                'ninth_month_sold',
                'tenth_month_sold',
                'eleventh_month_sold',
                'twelfth_month_sold',
                DB::raw('DATEDIFF(NOW(), product_created_at) as age_category_based_on_created_at'),
                DB::raw(
                    'IF(MAX(first_goods_received_note) IS NULL, NULL, DATEDIFF(NOW(), MAX(first_goods_received_note))) as age_category_based_on_first_goods_received_note'
                ),
                DB::raw(
                    'IF(MAX(first_transfer_in) IS NULL, NULL, DATEDIFF(NOW(), MAX(first_transfer_in))) as age_category_based_on_first_transfer_in'
                )
            )
            ->with($relations)
            ->join('products', 'products.id', '=', 'product_ageings.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            }, function ($query): void {
                $query->leftJoin('colors', 'colors.id', '=', 'products.color_id')
                    ->leftJoin('sizes', 'sizes.id', '=', 'products.size_id');
            })
            ->where($this->filtersDataByUpc($filterData, $companyId))
            ->groupBy('products.id')
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('product' === $filterData['sort_by']) {
                    $query->orderBy('products.name', $filterData['sort_direction']);
                }

                if ('upc' === $filterData['sort_by']) {
                    $query->orderBy('products.upc', $filterData['sort_direction']);
                }

                if ('article_number' === $filterData['sort_by']) {
                    $query->orderBy('products.article_number', $filterData['sort_direction']);
                }

                if ('color' === $filterData['sort_by']) {
                    $query->orderBy('colors.name', $filterData['sort_direction']);
                }

                if ('size' === $filterData['sort_by']) {
                    $query->orderBy('sizes.sort_order', $filterData['sort_direction']);
                }

                if ('last_selling_date' === $filterData['sort_by']) {
                    $query->orderBy('last_selling_date', $filterData['sort_direction']);
                }

                if ('first_transfer_in_date' === $filterData['sort_by']) {
                    $query->orderBy('first_transfer_in', $filterData['sort_direction']);
                }

                if ('first_grn_date' === $filterData['sort_by']) {
                    $query->orderBy('first_goods_received_note', $filterData['sort_direction']);
                }

                if ('created_at' === $filterData['sort_by']) {
                    $query->orderBy('product_created_at', $filterData['sort_direction']);
                }

                if ('quantity_sold' === $filterData['sort_by']) {
                    $query->orderBy('quantity_sold', $filterData['sort_direction']);
                }

                if ('quantity_remaining' === $filterData['sort_by']) {
                    $query->orderBy('quantity_remaining', $filterData['sort_direction']);
                }

                if ('age_of_the_product' === $filterData['sort_by']) {
                    $query->orderBy('age_category_based_on_created_at', $filterData['sort_direction']);
                }

                if ('age_of_the_product_first_grn' === $filterData['sort_by']) {
                    $query->orderBy('age_category_based_on_first_goods_received_note', $filterData['sort_direction']);
                }

                if ('age_of_the_product_first_transfer_in' === $filterData['sort_by']) {
                    $query->orderBy('age_category_based_on_first_transfer_in', $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('product_id', 'desc');
            });
    }

    public function getBasicColumns(): array
    {
        return [
            'product_id', 'location_id', 'product_created_at', 'last_selling_date', 'first_transfer_in', 'first_goods_received_note', 'quantity_sold', 'quantity_remaining', 'first_month_sold', 'second_month_sold', 'third_month_sold', 'fourth_month_sold', 'fifth_month_sold', 'sixth_month_sold', 'seventh_month_sold', 'eighth_month_sold', 'ninth_month_sold', 'tenth_month_sold', 'eleventh_month_sold', 'twelfth_month_sold',
        ];
    }

    public function getDetailsByProductIdAndLocationId(int $productId, int $locationId): ?ProductAgeing
    {
        return ProductAgeing::query()
            ->select('product_id', 'location_id', 'first_transfer_in', 'first_goods_received_note')
            ->where('product_id', $productId)
            ->where('product_id', $locationId)
            ->first();
    }
}
