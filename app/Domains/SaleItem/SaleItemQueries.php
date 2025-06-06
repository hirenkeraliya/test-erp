<?php

declare(strict_types=1);

namespace App\Domains\SaleItem;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Cashback\CashbackQueries;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Services\ProductVariantFilterService;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyCampaign\LoyaltyCampaignQueries;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Sale\Enums\DiscountTypeReports;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SaleItemAssemblyChildProduct\SaleItemAssemblyChildProductQueries;
use App\Domains\SaleItemComplimentary\SaleItemComplimentaryQueries;
use App\Domains\SaleItemDiscount\Enums\DiscountableTypes;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\SaleItemPriceOverride\SaleItemPriceOverrideQueries;
use App\Domains\SaleItemUnit\SaleItemUnitQueries;
use App\Domains\SalePayment\SalePaymentQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Models\BoxProduct;
use App\Models\Sale;
use App\Models\SaleItem;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SaleItemQueries
{
    public function getPaginatedMemberSalesReportList(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getMemberSalesReportListQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getPaginatedMemberSalesReportListForStoreManager(
        array $filterData,
        int $locationId
    ): LengthAwarePaginator {
        return $this->getMemberSalesReportListForStoreManager($filterData, $locationId)->paginate(
            $filterData['per_page']
        );
    }

    public function getPaginatedMemberSalesListForExport(array $filterData, int $companyId): Collection
    {
        return $this->getMemberSalesReportListQuery($filterData, $companyId)->get();
    }

    public function addNew(
        Sale $sale,
        array $item,
        float $itemSubTotal,
        float $itemTax,
        float $itemCartDiscount,
        float $itemDiscountAmount,
        ?int $exchangeReturnItemId = null,
    ): SaleItem {
        $totalPricePaid = array_key_exists('total_price_paid', $item)
            ? (float) $item['total_price_paid']
            : $itemSubTotal - $itemCartDiscount - $itemDiscountAmount + $itemTax;

        $pricePaidPerUnit = $totalPricePaid / (float) $item['quantity'];
        $totalDiscountAmount = $itemCartDiscount + $itemDiscountAmount;

        $saleItem = SaleItem::create([
            'sale_id' => $sale->getKey(),
            'product_id' => $item['id'],
            'quantity' => $item['quantity'],
            'derivative_id' => array_key_exists(
                'derivative_id',
                $item
            ) && $item['derivative_id'] ? $item['derivative_id'] : null,
            'price_based_on_derivative' => array_key_exists(
                'price_based_on_derivative',
                $item
            ) && $item['price_based_on_derivative'] ? (float) $item['price_based_on_derivative'] : 0.00,
            'quantity_of_derivative' => array_key_exists(
                'quantity_of_derivative',
                $item
            ) && $item['quantity_of_derivative'] ? (float) $item['quantity_of_derivative'] : 0.00,
            'price_paid_of_derivative' => array_key_exists(
                'price_paid_of_derivative',
                $item
            ) && $item['price_paid_of_derivative'] ? (float) $item['price_paid_of_derivative'] : 0.00,
            'sale_return_item_id' => $exchangeReturnItemId,
            'group_id' => array_key_exists('group_id', $item) && $item['group_id'] ? $item['group_id'] : null,
            'original_price_per_unit' => $item['price'] ?? $item['open_price'],
            'item_discount_amount' => CommonFunctions::numberFormat($itemDiscountAmount),
            'cart_discount_amount' => CommonFunctions::numberFormat($itemCartDiscount),
            'total_discount_amount' => CommonFunctions::numberFormat($totalDiscountAmount),
            'price_paid_per_unit' => CommonFunctions::numberFormat($pricePaidPerUnit),
            'total_price_paid' => CommonFunctions::numberFormat($totalPricePaid),
            'total_tax_amount' => $itemTax,
            'is_exchange' => $exchangeReturnItemId && array_key_exists('is_exchange', $item) && $item['is_exchange'],
            'discount_item_sequence' => array_key_exists(
                'discount_item_sequence',
                $item
            ) && $item['discount_item_sequence'] ? $item['discount_item_sequence'] : null,
            'vendor_commission_percentage' => $item['vendor_commission_percentage'],
        ]);

        if (array_key_exists('promoter_ids', $item)) {
            $saleItem->promoters()->attach($item['promoter_ids']);
        }

        return $saleItem;
    }

    public function updateTotalPricePaid(SaleItem $saleItem, float $totalPricePaid): void
    {
        $saleItem->total_price_paid = $totalPricePaid;
        $saleItem->save();
    }

    public function updateBoxProductDetails(SaleItem $saleItem, BoxProduct $boxProduct): void
    {
        $saleItem->box_product_id = $boxProduct->id;
        $saleItem->product_box_package_type_id = $boxProduct->package_type_id;
        $saleItem->product_box_units = $boxProduct->units;
        $saleItem->save();
    }

    public static function getColumnNamesForPos(): string
    {
        return 'id,sale_id,product_id,quantity,returned_quantity,original_price_per_unit,cart_discount_amount,item_discount_amount,total_discount_amount,total_tax_amount,price_paid_per_unit,total_price_paid,box_product_id,is_exchange,group_id,derivative_id,price_based_on_derivative,quantity_of_derivative,price_paid_of_derivative';
    }

    public static function getColumnNamesForDiscountReport(): string
    {
        return 'id,sale_id,product_id,quantity';
    }

    public static function getSaleIdColumn(): string
    {
        return 'id,sale_id';
    }

    public static function getColumnNamesForPromoterCommissionReport(): string
    {
        return 'id,sale_id,quantity,product_id';
    }

    public static function getColumnNamesForSaleUpdate(): string
    {
        return 'id,sale_id,cart_discount_amount,item_discount_amount,total_discount_amount,total_tax_amount,total_price_paid';
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_id,product_id,quantity,returned_quantity,price_paid_per_unit,total_price_paid';
    }

    public function getBasicColumnNamesForSaleReturnReport(): string
    {
        return 'id,sale_id,product_id,quantity,returned_quantity,price_paid_per_unit,total_price_paid,is_exchange';
    }

    public function getBasicColumnForGeneralSalesPromoter(): string
    {
        return 'id,sale_id,product_id,quantity,total_discount_amount,total_price_paid,original_price_per_unit';
    }

    public function getBasicColumnNamesForReturnAndExchangeReport(): string
    {
        return 'id,sale_id,product_id,quantity,sale_return_item_id,total_discount_amount,total_price_paid,original_price_per_unit';
    }

    public function getBasicColumnNamesForSaleExchanges(): string
    {
        return 'id,sale_id,quantity,returned_quantity,original_price_per_unit,price_paid_per_unit,total_price_paid,total_discount_amount,total_tax_amount,box_product_id,product_box_package_type_id,product_box_units';
    }

    public function getBasicColumnNamesInArray(): array
    {
        return ['id', 'sale_id', 'product_id', 'quantity', 'returned_quantity', 'total_price_paid'];
    }

    public function getColumnsForPaginatedVoidSales(): string
    {
        return 'id,sale_id,product_id,quantity,original_price_per_unit,cart_discount_amount,item_discount_amount,total_discount_amount,total_tax_amount,price_paid_per_unit,total_price_paid,returned_quantity,box_product_id,is_exchange';
    }

    public function getColumnsForCompleteLayawaySale(): string
    {
        return 'id,sale_id,original_price_per_unit,quantity,total_discount_amount,total_tax_amount,total_price_paid,returned_quantity,price_paid_per_unit,product_id,box_product_id,is_exchange';
    }

    public function getColumnsForCompleteCreditSale(): string
    {
        return 'id,sale_id,original_price_per_unit,quantity,total_discount_amount,total_tax_amount,total_price_paid,returned_quantity,price_paid_per_unit,is_exchange';
    }

    public static function getColumnNamesForCloseCounter(): string
    {
        return 'id,sale_id,total_price_paid';
    }

    public function updateLayawayAmountOf(Sale $sale, float $totalPaymentAmount, bool $isCompletedLayawaySale): void
    {
        $sale->fresh();

        $totalAmount = $sale->total_amount_paid + $sale->layaway_pending_amount;

        $saleItems = $sale->getSaleItems();
        $saleItems = $saleItems->sortBy('total_price_paid')->values();

        $lastKey = $saleItems->keys()->last();
        $itemWiseTotalPaidAmount = 0;
        foreach ($saleItems as $saleItemKey => $saleItem) {
            $itemSubtotal = $saleItem->original_price_per_unit * $saleItem->quantity;
            $itemSubtotal -= $saleItem->total_discount_amount;
            $itemSubtotal += $saleItem->total_tax_amount;

            $totalPricePaid = CommonFunctions::numberFormat($totalPaymentAmount * $itemSubtotal / $totalAmount);

            if ($saleItemKey === $lastKey) {
                $totalPricePaid = CommonFunctions::numberFormat($totalPaymentAmount - $itemWiseTotalPaidAmount);
            }

            $itemWiseTotalPaidAmount += $totalPricePaid;

            $saleItem->total_price_paid += $totalPricePaid;

            if ($isCompletedLayawaySale) {
                $saleItem->total_price_paid = $saleItem->quantity * $saleItem->price_paid_per_unit;
            }

            $saleItem->save();
        }
    }

    public function updateCreditAmountOf(Sale $sale, float $totalPaymentAmount, bool $isCompletedCreditSale): void
    {
        $sale->fresh();

        $totalAmount = $sale->total_amount_paid + $sale->credit_pending_amount;
        $saleItems = $sale->getSaleItems();
        $saleItems = $saleItems->sortBy('total_price_paid')->values();

        $lastKey = $saleItems->keys()->last();
        $itemWiseTotalPaidAmount = 0;
        foreach ($saleItems as $saleItemKey => $saleItem) {
            $itemSubtotal = $saleItem->original_price_per_unit * $saleItem->quantity;
            $itemSubtotal -= $saleItem->total_discount_amount;
            $itemSubtotal += $saleItem->total_tax_amount;

            $totalPricePaid = CommonFunctions::numberFormat($totalPaymentAmount * $itemSubtotal / $totalAmount);

            if ($saleItemKey === $lastKey) {
                $totalPricePaid = CommonFunctions::numberFormat($totalPaymentAmount - $itemWiseTotalPaidAmount);
            }

            $itemWiseTotalPaidAmount += $totalPricePaid;

            $saleItem->total_price_paid += $totalPricePaid;

            if ($isCompletedCreditSale) {
                $saleItem->total_price_paid = $saleItem->quantity * $saleItem->price_paid_per_unit;
            }

            $saleItem->save();
        }
    }

    public function getByIdsWithRelations(array $ids): Collection
    {
        $saleQueries = new SaleQueries();
        $cashbackQueries = new CashbackQueries();
        $salePaymentQueries = new SalePaymentQueries();
        $loyaltyPointQueries = new LoyaltyPointQueries();
        $loyaltyCampaignQueries = new LoyaltyCampaignQueries();
        $saleItemUnitQueries = new SaleItemUnitQueries();
        $brandQueries = new BrandQueries();
        $productQueries = new ProductQueries();
        $saleItemDiscountQueries = new SaleItemDiscountQueries();
        $saleDiscountQueries = new SaleDiscountQueries();
        $memberQueries = resolve(MemberQueries::class);
        $saleItemAssemblyChildProductQueries = new SaleItemAssemblyChildProductQueries();

        return SaleItem::query()
            ->select(
                'id',
                'sale_id',
                'is_exchange',
                'product_id',
                'quantity',
                'returned_quantity',
                'total_price_paid',
                'price_paid_per_unit',
                'original_price_per_unit',
                'cart_discount_amount',
                'item_discount_amount',
                'total_discount_amount',
                'total_tax_amount',
                'box_product_id',
                'product_box_package_type_id',
                'product_box_units'
            )
            ->with([
                'sale:' . $saleQueries->getBasicColumnNames(),
                'sale.member:' . $memberQueries->getBasicColumnNamesForPosSale(),
                'sale.saleDiscounts:' . $saleDiscountQueries->getBasicColumnNames(),
                'saleItemDiscounts:' . $saleItemDiscountQueries->getBasicColumnNames(),
                'saleItemDiscounts.discountable',
                'sale.cashback:' . $cashbackQueries->getBasicColumnNames(),
                'sale.payments:' . $salePaymentQueries->getBasicColumnNames(),
                'sale.issuedLoyaltyPoints:' . $loyaltyPointQueries->getBasicColumnNames(),
                'sale.issuedLoyaltyPoints.loyaltyCampaign:' . $loyaltyCampaignQueries->getBasicColumnNames(),
                'sale.issuedLoyaltyPoints.loyaltyCampaign.excludedBrands:' . $brandQueries->getIdAndNameColumnNames(),
                'saleItemUnits:' . $saleItemUnitQueries->getBasicColumnNames(),
                'product:' . $productQueries->getBasicColumnNames(),
                'saleItemAssemblyChildProducts:' . $saleItemAssemblyChildProductQueries->getBasicColumnNames(),
            ])
            ->whereIntegerInRaw('id', $ids)
            ->get();
    }

    public function getByIds(array $ids): Collection
    {
        return SaleItem::query()
            ->select(
                'id',
                'sale_id',
                'product_id',
                'quantity',
                'returned_quantity',
                'total_price_paid',
                'price_paid_per_unit',
                'original_price_per_unit',
                'cart_discount_amount',
                'item_discount_amount',
                'total_discount_amount',
                'total_tax_amount',
            )
            ->whereIntegerInRaw('id', $ids)
            ->get();
    }

    public function incrementReturnedQuantity(SaleItem $saleItem, float $quantity): void
    {
        $saleItem->returned_quantity += $quantity;
        $saleItem->save();
    }

    public function getBasicColumns(): string
    {
        return 'id,sale_id,product_id,total_price_paid';
    }

    public function filterByPromoterId(int $promoterId): Closure
    {
        return fn ($query) => $query->whereHas(
            'promoters',
            function ($query) use ($promoterId): void {
                $query->where('promoter_id', $promoterId);
            }
        );
    }

    public function filterByRegularCreditAndLayawaySaleByCounterUpdateId(int $counterUpdateId): Closure
    {
        $saleQueries = resolve(SaleQueries::class);

        return fn ($query) => $query->whereHas(
            'sale',
            $saleQueries->filterByRegularCreditAndLayawaySaleByCounterUpdateId($counterUpdateId)
        );
    }

    public function filterByStoreId(int $locationId): Closure
    {
        $saleQueries = resolve(SaleQueries::class);

        return fn ($query) => $query->whereHas('sale', function ($query) use ($saleQueries, $locationId): void {
            $query->select('id')->onlyRegularCompleteCreditAndCompleteLayawaySale()
                ->where($saleQueries->filterByStoreId($locationId));
        });
    }

    public function getPaginatedMemberSalesListForExportInStoreManagerPanel(
        array $filterData,
        int $locationId
    ): Collection {
        return $this->getMemberSalesReportListForStoreManager($filterData, $locationId)->get();
    }

    public function getBasicColumnNamesForSaleReturn(): string
    {
        return 'id,original_price_per_unit,box_product_id,is_exchange';
    }

    public function update(
        int $saleItemId,
        float $taxDifference,
        float $currentItemTax,
        float $totalDiscountAmount,
        SaleItem $oldSaleItem
    ): void {
        /** @var SaleItem $$saleItem */
        $saleItem = SaleItem::select(
            'id',
            'quantity',
            'original_price_per_unit',
            'item_discount_amount',
            'total_discount_amount',
            'total_price_paid',
            'total_tax_amount',
            'price_paid_per_unit'
        )
            ->find($saleItemId);

        $subTotal = $saleItem->quantity * $saleItem->original_price_per_unit;
        $totalPricePaid = ($subTotal - $totalDiscountAmount + $currentItemTax);
        $saleItem->update([
            'item_discount_amount' => $totalDiscountAmount,
            'total_discount_amount' => $totalDiscountAmount,
            'price_paid_per_unit' => $totalPricePaid / $saleItem->quantity,
            'total_price_paid' => $totalPricePaid,
            'box_product_id' => $oldSaleItem->box_product_id,
            'product_box_package_type_id' => $oldSaleItem->product_box_package_type_id,
            'product_box_units' => $oldSaleItem->product_box_units,
        ]);
    }

    public function getGeneralSalesReportByProduct(array $filterData, bool $excludeProductsWithNoPrice): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantFilterService = resolve(ProductVariantFilterService::class);

        $relations = [
            'product' => function ($query) use ($productQueries, $excludeProductsWithNoPrice): void {
                $columns = explode(',', $productQueries->getBasicColumnNames());
                $query->select(...$columns)
                    ->when($excludeProductsWithNoPrice, function ($query): void {
                        $query->where('retail_price', '>', 0);
                    });
            },
            'sale:' . $saleQueries->getBasicColumnNames(),
            'sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'sale.counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
            'sale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
            'promoters:' . $promoterQueries->getBasicColumnNames(),
            'promoters.employee:' . $employeeQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.brand:' . $brandQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return SaleItem::query()
            ->select(
                'id',
                'sale_id',
                'price_paid_per_unit',
                'quantity',
                'product_id',
                'original_price_per_unit',
                'total_discount_amount',
                'total_tax_amount',
                'price_paid_per_unit',
                'total_price_paid'
            )
            ->with($relations)
            ->whereHas('product', function ($query) use ($productQueries, $excludeProductsWithNoPrice): void {
                $columns = explode(',', $productQueries->getBasicColumnNames());
                $query->select(...$columns)
                    ->when($excludeProductsWithNoPrice, function ($query): void {
                        $query->where('retail_price', '>', 0);
                    });
            })
            ->whereHas('sale', function ($query) use ($saleQueries, $filterData): void {
                $query->select('id')->onlyRegularCompleteCreditAndCompleteLayawaySale()
                    ->where($saleQueries->filterByStoreIds($filterData['location_ids']))
                    ->when(
                        isset($filterData['e_invoice_submitted']) && null != $filterData['e_invoice_submitted'],
                        function ($query) use ($filterData): void {
                            $query->whereNot('digital_invoice_submitted', $filterData['e_invoice_submitted']);
                        }
                    )
                    ->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date_range']));
            })
            ->when(null !== $filterData['department_ids'], function ($query) use (
                $filterData,
                $productVariantFilterService
            ): void {
                $query->whereIn(
                    'product_id',
                    $productVariantFilterService->filterByDepartmentAndBrandIds(
                        'department_id',
                        $filterData['department_ids']
                    )
                );
            })
            ->when(null !== $filterData['brand_ids'], function ($query) use (
                $filterData,
                $productVariantFilterService
            ): void {
                $query->whereIn(
                    'product_id',
                    $productVariantFilterService->filterByDepartmentAndBrandIds('brand_id', $filterData['brand_ids'])
                );
            })
            ->when(null !== $filterData['promoter_ids'], function ($query) use (
                $filterData,
                $promoterQueries
            ): void {
                $query->whereHas('promoters', function ($query) use ($filterData, $promoterQueries): void {
                    $query->where($promoterQueries->filterByPromoterIds($filterData['promoter_ids']));
                });
            })
            ->when(null !== $filterData['counter_ids'], function ($query) use ($filterData): void {
                $query->whereHas('sale', function ($query) use ($filterData): void {
                    $query->select('id', 'counter_update_id')
                        ->onlyRegularCompleteCreditAndCompleteLayawaySale()
                        ->whereHas('counterUpdate', function ($query) use ($filterData): void {
                            $query->select('id')
                                ->whereIntegerInRaw('counter_id', $filterData['counter_ids']);
                        });
                });
            })
            ->isNotExchange()
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getGeneralSalesReportBySummary(array $filterData, bool $excludeProductsWithNoPrice): Collection
    {
        $promoterQueries = resolve(PromoterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return $this->getCommonGeneralSalesReportByDateAndBrandAndBySummaryQuery(
            $filterData,
            $excludeProductsWithNoPrice
        )
            ->with([
                'sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'sale.counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
                'promoters:' . $promoterQueries->getBasicColumnNames(),
                'promoters.employee:' . $employeeQueries->getBasicColumnNames(),
            ])
            ->get();
    }

    public function getGeneralSalesReportByDateAndBrand(array $filterData, bool $excludeProductsWithNoPrice): Collection
    {
        $promoterQueries = resolve(PromoterQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $brandQueries = resolve(BrandQueries::class);

        $relations = [
            'sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'sale.counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
            'promoters:' . $promoterQueries->getBasicColumnNames(),
            'promoters.employee:' . $employeeQueries->getBasicColumnNames(),
            'product:' . $productQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, ['product.brand:' . $brandQueries->getBasicColumnNames()]);
        }

        return $this->getCommonGeneralSalesReportByDateAndBrandAndBySummaryQuery(
            $filterData,
            $excludeProductsWithNoPrice
        )
        ->with($relations)->get();
    }

    public function getByStoreForTopCategoryExport(array $filterData): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $relations = [
            'product' => function ($query) use ($productQueries): void {
                $columns = explode(',', $productQueries->getBasicColumnsName());
                $query->select(...$columns);
            },
            'sale:' . $saleQueries->getBasicColumnNames(),
            'sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'sale.counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
            'sale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.categories:' . $categoryQueries->getBasicColumnNames(),
            ]);
        }

        return SaleItem::query()
            ->select(
                'id',
                'quantity',
                'product_id',
                'sale_id',
                'total_discount_amount',
                'total_price_paid',
                'total_tax_amount',
            )
            ->isNotExchange()
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
            ->whereHas('sale', function ($query) use ($saleQueries, $counterUpdateQueries, $filterData): void {
                $query->select('id')->onlyRegularCompleteCreditAndCompleteLayawaySale()
                    ->where($saleQueries->filterByStoreIds($filterData['location_ids']))
                    ->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date_range']))
                    ->whereHas('counterUpdate', function ($query) use ($filterData, $counterUpdateQueries): void {
                        $query->when(null !== $filterData['counter_ids'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where($counterUpdateQueries->filterByCounterIds($filterData['counter_ids']));
                        })
                        ->when(null !== $filterData['cashier_ids'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where($counterUpdateQueries->filterByCashierIds($filterData['cashier_ids']));
                        });
                    });
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getByStoreForTopStyleExport(array $filterData): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);

        return SaleItem::query()
            ->select(
                'id',
                'quantity',
                'product_id',
                'sale_id',
                'total_discount_amount',
                'total_price_paid',
                'total_tax_amount',
            )
            ->isNotExchange()
            ->with([
                'product' => function ($query) use ($productQueries): void {
                    $columns = explode(',', $productQueries->getBasicColumnsName());
                    $query->select(...$columns);
                },
                'product.categories:' . $categoryQueries->getBasicColumnNames(),
                'sale:' . $saleQueries->getBasicColumnNames(),
                'sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'sale.counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
                'sale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
            ])
            ->whereHas('product', function ($query): void {
                $query->where('is_non_selling_item', false);
            })
            ->whereHas('sale', function ($query) use ($saleQueries, $counterUpdateQueries, $filterData): void {
                $query->select('id')->onlyRegularCompleteCreditAndCompleteLayawaySale()
                    ->where($saleQueries->filterByStoreIds($filterData['location_ids']))
                    ->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date_range']))
                    ->whereHas('counterUpdate', function ($query) use ($filterData, $counterUpdateQueries): void {
                        $query->when(null !== $filterData['counter_ids'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where($counterUpdateQueries->filterByCounterIds($filterData['counter_ids']));
                        })
                        ->when(null !== $filterData['cashier_ids'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where($counterUpdateQueries->filterByCashierIds($filterData['cashier_ids']));
                        });
                    });
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getByStoreForTopAttributeExport(array $filterData): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        return SaleItem::query()
            ->select(
                'id',
                'quantity',
                'product_id',
                'sale_id',
                'total_discount_amount',
                'total_price_paid',
                'total_tax_amount',
            )
            ->isNotExchange()
            ->with([
                'product' => function ($query) use ($productQueries): void {
                    $columns = explode(',', $productQueries->getBasicColumnsName());
                    $query->select(...$columns);
                },
                'product.productVariantValue' => function ($query) use ($filterData): void {
                    $query->select('id', 'value', 'attribute_id', 'product_id')->where(
                        'attribute_id',
                        $filterData['attribute_type']
                    );
                },
                'product.productVariantValue.attribute:' . $attributeQueries->getBasicColumnNames(),
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'sale:' . $saleQueries->getBasicColumnNames(),
                'sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'sale.counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
                'sale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
            ])
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
            ->whereHas('sale', function ($query) use ($saleQueries, $counterUpdateQueries, $filterData): void {
                $query->select('id')->onlyRegularCompleteCreditAndCompleteLayawaySale()
                    ->where($saleQueries->filterByStoreIds($filterData['location_ids']))
                    ->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date_range']))
                    ->whereHas('counterUpdate', function ($query) use ($filterData, $counterUpdateQueries): void {
                        $query->when(null !== $filterData['counter_ids'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where($counterUpdateQueries->filterByCounterIds($filterData['counter_ids']));
                        })
                        ->when(null !== $filterData['cashier_ids'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where($counterUpdateQueries->filterByCashierIds($filterData['cashier_ids']));
                        });
                    });
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getByStoreForTopColorExport(array $filterData): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return SaleItem::query()
            ->select(
                'id',
                'quantity',
                'product_id',
                'sale_id',
                'total_discount_amount',
                'total_price_paid',
                'total_tax_amount',
            )
            ->isNotExchange()
            ->with([
                'product' => function ($query) use ($productQueries): void {
                    $columns = explode(',', $productQueries->getBasicColumnsName());
                    $query->select(...$columns);
                },
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'sale:' . $saleQueries->getBasicColumnNames(),
                'sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'sale.counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
                'sale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
            ])
            ->whereHas('product', function ($query): void {
                $query->where('is_non_selling_item', false);
            })
            ->whereHas('sale', function ($query) use ($saleQueries, $counterUpdateQueries, $filterData): void {
                $query->select('id')->onlyRegularCompleteCreditAndCompleteLayawaySale()
                    ->where($saleQueries->filterByStoreIds($filterData['location_ids']))
                    ->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date_range']))
                    ->whereHas('counterUpdate', function ($query) use ($filterData, $counterUpdateQueries): void {
                        $query->when(null !== $filterData['counter_ids'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where($counterUpdateQueries->filterByCounterIds($filterData['counter_ids']));
                        })
                        ->when(null !== $filterData['cashier_ids'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where($counterUpdateQueries->filterByCashierIds($filterData['cashier_ids']));
                        });
                    });
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getByStoreForTopBrandExport(array $filterData): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $relations = [
            'product' => function ($query) use ($productQueries): void {
                $columns = explode(',', $productQueries->getBasicColumnsName());
                $query->select(...$columns);
            },
            'sale:' . $saleQueries->getBasicColumnNames(),
            'sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'sale.counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
            'sale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, ['product.brand:' . $brandQueries->getBasicColumnNames()]);
        }

        return SaleItem::query()
            ->select(
                'id',
                'quantity',
                'product_id',
                'sale_id',
                'total_discount_amount',
                'total_price_paid',
                'total_tax_amount',
            )
            ->isNotExchange()
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
            ->whereHas('sale', function ($query) use ($saleQueries, $counterUpdateQueries, $filterData): void {
                $query->select('id')->onlyRegularCompleteCreditAndCompleteLayawaySale()
                    ->where($saleQueries->filterByStoreIds($filterData['location_ids']))
                    ->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date_range']))
                    ->whereHas('counterUpdate', function ($query) use ($filterData, $counterUpdateQueries): void {
                        $query->when(null !== $filterData['counter_ids'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where($counterUpdateQueries->filterByCounterIds($filterData['counter_ids']));
                        })
                        ->when(null !== $filterData['cashier_ids'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where($counterUpdateQueries->filterByCashierIds($filterData['cashier_ids']));
                        });
                    });
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getByStoreForTopArticleNumberExport(array $filterData): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $relations = [
            'product' => function ($query) use ($productQueries): void {
                $columns = explode(',', $productQueries->getBasicColumnsName());
                $query->select(...$columns);
            },
            'sale:' . $saleQueries->getBasicColumnNames(),
            'sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'sale.counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
            'sale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
            ]);
        }

        return SaleItem::query()
            ->select(
                'id',
                'quantity',
                'product_id',
                'sale_id',
                'total_discount_amount',
                'total_price_paid',
                'total_tax_amount',
            )
            ->isNotExchange()
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
            ->whereHas('sale', function ($query) use ($saleQueries, $counterUpdateQueries, $filterData): void {
                $query->select('id')->onlyRegularCompleteCreditAndCompleteLayawaySale()
                    ->where($saleQueries->filterByStoreIds($filterData['location_ids']))
                    ->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date_range']))
                    ->whereHas('counterUpdate', function ($query) use ($filterData, $counterUpdateQueries): void {
                        $query->when(null !== $filterData['counter_ids'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where($counterUpdateQueries->filterByCounterIds($filterData['counter_ids']));
                        })
                        ->when(null !== $filterData['cashier_ids'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where($counterUpdateQueries->filterByCashierIds($filterData['cashier_ids']));
                        });
                    });
            })
            ->orderBy('quantity', 'desc')
            ->get();
    }

    public function getByStoreForTopProductExport(array $filterData): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $relations = [
            'product' => function ($query) use ($productQueries): void {
                $columns = explode(',', $productQueries->getBasicColumnsName());
                $query->select(...$columns);
            },
            'sale:' . $saleQueries->getBasicColumnNames(),
            'sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'sale.counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
            'sale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
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

        return SaleItem::query()
            ->select(
                'id',
                'quantity',
                'product_id',
                'sale_id',
                'total_discount_amount',
                'total_price_paid',
                'total_tax_amount',
            )
            ->isNotExchange()
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
            ->whereHas('sale', function ($query) use ($saleQueries, $counterUpdateQueries, $filterData): void {
                $query->select('id')->onlyRegularCompleteCreditAndCompleteLayawaySale()
                    ->where($saleQueries->filterByStoreIds($filterData['location_ids']))
                    ->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date_range']))
                    ->whereHas('counterUpdate', function ($query) use ($filterData, $counterUpdateQueries): void {
                        $query->when(null !== $filterData['counter_ids'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where($counterUpdateQueries->filterByCounterIds($filterData['counter_ids']));
                        })
                        ->when(null !== $filterData['cashier_ids'], function ($query) use (
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->where($counterUpdateQueries->filterByCashierIds($filterData['cashier_ids']));
                        });
                    });
            })
            ->orderBy('quantity', 'desc')
            ->get();
    }

    public function getForGeneralSalesReportBySalesDate(array $filterData, bool $excludeProductsWithNoPrice): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return SaleItem::query()
            ->select(
                'id',
                'sale_id',
                'price_paid_per_unit',
                'quantity',
                'product_id',
                'original_price_per_unit',
                'total_discount_amount',
                'total_tax_amount',
                'price_paid_per_unit',
                'total_price_paid'
            )
            ->with([
                'product' => function ($query) use ($productQueries, $excludeProductsWithNoPrice): void {
                    $columns = explode(',', $productQueries->getBasicColumnNames());
                    $query->select(...$columns)
                        ->when($excludeProductsWithNoPrice, function ($query): void {
                            $query->where('retail_price', '>', 0);
                        });
                },
                'sale:' . $saleQueries->getBasicColumnsForReport(),
                'sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'sale.counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
                'sale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'promoters:' . $promoterQueries->getBasicColumnNames(),
                'promoters.employee:' . $employeeQueries->getBasicColumnNames(),
            ])
            ->whereHas('product', function ($query) use ($productQueries, $excludeProductsWithNoPrice): void {
                $columns = explode(',', $productQueries->getBasicColumnNames());
                $query->select(...$columns)
                    ->when($excludeProductsWithNoPrice, function ($query): void {
                        $query->where('retail_price', '>', 0);
                    });
            })
            ->whereHas('sale', function ($query) use ($saleQueries, $filterData): void {
                $query->select('id')
                    ->onlyRegularCompleteCreditAndCompleteLayawaySale()
                    ->where($saleQueries->filterByStoreIds($filterData['location_ids']))
                    ->when(
                        isset($filterData['e_invoice_submitted']) && null != $filterData['e_invoice_submitted'],
                        function ($query) use ($filterData): void {
                            $query->whereNot('digital_invoice_submitted', $filterData['e_invoice_submitted']);
                        }
                    )
                    ->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date_range']));
            })
            ->when(null !== $filterData['department_ids'], function ($query) use ($filterData): void {
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
            ->when(null !== $filterData['brand_ids'], function ($query) use ($filterData): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    if (config('app.product_variant')) {
                        $query->select('products.id')
                            ->from('products')
                            ->join('master_products', 'products.master_product_id', '=', 'master_products.id')
                            ->whereIntegerInRaw('master_products.brand_id', $filterData['brand_ids']);
                    } else {
                        $query->select('products.id')
                            ->from('products')
                            ->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
                    }
                });
            })
            ->when(null !== $filterData['promoter_ids'], function ($query) use (
                $filterData,
                $promoterQueries
            ): void {
                $query->whereHas('promoters', function ($query) use ($filterData, $promoterQueries): void {
                    $query->where($promoterQueries->filterByPromoterIds($filterData['promoter_ids']));
                });
            })
            ->when(null !== $filterData['counter_ids'], function ($query) use ($filterData): void {
                $query->whereHas('sale', function ($query) use ($filterData): void {
                    $query->select('id', 'counter_update_id')
                        ->onlyRegularCompleteCreditAndCompleteLayawaySale()
                        ->whereHas('counterUpdate', function ($query) use ($filterData): void {
                            $query->select('id')
                                ->whereIntegerInRaw('counter_id', $filterData['counter_ids']);
                        });
                });
            })
            ->isNotExchange()
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getForGeneralSalesReportBySalesDateColorAndSize(
        array $filterData,
        bool $excludeProductsWithNoPrice
    ): Collection {
        $productQueries = resolve(ProductQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return SaleItem::query()
            ->select(
                'id',
                'sale_id',
                'price_paid_per_unit',
                'quantity',
                'product_id',
                'original_price_per_unit',
                'total_discount_amount',
                'total_tax_amount',
                'price_paid_per_unit',
                'total_price_paid'
            )
            ->with([
                'product' => function ($query) use ($productQueries, $excludeProductsWithNoPrice): void {
                    $columns = explode(',', $productQueries->getBasicColumnNames());
                    $query->select(...$columns)
                        ->when($excludeProductsWithNoPrice, function ($query): void {
                            $query->where('retail_price', '>', 0);
                        });
                },
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
                'sale:' . $saleQueries->getBasicColumnNames(),
                'sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'sale.counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
                'sale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'promoters:' . $promoterQueries->getBasicColumnNames(),
                'promoters.employee:' . $employeeQueries->getBasicColumnNames(),
            ])
            ->whereHas('product', function ($query) use ($productQueries, $excludeProductsWithNoPrice): void {
                $columns = explode(',', $productQueries->getBasicColumnNames());
                $query->select(...$columns)
                    ->when($excludeProductsWithNoPrice, function ($query): void {
                        $query->where('retail_price', '>', 0);
                    });
            })
            ->whereHas('sale', function ($query) use ($saleQueries, $filterData): void {
                $query->select('id')
                    ->onlyRegularCompleteCreditAndCompleteLayawaySale()
                    ->where($saleQueries->filterByStoreIds($filterData['location_ids']))
                    ->when(
                        isset($filterData['e_invoice_submitted']) && null != $filterData['e_invoice_submitted'],
                        function ($query) use ($filterData): void {
                            $query->whereNot('digital_invoice_submitted', $filterData['e_invoice_submitted']);
                        }
                    )
                    ->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date_range']));
            })
            ->when(null !== $filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    $query->select('id')
                        ->from('products')
                        ->whereIntegerInRaw('department_id', $filterData['department_ids']);
                });
            })
            ->when(null !== $filterData['brand_ids'], function ($query) use ($filterData): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    $query->select('id')
                        ->from('products')
                        ->whereIntegerInRaw('brand_id', $filterData['brand_ids']);
                });
            })
            ->when(null !== $filterData['promoter_ids'], function ($query) use (
                $filterData,
                $promoterQueries
            ): void {
                $query->whereHas('promoters', function ($query) use ($filterData, $promoterQueries): void {
                    $query->where($promoterQueries->filterByPromoterIds($filterData['promoter_ids']));
                });
            })
            ->when(null !== $filterData['counter_ids'], function ($query) use ($filterData): void {
                $query->whereHas('sale', function ($query) use ($filterData): void {
                    $query->select('id', 'counter_update_id')
                        ->onlyRegularCompleteCreditAndCompleteLayawaySale()
                        ->whereHas('counterUpdate', function ($query) use ($filterData): void {
                            $query->select('id')
                                ->whereIntegerInRaw('counter_id', $filterData['counter_ids']);
                        });
                });
            })
            ->isNotExchange()
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getForGeneralSalesReportBySalesDateAttribute(
        array $filterData,
        bool $excludeProductsWithNoPrice
    ): Collection {
        $productQueries = resolve(ProductQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $saleQueries = resolve(SaleQueries::class);

        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $brandQueries = resolve(BrandQueries::class);

        return SaleItem::query()
            ->select(
                'id',
                'sale_id',
                'price_paid_per_unit',
                'quantity',
                'product_id',
                'original_price_per_unit',
                'total_discount_amount',
                'total_tax_amount',
                'price_paid_per_unit',
                'total_price_paid'
            )
            ->with([
                'product' => function ($query) use ($productQueries, $excludeProductsWithNoPrice): void {
                    $columns = explode(',', $productQueries->getBasicColumnNames());
                    $query->select(...$columns)
                        ->when($excludeProductsWithNoPrice, function ($query): void {
                            $query->where('retail_price', '>', 0);
                        });
                },
                'sale:' . $saleQueries->getBasicColumnNames(),
                'sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'sale.counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
                'sale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
                'promoters:' . $promoterQueries->getBasicColumnNames(),
                'promoters.employee:' . $employeeQueries->getBasicColumnNames(),
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ])
            ->whereHas('product', function ($query) use ($productQueries, $excludeProductsWithNoPrice): void {
                $columns = explode(',', $productQueries->getBasicColumnNames());
                $query->select(...$columns)
                    ->when($excludeProductsWithNoPrice, function ($query): void {
                        $query->where('retail_price', '>', 0);
                    });
            })
            ->whereHas('sale', function ($query) use ($saleQueries, $filterData): void {
                $query->select('id')
                    ->onlyRegularCompleteCreditAndCompleteLayawaySale()
                    ->where($saleQueries->filterByStoreIds($filterData['location_ids']))
                    ->when(
                        isset($filterData['e_invoice_submitted']) && null != $filterData['e_invoice_submitted'],
                        function ($query) use ($filterData): void {
                            $query->whereNot('digital_invoice_submitted', $filterData['e_invoice_submitted']);
                        }
                    )
                    ->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date_range']));
            })
            ->when(null !== $filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('department_id', (array) $filterData['department_ids']);
                    });
                });
            })
            ->when(null !== $filterData['brand_ids'], function ($query) use ($filterData): void {
                $query->whereHas('product', function ($query) use ($filterData): void {
                    $query->whereHas('masterProduct', function ($query) use ($filterData): void {
                        $query->whereIntegerInRaw('brand_id', (array) $filterData['brand_ids']);
                    });
                });
            })
            ->when(null !== $filterData['promoter_ids'], function ($query) use (
                $filterData,
                $promoterQueries
            ): void {
                $query->whereHas('promoters', function ($query) use ($filterData, $promoterQueries): void {
                    $query->where($promoterQueries->filterByPromoterIds($filterData['promoter_ids']));
                });
            })
            ->when(null !== $filterData['counter_ids'], function ($query) use ($filterData): void {
                $query->whereHas('sale', function ($query) use ($filterData): void {
                    $query->select('id', 'counter_update_id')
                        ->onlyRegularCompleteCreditAndCompleteLayawaySale()
                        ->whereHas('counterUpdate', function ($query) use ($filterData): void {
                            $query->select('id')
                                ->whereIntegerInRaw('counter_id', $filterData['counter_ids']);
                        });
                });
            })
            ->isNotExchange()
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getGeneralSalesReportBySummaryWithMonthQuery(
        array $filterData,
        bool $excludeProductsWithNoPrice
    ): Collection {
        $saleQueries = resolve(SaleQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);

        return SaleItem::query()
            ->select(
                DB::raw('month(sales.happened_at) as month'),
                DB::raw('SUM(sale_items.total_price_paid) as sales_amount'),
                'counters.location_id as location_id',
                'locations.name as location_name',
                'product_id',
            )
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->whereIntegerInRaw('sales.status', SaleStatus::getOnlyLayawayAndCreditCompleteSaleStatusValues())
            ->when(
                isset($filterData['e_invoice_submitted']) && null != $filterData['e_invoice_submitted'],
                function ($query) use ($filterData): void {
                    $query->whereNot('sales.digital_invoice_submitted', $filterData['e_invoice_submitted']);
                }
            )
            ->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date_range']))
            ->when(null !== $filterData['counter_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('counters.id', $filterData['counter_ids']);
            })
            ->when(null !== $filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
            })
            ->isNotExchange()
            ->when(null !== $filterData['department_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw(
                    config('app.product_variant') ? 'master_products.department_id' : 'products.department_id',
                    $filterData['department_ids']
                );
            })
            ->when($excludeProductsWithNoPrice, function ($query): void {
                $query->where('products.retail_price', '>', 0);
            })
            ->when(null !== $filterData['brand_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw(
                    config('app.product_variant') ? 'master_products.brand_id' : 'products.brand_id',
                    $filterData['brand_ids']
                );
            })
            ->when(null !== $filterData['promoter_ids'], function ($query) use (
                $filterData,
                $promoterQueries
            ): void {
                $query->whereHas('promoters', function ($query) use ($filterData, $promoterQueries): void {
                    $query->where($promoterQueries->filterByPromoterIds($filterData['promoter_ids']));
                });
            })
            ->groupBY(['location_id', 'month'])
            ->get();
    }

    public function getTotalQuantitiesBy(string $previousDate, string $currentDate, int $employeeId): int
    {
        $saleQueries = resolve(SaleQueries::class);
        $saleItems = SaleItem::select('id', 'sale_id', 'quantity')
            ->whereHas(
                'sale',
                function ($query) use ($saleQueries, $previousDate, $currentDate, $employeeId): void {
                    $query->select('id')
                    ->withoutVoidSale()
                    ->whereHas('member', function ($query) use ($employeeId): void {
                        $query->where('employee_id', $employeeId);
                    })
                    ->where($saleQueries->filterByHappenedAtWithinDateRange([$previousDate, $currentDate]));
                }
            )
            ->isNotExchange()
            ->get();

        return (int) $saleItems->sum('quantity');
    }

    public function getAllDataWithSalesAndDiscounts(array $filterData): Collection
    {
        $saleQueries = resolve(SaleQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $saleItemDiscountQueries = new SaleItemDiscountQueries();
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $saleItemComplimentaryQueries = resolve(SaleItemComplimentaryQueries::class);
        $saleItemPriceOverrideQueries = resolve(SaleItemPriceOverrideQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        if (config('app.product_variant')) {
            $relations = [
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'product.masterProduct.tags:' . $tagQueries->getBasicColumnNames(),
                'product.masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ];
        } else {
            $relations = [
                'product.brand:' . $brandQueries->getBasicColumnNames(),
                'product.tags:' . $tagQueries->getBasicColumnNames(),
                'product.department:' . $departmentQueries->getBasicColumnNames(),
                'product.style:' . $styleQueries->getBasicColumnNames(),
            ];
        }

        return SaleItem::query()
            ->select('id', 'sale_id', 'product_id', 'quantity', 'returned_quantity')
            ->with([
                'product' => function ($query) use ($productQueries): void {
                    $columns = explode(',', $productQueries->getColumnsForDiscountReports());
                    $query->select(...$columns);
                },
                ...$relations,
                'sale:' . $saleQueries->getBasicColumnNames(),
                'sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'sale.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'sale.counterUpdate.counter.location:' . $locationQueries->getNameColumnName(),
                'sale.counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'sale.counterUpdate.cashier.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                'saleItemDiscounts' => function ($query) use ($saleItemDiscountQueries, $filterData): void {
                    $columns = explode(',', $saleItemDiscountQueries->getBasicColumnNames());
                    $query->select(...$columns)
                        ->when(null !== $filterData['report_type'], function ($query) use ($filterData): void {
                            $query->when(
                                (int) $filterData['report_type'] === DiscountTypeReports::DREAM_PRICE->value,
                                function ($query): void {
                                    $query->where('discountable_type', DiscountableTypes::DREAM_PRICE->name);
                                }
                            )
                                ->when(
                                    (int) $filterData['report_type'] === DiscountTypeReports::COMPLIMENTARY->value,
                                    function ($query): void {
                                        $query->where(
                                            'discountable_type',
                                            DiscountableTypes::COMPLIMENTARY_ITEM_REASON->name
                                        );
                                    }
                                )
                                ->when(
                                    (int) $filterData['report_type'] === DiscountTypeReports::PROMOTION->value,
                                    function ($query): void {
                                        $query->where('discountable_type', DiscountableTypes::PROMOTION->name);
                                    }
                                )
                                ->when(
                                    (int) $filterData['report_type'] === DiscountTypeReports::PRICE_OVERRIDE->value,
                                    function ($query): void {
                                        $query->where(
                                            'discountable_type',
                                            DiscountableTypes::SALE_ITEM_PRICE_OVERRIDE->name
                                        );
                                    }
                                )
                                ->when(
                                    (int) $filterData['report_type'] === DiscountTypeReports::HAPPY_HOUR_DISCOUNT->value,
                                    function ($query): void {
                                        $query->where(
                                            'discountable_type',
                                            DiscountableTypes::HAPPY_HOUR_DISCOUNT->name
                                        );
                                    }
                                );
                        });
                },
                'saleItemComplimentary:' . $saleItemComplimentaryQueries->getBasicColumnNames(),
                'saleItemComplimentary.authorizer:' . $this->getMorphLocationBasicColumns(),
                'saleItemComplimentary.authorizer.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
                'saleItemPriceOverride:' . $saleItemPriceOverrideQueries->getBasicColumnNames(),
                'saleItemPriceOverride.negotiator:' . $this->getMorphLocationBasicColumns(),
                'saleItemPriceOverride.negotiator.employee:' . $employeeQueries->getFirstAndLastNameColumns(),
            ])
            ->whereHas('product', function ($query): void {
                if (config('app.product_variant')) {
                    $query->whereHas('masterProduct', function ($query): void {
                        $query->where('is_non_selling_item', false);
                    });
                } else {
                    $query->where('is_non_selling_item', false);
                }
            })
            ->whereHas('saleItemDiscounts', function ($query): void {
                $query->whereNotNull('amount');
            })
            ->whereHas('sale', function ($query) use ($filterData, $saleQueries): void {
                $query->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->where($saleQueries->filterByStoreIds($filterData['location_ids']))
                    ->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date_range']));
            })
            ->when(null !== $filterData['report_type'], function ($query) use ($filterData): void {
                $query->whereHas('saleItemDiscounts', function ($query) use ($filterData): void {
                    $query->when(
                        (int) $filterData['report_type'] === DiscountTypeReports::DREAM_PRICE->value,
                        function ($query): void {
                            $query->where('discountable_type', DiscountableTypes::DREAM_PRICE->name);
                        }
                    )
                        ->when(
                            (int) $filterData['report_type'] === DiscountTypeReports::COMPLIMENTARY->value,
                            function ($query): void {
                                $query->where('discountable_type', DiscountableTypes::COMPLIMENTARY_ITEM_REASON->name);
                            }
                        )
                        ->when(
                            (int) $filterData['report_type'] === DiscountTypeReports::PROMOTION->value,
                            function ($query): void {
                                $query->where('discountable_type', DiscountableTypes::PROMOTION->name);
                            }
                        )
                        ->when(
                            (int) $filterData['report_type'] === DiscountTypeReports::PRICE_OVERRIDE->value,
                            function ($query): void {
                                $query->where('discountable_type', DiscountableTypes::SALE_ITEM_PRICE_OVERRIDE->name);
                            }
                        )
                        ->when(
                            (int) $filterData['report_type'] === DiscountTypeReports::HAPPY_HOUR_DISCOUNT->value,
                            function ($query): void {
                                $query->where('discountable_type', DiscountableTypes::HAPPY_HOUR_DISCOUNT->name);
                            }
                        );
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
            ->when(null !== $filterData['style_ids'] && config('app.product_variant') === false, function ($query) use (
                $filterData
            ): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    $query->select('id')
                        ->from('products')
                        ->whereIntegerInRaw('style_id', $filterData['style_ids']);
                });
            })
            ->when(
                null !== $filterData['attribute_values'] && config('app.product_variant') === true,
                function ($query) use ($filterData): void {
                    $query->whereHas('product.productVariantValues', function ($query) use ($filterData): void {
                        $query->where('attribute_id', $filterData['attribute_type'])
                              ->whereIn('value', $filterData['attribute_values']);
                    });
                }
            )
            ->when(null !== $filterData['tag_ids'], function ($query) use ($filterData, $tagQueries): void {
                $query->whereHas('product', function ($query) use ($tagQueries, $filterData): void {
                    if (config('app.product_variant')) {
                        $query->whereHas('masterProduct', function ($query) use ($filterData, $tagQueries): void {
                            $query->select('id')
                                ->whereHas('tags', $tagQueries->filterByIds($filterData['tag_ids']));
                        });
                    } else {
                        $query->select('id')
                            ->whereHas('tags', $tagQueries->filterByIds($filterData['tag_ids']));
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
            ->get();
    }

    public function getPromotersWiseSales(array $filteredData, int $promoterId): Collection
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return SaleItem::query()
            ->select(
                'sale_items.id',
                'sale_items.sale_id',
                'sale_items.quantity',
                'sale_items.total_price_paid',
                'sales.offline_sale_id as receipt_id'
            )
            ->selectRaw('DATE(sales.happened_at) as date')
            ->whereHas('sale', function ($query) use ($filteredData, $counterUpdateQueries): void {
                $query->select('id')->where(
                    'happened_at',
                    '>=',
                    CommonFunctions::addStartTime($filteredData['start_date'])
                )
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->where('happened_at', '<=', CommonFunctions::addEndTime($filteredData['end_date']))
                    ->whereHas(
                        'counterUpdate',
                        $counterUpdateQueries->filterByStoreId((int) $filteredData['location_id'])
                    );
            })
            ->join('sales', 'sales.id', 'sale_items.sale_id')
            ->whereHas('promoters', function ($query) use ($promoterId): void {
                $query->select('id')->where('id', $promoterId);
            })
            ->get();
    }

    public function getSaleItemWithProductsAndPromoters(int $itemId, int $promoterId): SaleItem
    {
        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        return SaleItem::query()
            ->select('id', 'product_id', 'quantity', 'total_price_paid')
            ->with([
                'promoters' => function ($query) use ($promoterId, $employeeQueries): void {
                    $query->select('id', 'employee_id', 'code')
                        ->with('employee:' . $employeeQueries->getBasicColumnNames())
                        ->whereNot('id', $promoterId);
                },
                'product:' . $productQueries->getBasicColumns(),
                'product.brand:' . $brandQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
                'product.department:' . $departmentQueries->getBasicColumnNames(),
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'product.masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ])
            ->findOrFail($itemId);
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $saleQueries = resolve(SaleQueries::class);

        $saleItems = SaleItem::query()
            ->select('id', 'sale_id', 'product_id')
            ->whereHas('sale', $saleQueries->filterByCompanyId($companyId))
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($saleItems as $saleItem) {
            $saleItem->product_id = $newProductId;
            $saleItem->save();
        }
    }

    public function getCachedTodaySalesForDashboard(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string $startDate,
        string $endDate,
        bool $refresh = false
    ): SaleItem {
        $cacheKey = null !== $locationId ? 'cache-today-sales-dashboard-' . $locationId . $brandId . $startDate . $endDate : 'cache-today-sales-dashboard-' . $startDate . $endDate . $brandId;

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): SaleItem => SaleItem::query()->select(
                DB::raw('SUM(sale_items.total_price_paid) as total_amount'),
                DB::raw('SUM(sale_items.quantity) as total_units_sold'),
                DB::raw('COUNT(DISTINCT sales.id) as total_sales_count'),
            )
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                ->join('locations', 'counters.location_id', '=', 'locations.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->where('locations.company_id', $companyId)
                ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                    $query->where('counters.location_id', $locationId);
                })
                ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                    $query->where('products.brand_id', $brandId);
                })
                ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($startDate))
                ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($endDate))
                ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                ->firstOrFail()
        );
    }

    public function getPaginatedEmployeeSalesReportList(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getEmployeeSalesReportListQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function getPaginatedEmployeeSalesListForExport(array $filterData, int $companyId): Collection
    {
        return $this->getEmployeeSalesReportListQuery($filterData, $companyId)->get();
    }

    public function getPaginatedEmployeeSalesReportListForStoreManager(
        array $filterData,
        int $locationId,
        int $companyId
    ): LengthAwarePaginator {
        return $this->getEmployeeSalesReportListForStoreManager($filterData, $locationId, $companyId)->paginate(
            $filterData['per_page']
        );
    }

    public function getPaginatedEmployeeSalesListForExportInStoreManagerPanel(
        array $filterData,
        int $locationId,
        int $companyId
    ): Collection {
        return $this->getEmployeeSalesReportListForStoreManager($filterData, $locationId, $companyId)->get();
    }

    public function getSelectIdColumn(): Closure
    {
        return fn ($query) => $query->select('id');
    }

    public function getOfflineSaleWithRelation(): Closure
    {
        $saleQueries = resolve(SaleQueries::class);

        return fn ($query) => $query->select('id', 'sale_id')
            ->with(['sale:' . $saleQueries->getOfflineSaleIdWithStatus()]);
    }

    public function getSaleAndProductRelationColumns(): Closure
    {
        $saleQueries = resolve(SaleQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [
            'sale:' . $saleQueries->getOfflineSaleId(),
            'product:' . $productQueries->getBasicColumns(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.brand:' . $brandQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return fn ($query) => $query->select('id', 'sale_id', 'product_id')
            ->with($relations);
    }

    public function getSaleProductAndCounterRelationColumns(): Closure
    {
        $saleQueries = resolve(SaleQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [
            'sale:' . $saleQueries->getBasicColumnsForReport(),
            'sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'sale.counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
            'sale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
            'product:' . $productQueries->getBasicColumns(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.brand:' . $brandQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return fn ($query) => $query->select('id', 'sale_id', 'product_id', 'quantity')
            ->with($relations);
    }

    public function getSaleProductAndPromoterRelationColumns(): Closure
    {
        $saleQueries = resolve(SaleQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        return fn ($query) => $query->select('id', 'sale_id', 'product_id', 'quantity')
            ->with([
                'sale:' . $saleQueries->getOfflineSaleId(),
                'product:' . $productQueries->getBasicColumns(),
                'product.brand:' . $brandQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
                'promoters:' . $promoterQueries->getBasicColumnNames(),
                'promoters.employee:' . $employeeQueries->getBasicColumnNames(),
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
    }

    public function getIdSaleIdAndQuantityWithSaleRelation(): Closure
    {
        $saleQueries = resolve(SaleQueries::class);

        return fn ($query) => $query->select('id', 'sale_id', 'quantity')
            ->with(['sale:' . $saleQueries->getOfflineSaleId()]);
    }

    public function getSaleDetailsById(int $saleItemId): SaleItem
    {
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        return SaleItem::query()
            ->select('sale_items.id', 'sale_id', 'product_id', 'quantity', 'returned_quantity')
            ->isNotExchange()
            ->with([
                'sale:' . $saleQueries->getBasicColumnsForSaleDetails(),
                'sale.counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'sale.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'sale.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
            ])
            ->findOrFail($saleItemId);
    }

    public function getBasicColumnNamesForSaleSaveEvent(): string
    {
        return 'id,sale_id,original_price_per_unit,price_paid_per_unit,product_id';
    }

    public function getSaleItemsForTheStoreManagerApplicationDashboard(
        int $locationId,
        array $date,
        int $companyId
    ): SaleItem {
        return SaleItem::query()->select(
            DB::raw('SUM(sale_items.total_price_paid) as total_sales_amount'),
            DB::raw('SUM(sale_items.quantity) as unit_sold'),
            DB::raw('COUNT(DISTINCT sales.id) as total_sales'),
        )
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->where('locations.company_id', $companyId)
            ->where('counters.location_id', $locationId)
            ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($date[0]))
            ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($date[1]))
            ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
            ->firstOrFail();
    }

    private function getMorphLocationBasicColumns(): string
    {
        return 'id,employee_id';
    }

    private function getCommonGeneralSalesReportByDateAndBrandAndBySummaryQuery(
        array $filterData,
        bool $excludeProductsWithNoPrice
    ): Builder {
        $saleQueries = resolve(SaleQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $productVariantFilterService = resolve(ProductVariantFilterService::class);

        return SaleItem::query()
            ->select(
                'id',
                'sale_id',
                DB::raw('DATE(created_at) as sale_date'),
                'quantity as total_quantity',
                'total_price_paid as total_price_paid',
                'product_id'
            )
            ->whereHas('product', function ($query) use ($productQueries, $excludeProductsWithNoPrice): void {
                $columns = explode(',', $productQueries->getBasicColumnNames());
                $query->select(...$columns)
                    ->when($excludeProductsWithNoPrice, function ($query): void {
                        $query->where('retail_price', '>', 0);
                    });
            })
            ->whereHas('sale', function ($query) use ($saleQueries, $filterData): void {
                $query
                    ->select('id')
                    ->onlyRegularCompleteCreditAndCompleteLayawaySale()
                    ->where($saleQueries->filterByStoreIds($filterData['location_ids']))
                    ->when(
                        isset($filterData['e_invoice_submitted']) && null != $filterData['e_invoice_submitted'],
                        function ($query) use ($filterData): void {
                            $query->whereNot('digital_invoice_submitted', $filterData['e_invoice_submitted']);
                        }
                    )
                    ->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date_range']));
            })
            ->when(null !== $filterData['department_ids'], function ($query) use (
                $filterData,
                $productVariantFilterService
            ): void {
                $query->whereIn(
                    'product_id',
                    $productVariantFilterService->filterByDepartmentAndBrandIds(
                        'department_id',
                        $filterData['department_ids']
                    )
                );
            })
            ->when(null !== $filterData['brand_ids'], function ($query) use (
                $filterData,
                $productVariantFilterService
            ): void {
                $query->whereIn(
                    'product_id',
                    $productVariantFilterService->filterByDepartmentAndBrandIds('brand_id', $filterData['brand_ids'])
                );
            })
            ->when(null !== $filterData['promoter_ids'], function ($query) use (
                $filterData,
                $promoterQueries
            ): void {
                $query->whereHas('promoters', function ($query) use ($filterData, $promoterQueries): void {
                    $query->where($promoterQueries->filterByPromoterIds($filterData['promoter_ids']));
                });
            })
            ->when(null !== $filterData['counter_ids'], function ($query) use ($filterData): void {
                $query->whereHas('sale', function ($query) use ($filterData): void {
                    $query->select('id', 'counter_update_id')
                        ->onlyRegularCompleteCreditAndCompleteLayawaySale()
                        ->whereHas('counterUpdate', function ($query) use ($filterData): void {
                            $query->select('id')
                                ->whereIntegerInRaw('counter_id', $filterData['counter_ids']);
                        });
                });
            })
            ->orderBy('sale_date', 'desc');
    }

    private function getMemberSalesReportListQuery(array $filterData, int $companyId): Builder
    {
        $saleQueries = resolve(SaleQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [
            'sale:' . $saleQueries->getBasicColumns(),
            'sale.member:' . $memberQueries->getColumnNamesForMemberSalesReport(),
            'sale.counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
            'sale.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
            'product:' . $productQueries->getBasicColumns(),
            'sale.mismatches',
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

        return SaleItem::query()
            ->select('sale_items.id', 'sale_id', 'product_id', 'quantity', 'returned_quantity')
            ->isNotExchange()
            ->with($relations)
            ->whereHas('sale', function ($query) use (
                $saleQueries,
                $companyId,
                $filterData,
                $counterUpdateQueries
            ): void {
                $query->when(null !== $filterData['location_id'], function ($query) use (
                    $filterData,
                    $counterUpdateQueries
                ): void {
                    $query->whereHas(
                        'counterUpdate',
                        $counterUpdateQueries->filterByStoreId((int) $filterData['location_id'])
                    );
                })
                    ->select('id')
                    ->where($saleQueries->filterByCompanyIdForMemberSalesReport($companyId))
                    ->when($filterData['member_id'], function ($query) use ($filterData): void {
                        $query->where('member_id', (int) $filterData['member_id']);
                    })
                    ->when(
                        $filterData['date_range'],
                        function ($query) use ($filterData): void {
                            $query->where('happened_at', '>=', $filterData['date_range'][0])
                                ->where('happened_at', '<=', $filterData['date_range'][1]);
                        }
                    );
            })
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $productQueries,
                $saleQueries,
                $counterUpdateQueries
            ): void {
                $query->where(function ($query) use (
                    $filterData,
                    $productQueries,
                    $saleQueries,
                    $counterUpdateQueries
                ): void {
                    $query
                        ->whereAny(
                            ['total_price_paid', 'total_discount_amount'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhereHas('product', $productQueries->searchByCompoundName($filterData['search_text']))
                        ->orWhereHas('sale', function ($query) use (
                            $saleQueries,
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->when(null !== $filterData['location_id'], function ($query) use (
                                $filterData,
                                $counterUpdateQueries
                            ): void {
                                $query->whereHas(
                                    'counterUpdate',
                                    $counterUpdateQueries->filterByStoreId((int) $filterData['location_id'])
                                );
                            })->select('id')
                                ->where($saleQueries->searchByMemberNameAndMobileNumber($filterData['search_text']))
                                ->when($filterData['member_id'], function ($query) use ($filterData): void {
                                    $query->where('member_id', (int) $filterData['member_id']);
                                })
                                ->when($filterData['date_range'], function ($query) use ($filterData): void {
                                    $query->where('happened_at', '>=', $filterData['date_range'][0])
                                        ->where('happened_at', '<=', $filterData['date_range'][1]);
                                });
                        });
                });
            })
            ->when($filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('product_id', (int) $filterData['product_id']);
            })
            ->when($filterData['product_collection_id'], function ($query) use ($filterData): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    $query->select('product_id')
                        ->from('product_collection_products')
                        ->where('product_collection_id', (int) $filterData['product_collection_id']);
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('product' === $filterData['sort_by']) {
                    $query->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->orderBy('products.name', $filterData['sort_direction']);
                }

                if ('color' === $filterData['sort_by']) {
                    $query->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->join('colors', 'colors.id', '=', 'products.color_id')
                        ->orderBy('colors.name', $filterData['sort_direction']);
                }

                if ('size' === $filterData['sort_by']) {
                    $query->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->join('sizes', 'sizes.id', '=', 'products.size_id')
                        ->orderBy('sizes.name', $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('sale_items.id', 'desc');
            });
    }

    private function getMemberSalesReportListForStoreManager(array $filterData, int $locationId): Builder
    {
        $saleQueries = resolve(SaleQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);

        return SaleItem::query()
            ->select('sale_items.id', 'sale_id', 'product_id', 'quantity', 'returned_quantity')
            ->isNotExchange()
            ->with([
                'sale:' . $saleQueries->getBasicColumns(),
                'sale.member:' . $memberQueries->getColumnNamesForMemberSalesReport(),
                'sale.counterUpdate:' . $counterUpdateQueries->getBasicColumnNames(),
                'sale.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'product:' . $productQueries->getBasicColumns(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
                'sale.mismatches',
            ])
            ->whereHas('sale', function ($query) use (
                $saleQueries,
                $locationId,
                $filterData,
                $counterUpdateQueries
            ): void {
                $query->when(null !== $filterData['location_id'], function ($query) use (
                    $filterData,
                    $counterUpdateQueries
                ): void {
                    $query->whereHas(
                        'counterUpdate',
                        $counterUpdateQueries->filterByStoreId((int) $filterData['location_id'])
                    );
                })
                    ->select('id')
                    ->where($saleQueries->filterByStoreIdForMemberSalesReport($locationId))
                    ->when($filterData['member_id'], function ($query) use ($filterData): void {
                        $query->where('member_id', (int) $filterData['member_id']);
                    })
                    ->when($filterData['date_range'], function ($query) use ($filterData): void {
                        $query->where('happened_at', '>=', $filterData['date_range'][0])
                            ->where('happened_at', '<=', $filterData['date_range'][1]);
                    });
            })
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $productQueries,
                $saleQueries,
                $counterUpdateQueries
            ): void {
                $query->where(function ($query) use (
                    $filterData,
                    $productQueries,
                    $saleQueries,
                    $counterUpdateQueries
                ): void {
                    $query
                        ->whereAny(
                            ['total_price_paid', 'total_discount_amount'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhereHas('product', $productQueries->searchByCompoundName($filterData['search_text']))
                        ->orWhereHas('sale', function ($query) use (
                            $saleQueries,
                            $filterData,
                            $counterUpdateQueries
                        ): void {
                            $query->when(null !== $filterData['location_id'], function ($query) use (
                                $filterData,
                                $counterUpdateQueries
                            ): void {
                                $query->whereHas(
                                    'counterUpdate',
                                    $counterUpdateQueries->filterByStoreId((int) $filterData['location_id'])
                                );
                            })
                                ->select('id')
                                ->where($saleQueries->searchByMemberNameAndMobileNumber($filterData['search_text']))
                                ->when($filterData['member_id'], function ($query) use ($filterData): void {
                                    $query->where('member_id', (int) $filterData['member_id']);
                                })
                                ->when($filterData['date_range'], function ($query) use ($filterData): void {
                                    $query->where('happened_at', '>=', $filterData['date_range'][0])
                                        ->where('happened_at', '<=', $filterData['date_range'][1]);
                                });
                        });
                });
            })
            ->when($filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('product_id', (int) $filterData['product_id']);
            })
            ->when($filterData['product_collection_id'], function ($query) use ($filterData): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    $query->select('product_id')
                        ->from('product_collection_products')
                        ->where('product_collection_id', (int) $filterData['product_collection_id']);
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('product' === $filterData['sort_by']) {
                    $query->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->orderBy('products.name', $filterData['sort_direction']);
                }

                if ('color' === $filterData['sort_by']) {
                    $query->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->join('colors', 'colors.id', '=', 'products.color_id')
                        ->orderBy('colors.name', $filterData['sort_direction']);
                }

                if ('size' === $filterData['sort_by']) {
                    $query->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->join('sizes', 'sizes.id', '=', 'products.size_id')
                        ->orderBy('sizes.name', $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('sale_items.id', 'desc');
            });
    }

    private function getEmployeeSalesReportListQuery(array $filterData, int $companyId): Builder
    {
        $saleQueries = resolve(SaleQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [
            'sale:' . $saleQueries->getBasicColumns(),
            'sale.member:' . $memberQueries->getColumnNamesForMemberSalesReport(),
            'sale.member.employee:' . $employeeQueries->getColumnNamesForEmployeeSalesReport(),
            'product:' . $productQueries->getBasicColumns(),
            'sale.mismatches',
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

        return SaleItem::query()
            ->select('sale_items.id', 'sale_id', 'product_id', 'quantity', 'returned_quantity')
            ->isNotExchange()
            ->with($relations)
            ->whereHas('sale', function ($query) use ($saleQueries, $companyId, $filterData): void {
                $query->select('id')
                    ->where($saleQueries->filterByCompanyIdForEmployeeSalesReport($companyId))
                    ->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                        $query->whereHas('member', function ($query) use ($filterData): void {
                            $query->where('employee_id', (int) $filterData['employee_id']);
                        });
                    })
                    ->when(
                        $filterData['date_range'],
                        function ($query) use ($filterData): void {
                            $query->where('happened_at', '>=', $filterData['date_range'][0])
                                ->where('happened_at', '<=', $filterData['date_range'][1]);
                        }
                    );
            })
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $productQueries,
                $saleQueries
            ): void {
                $query->where(function ($query) use ($filterData, $productQueries, $saleQueries): void {
                    $query
                        ->whereAny(
                            ['total_price_paid', 'total_discount_amount'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhereHas('product', $productQueries->searchByCompoundName($filterData['search_text']))
                        ->orWhereHas('sale', function ($query) use ($saleQueries, $filterData): void {
                            $query->onlyRegularCompleteCreditAndCompleteLayawaySale()
                                ->where($saleQueries->searchByMemberNameAndMobileNumber($filterData['search_text']))
                                ->when(null !== $filterData['employee_id'], function ($query) use (
                                    $filterData
                                ): void {
                                    $query->whereHas('member', function ($query) use ($filterData): void {
                                        $query->where('employee_id', (int) $filterData['employee_id']);
                                    });
                                })
                                ->when($filterData['date_range'], function ($query) use ($filterData): void {
                                    $query->where('happened_at', '>=', $filterData['date_range'][0])
                                        ->where('happened_at', '<=', $filterData['date_range'][1]);
                                });
                        });
                });
            })
            ->when($filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('product_id', (int) $filterData['product_id']);
            })
            ->when($filterData['product_collection_id'], function ($query) use ($filterData): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    $query->select('product_id')
                        ->from('product_collection_products')
                        ->where('product_collection_id', (int) $filterData['product_collection_id']);
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('product' === $filterData['sort_by']) {
                    $query->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->orderBy('products.name', $filterData['sort_direction']);
                }

                if (! config('app.product_variant') && 'color' === $filterData['sort_by']) {
                    $query->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->join('colors', 'colors.id', '=', 'products.color_id')
                        ->orderBy('colors.name', $filterData['sort_direction']);
                }

                if (! config('app.product_variant') && 'size' === $filterData['sort_by']) {
                    $query->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->join('sizes', 'sizes.id', '=', 'products.size_id')
                        ->orderBy('sizes.name', $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('sale_items.id', 'desc');
            });
    }

    private function getEmployeeSalesReportListForStoreManager(
        array $filterData,
        int $locationId,
        int $companyId
    ): Builder {
        $saleQueries = resolve(SaleQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return SaleItem::query()
            ->select('sale_items.id', 'sale_id', 'product_id', 'quantity', 'returned_quantity')
            ->isNotExchange()
            ->with([
                'sale:' . $saleQueries->getBasicColumns(),
                'sale.member:' . $memberQueries->getColumnNamesForMemberSalesReport(),
                'sale.member.employee:' . $employeeQueries->getColumnNamesForEmployeeSalesReport(),
                'product:' . $productQueries->getBasicColumns(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
                'sale.mismatches',
            ])
            ->whereHas('sale', function ($query) use ($saleQueries, $locationId, $companyId, $filterData): void {
                $query->onlyRegularCompleteCreditAndCompleteLayawaySale()
                    ->where($saleQueries->filterByStoreIdAndForEmployeeSalesReport($locationId, $companyId))
                    ->when(null !== $filterData['employee_id'], function ($query) use ($filterData): void {
                        $query->whereHas('member', function ($query) use ($filterData): void {
                            $query->where('employee_id', (int) $filterData['employee_id']);
                        });
                    })
                    ->when($filterData['date_range'], function ($query) use ($filterData): void {
                        $query->where('happened_at', '>=', $filterData['date_range'][0])
                            ->where('happened_at', '<=', $filterData['date_range'][1]);
                    });
            })
            ->when($filterData['search_text'], function ($query) use (
                $filterData,
                $productQueries,
                $saleQueries
            ): void {
                $query->where(function ($query) use ($filterData, $productQueries, $saleQueries): void {
                    $query
                        ->whereAny(
                            ['total_price_paid', 'total_discount_amount'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        )
                        ->orWhereHas('product', $productQueries->searchByCompoundName($filterData['search_text']))
                        ->orWhereHas('sale', function ($query) use ($saleQueries, $filterData): void {
                            $query->onlyRegularCompleteCreditAndCompleteLayawaySale()
                                ->where($saleQueries->searchByMemberNameAndMobileNumber($filterData['search_text']))
                                ->when(null !== $filterData['employee_id'], function ($query) use (
                                    $filterData
                                ): void {
                                    $query->whereHas('member', function ($query) use ($filterData): void {
                                        $query->where('employee_id', (int) $filterData['employee_id']);
                                    });
                                })
                                ->when($filterData['date_range'], function ($query) use ($filterData): void {
                                    $query->where('happened_at', '>=', $filterData['date_range'][0])
                                        ->where('happened_at', '<=', $filterData['date_range'][1]);
                                });
                        });
                });
            })
            ->when($filterData['product_id'], function ($query) use ($filterData): void {
                $query->where('product_id', (int) $filterData['product_id']);
            })
            ->when($filterData['product_collection_id'], function ($query) use ($filterData): void {
                $query->whereIn('product_id', function ($query) use ($filterData): void {
                    $query->select('product_id')
                        ->from('product_collection_products')
                        ->where('product_collection_id', (int) $filterData['product_collection_id']);
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                if ('product' === $filterData['sort_by']) {
                    $query->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->orderBy('products.name', $filterData['sort_direction']);
                }

                if ('color' === $filterData['sort_by']) {
                    $query->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->join('colors', 'colors.id', '=', 'products.color_id')
                        ->orderBy('colors.name', $filterData['sort_direction']);
                }

                if ('size' === $filterData['sort_by']) {
                    $query->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->join('sizes', 'sizes.id', '=', 'products.size_id')
                        ->orderBy('sizes.name', $filterData['sort_direction']);
                }
            }, function ($query): void {
                $query->orderBy('sale_items.id', 'desc');
            });
    }

    public function getSaleItemsForTheProductAgeingReport(int $productId, int $locationId): Collection
    {
        $sale = DB::table('sale_items')
            ->select(
                'sale_items.id',
                'sale_items.product_id',
                'sale_items.quantity',
                DB::raw('MAX(sales.happened_at) as last_selling_date'),
                DB::raw("'sale' as type"),
                DB::raw(
                    'SUM(CASE WHEN sales.happened_at BETWEEN COALESCE(products.original_created_at, products.created_at) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 1 MONTH) THEN sale_items.quantity ELSE 0 END) AS first_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 1 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 2 MONTH) THEN sale_items.quantity ELSE 0 END) AS second_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 2 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 3 MONTH) THEN sale_items.quantity ELSE 0 END) AS third_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 3 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 4 MONTH) THEN sale_items.quantity ELSE 0 END) AS fourth_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 4 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 5 MONTH) THEN sale_items.quantity ELSE 0 END) AS fifth_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 5 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 6 MONTH) THEN sale_items.quantity ELSE 0 END) AS sixth_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 6 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 7 MONTH) THEN sale_items.quantity ELSE 0 END) AS seventh_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 7 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 8 MONTH) THEN sale_items.quantity ELSE 0 END) AS eighth_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 8 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 9 MONTH) THEN sale_items.quantity ELSE 0 END) AS ninth_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 9 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 10 MONTH) THEN sale_items.quantity ELSE 0 END) AS tenth_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sales.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 10 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 11 MONTH) THEN sale_items.quantity ELSE 0 END) AS eleventh_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sales.happened_at >= DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 11 MONTH) THEN sale_items.quantity ELSE 0 END) AS twelfth_month_quantity_sold'
                ),
            )
            ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
            ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
            ->where('sale_items.product_id', $productId)
            ->where('counters.location_id', $locationId)
            ->groupBy('sale_items.id')
            ->groupBy('sale_items.product_id')
            ->groupBy('sales.happened_at');

        $saleReturn = DB::table('sale_return_items')
            ->select(
                'sale_return_items.id',
                'sale_return_items.product_id',
                DB::raw('-sale_return_items.quantity as quantity'),
                DB::raw('NULL as last_selling_date'),
                DB::raw("'return' as type"),
                DB::raw(
                    'SUM(CASE WHEN sale_returns.happened_at BETWEEN COALESCE(products.original_created_at, products.created_at) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 1 MONTH) THEN -sale_return_items.quantity ELSE 0 END) AS first_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 1 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 2 MONTH) THEN -sale_return_items.quantity ELSE 0 END) AS second_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 2 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 3 MONTH) THEN -sale_return_items.quantity ELSE 0 END) AS third_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 3 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 4 MONTH) THEN -sale_return_items.quantity ELSE 0 END) AS fourth_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 4 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 5 MONTH) THEN -sale_return_items.quantity ELSE 0 END) AS fifth_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 5 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 6 MONTH) THEN -sale_return_items.quantity ELSE 0 END) AS sixth_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 6 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 7 MONTH) THEN -sale_return_items.quantity ELSE 0 END) AS seventh_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 7 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 8 MONTH) THEN -sale_return_items.quantity ELSE 0 END) AS eighth_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 8 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 9 MONTH) THEN -sale_return_items.quantity ELSE 0 END) AS ninth_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 9 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 10 MONTH) THEN -sale_return_items.quantity ELSE 0 END) AS tenth_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sale_returns.happened_at BETWEEN DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 10 MONTH) AND DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 11 MONTH) THEN -sale_return_items.quantity ELSE 0 END) AS eleventh_month_quantity_sold'
                ),
                DB::raw(
                    'SUM(CASE WHEN sale_returns.happened_at >= DATE_ADD(COALESCE(products.original_created_at, products.created_at), INTERVAL 11 MONTH) THEN -sale_return_items.quantity ELSE 0 END) AS twelfth_month_quantity_sold'
                ),
            )
            ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
            ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
            ->leftJoin('products', 'products.id', '=', 'sale_return_items.product_id')
            ->where('sale_return_items.product_id', $productId)
            ->where('counters.location_id', $locationId)
            ->groupBy('sale_return_items.id')
            ->groupBy('sale_return_items.product_id');

        $saleAndSaleReturnRecords = $sale->union($saleReturn);

        return $saleAndSaleReturnRecords->get();
    }

    public function getYesterdaySaleWithSaleItems(string $date): Collection
    {
        return SaleItem::query()
            ->select('sale_items.id', 'product_id', 'counters.location_id')
            ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sales.counter_update_id')
            ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
            ->where(function ($query) use ($date): void {
                $query->where('happened_at', '>=', CommonFunctions::addStartTime($date))
                    ->where('happened_at', '<=', CommonFunctions::addEndTime($date));
            })
            ->get();
    }

    public function getTopTwentyAggregateData(array $date): Collection
    {
        $saleQueries = resolve(SaleQueries::class);

        return SaleItem::query()
            ->select(
                'id',
                'quantity',
                'product_id',
                'sale_id',
                'total_discount_amount',
                'total_price_paid',
                'total_tax_amount',
            )
            ->isNotExchange()
            ->withWhereHas('sale', function ($query) use ($saleQueries, $date): void {
                $query->select(explode(',', $saleQueries->getBasicColumnNames()))
                    ->onlyRegularCompleteCreditAndCompleteLayawaySale()
                    ->where($saleQueries->filterByHappenedAtWithinDateRange($date));
            })
            ->orderBy('quantity', 'desc')
            ->get();
    }

    public function getPreferredItems(int $memberId, int $companyId, ?int $locationId = null): Collection
    {
        $productQueries = resolve(ProductQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        return SaleItem::query()
            ->select('id', 'quantity', 'product_id', 'sale_id')
            ->with([
                'product:' . $productQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
                'product.categories:' . $categoryQueries->getBasicColumnNames(),
                'sale:' . $saleQueries->getBasicColumnNames(),
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ])
            ->isNotExchange()
            ->whereHas('product', function ($query) use ($companyId): void {
                $query->select('id')
                    ->where('company_id', $companyId);
            })
            ->whereHas('sale', function ($query) use ($saleQueries, $memberId, $locationId): void {
                $query->select('id')
                    ->onlyRegularCompleteCreditAndCompleteLayawaySale()
                    ->where('member_id', $memberId)
                    ->when(null !== $locationId, function ($query) use ($saleQueries, $locationId): void {
                        $query->where($saleQueries->filterByStoreId((int) $locationId));
                    });
            })
            ->orderBy('quantity', 'desc')
            ->get();
    }

    public function getSalesForDashboardByDate(int $companyId, string $startDate, string $endDate): Collection
    {
        return SaleItem::query()->select(
            'locations.company_id',
            DB::raw('DATE(counter_updates.opened_by_pos_at) as opened_date'),
            DB::raw('SUM(sale_items.total_price_paid) as total_amount'),
            DB::raw('SUM(sale_items.quantity) as total_units_sold'),
            DB::raw('COUNT(DISTINCT sales.id) as total_sales_count'),
            'companies.uuid as company_uuid'
        )
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->join('companies', 'locations.company_id', '=', 'companies.id')
            ->where('locations.company_id', $companyId)
            ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($startDate))
            ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($endDate))
            ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
            ->groupBy('locations.company_id', 'opened_date')
            ->get();
    }

    public function getRegularProductSalesSummary(
        int $companyId,
        array $dates,
        array $salesStatus = [
            SaleStatus::REGULAR_SALE->value,
            SaleStatus::COMPLETE_LAYAWAY_SALE->value,
            SaleStatus::COMPLETE_CREDIT_SALE->value,
        ],
    ): QueryBuilder {
        $cacheKey = 'regular_product_sales_summary_' . $companyId;

        $cacheDuration = now()->addMinutes(10);

        return Cache::remember($cacheKey, $cacheDuration, fn () => DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
        ->join('locations', 'counters.location_id', '=', 'locations.id')
        ->where('locations.company_id', $companyId)
        ->where('products.type_id', ProductTypes::REGULAR_PRODUCT->value)
        ->whereIn('sales.status', $salesStatus)
        ->when([] !== $dates && isset($dates['start_date']) && isset($dates['end_date']), function ($query) use (
            $dates
        ): void {
            $query->where(DB::raw('DATE(counter_updates.closed_by_pos_at)'), '>=', $dates['start_date'])
                ->where(DB::raw('DATE(counter_updates.closed_by_pos_at)'), '<=', $dates['end_date']);
        })
        ->groupBy([DB::raw('DATE(sales.happened_at)'), 'locations.id', 'products.id'])
        ->select([
            DB::raw('DATE(sales.happened_at) as date'),
            'locations.id as location_id',
            'products.id as product_id',
            DB::raw('SUM(sale_items.quantity) as quantity'),
            DB::raw('SUM(sale_items.total_price_paid) as amount'),
        ]));
    }

    public function getRegularProductAggregateSales(int $companyId): PaginationLengthAwarePaginator
    {
        $saleReturnItemQueries = new SaleReturnItemQueries();

        $saleReturnItemsQueries = $saleReturnItemQueries->getRegularProductSalesReturnsSummary($companyId);
        $saleItemsQueries = $this->getRegularProductSalesSummary($companyId, []);

        $union = $saleItemsQueries->union($saleReturnItemsQueries);

        return DB::query()
            ->fromSub($union, 'combined')
            ->groupBy(['date', 'location_id', 'product_id'])
            ->select([
                'date',
                'location_id',
                'product_id',
                DB::raw('SUM(quantity) as quantity'),
                DB::raw('SUM(amount) as amount'),
            ])
            ->orderBy('date')
            ->paginate(5000);
    }

    public function getRegularProductSalesAggregateForClosedCounter(
        int $companyId,
        ?array $dates = []
    ): PaginationLengthAwarePaginator {
        $dates = $dates ?: [];
        $saleReturnItemQueries = new SaleReturnItemQueries();

        $saleReturnItemsQueries = $saleReturnItemQueries->getRegularProductSalesReturnsSummary($companyId, $dates);
        $saleItemsQueries = $this->getRegularProductSalesSummary($companyId, $dates, [SaleStatus::REGULAR_SALE->value]);

        $union = $saleItemsQueries->union($saleReturnItemsQueries);

        return DB::query()
            ->fromSub($union, 'combined')
            ->groupBy(['date', 'location_id', 'product_id'])
            ->select([
                'date',
                'location_id',
                'product_id',
                DB::raw('SUM(quantity) as quantity'),
                DB::raw('SUM(amount) as amount'),
            ])
            ->orderBy('date')
            ->paginate(5000);
    }

    public function getRegularProductCompleteCreditAndLayawaySalesAggregateForClosedCounter(
        int $companyId,
        ?array $dates
    ): Collection {
        $dates = $dates ?: [];

        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->where('locations.company_id', $companyId)
            ->where('products.type_id', ProductTypes::REGULAR_PRODUCT->value)
            ->whereIn(
                'sales.status',
                [SaleStatus::COMPLETE_LAYAWAY_SALE->value, SaleStatus::COMPLETE_CREDIT_SALE->value]
            )
            ->when([] !== $dates && isset($dates['start_date']) && isset($dates['end_date']), function ($query) use (
                $dates
            ): void {
                $query->where(DB::raw('DATE(counter_updates.closed_by_pos_at)'), '>=', $dates['start_date'])
                    ->where(DB::raw('DATE(counter_updates.closed_by_pos_at)'), '<=', $dates['end_date']);
            })
            ->where(function ($query) use ($dates): void {
                $query->where(function ($query) use ($dates): void {
                    $query->where(DB::raw('DATE(sales.layaway_completed_at)'), '>=', $dates['start_date'])
                        ->where(DB::raw('DATE(sales.layaway_completed_at)'), '<=', $dates['end_date']);
                })->orWhere(function ($query) use ($dates): void {
                    $query->where(DB::raw('DATE(sales.credit_completed_at)'), '>=', $dates['start_date'])
                        ->where(DB::raw('DATE(sales.credit_completed_at)'), '<=', $dates['end_date']);
                });
            })
            ->groupBy([DB::raw('DATE(sales.happened_at)'), 'locations.id', 'products.id'])
            ->select([
                DB::raw('DATE(sales.happened_at) as date'),
                'locations.id as location_id',
                'products.id as product_id',
                DB::raw('SUM(sale_items.quantity) as quantity'),
                DB::raw('SUM(sale_items.total_price_paid) as amount'),
            ])
            ->get();
    }
}
