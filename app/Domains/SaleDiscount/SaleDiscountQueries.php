<?php

declare(strict_types=1);

namespace App\Domains\SaleDiscount;

use App\CommonFunctions;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\Enums\SaleDiscountTypeReports;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\SalePriceOverride\SalePriceOverrideQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Models\SaleDiscount;
use App\Models\SalePriceOverride;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class SaleDiscountQueries
{
    public function addNew(
        int $saleId,
        int $discountableId,
        string $discountableType,
        float $amount,
        ?string $promoCode = null
    ): void {
        SaleDiscount::create([
            'sale_id' => $saleId,
            'discountable_id' => $discountableId,
            'discountable_type' => $discountableType,
            'amount' => $amount,
            'promo_code' => $promoCode,
        ]);
    }

    public function getSaleDiscountByCounterUpdateId(int $counterUpdateId): Collection
    {
        $saleQueries = resolve(SaleQueries::class);

        return SaleDiscount::query()
            ->whereHas('sale', $saleQueries->filterByRegularCreditAndLayawaySaleByCounterUpdateId($counterUpdateId))
            ->get();
    }

    public function fetchSaleDiscountByPromotionAndPromoCode(int $promotionId, string $promoCode): ?SaleDiscount
    {
        return SaleDiscount::query()
            ->select('id', 'discountable_id', 'discountable_type', 'promo_code')
            ->where('discountable_id', $promotionId)
            ->where('discountable_type', ModelMapping::PROMOTION->name)
            ->where('promo_code', $promoCode)
            ->first();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,discountable_id,discountable_type,sale_id,amount,promo_code';
    }

    public function getVoucherIdBySale(int $saleId): ?int
    {
        return SaleDiscount::select('id', 'discountable_id')
            ->where('discountable_type', ModelMapping::VOUCHER->name)
            ->where('sale_id', $saleId)
            ->first()
            ?->discountable_id;
    }

    public function getSaleDiscountBasedOnFilterForSaleSeasonal(array $filterData, int $companyId): Collection
    {
        $saleQueries = new SaleQueries();
        $voucherQueries = new VoucherQueries();
        $salePriceOverrideQueries = new SalePriceOverrideQueries();

        return SaleDiscount::query()
            ->select('id', 'discountable_id', 'discountable_type', 'amount')
            ->with([
                'discountable' => function (MorphTo $morphTo) use (
                    $voucherQueries,
                    $salePriceOverrideQueries
                ): void {
                    $morphTo->constrain([
                        Voucher::class => $voucherQueries->getSeasonalSalesVoucherColumns(),
                        SalePriceOverride::class => $salePriceOverrideQueries->getSeasonalSalesBasicColumns(),
                    ]);
                },
            ])
            ->whereHas('sale', $saleQueries->filterByCompanyId($companyId))
            ->when((int) $filterData['location_id'] > 0, function ($query) use ($saleQueries, $filterData): void {
                $query->whereHas('sale', $saleQueries->filterByLocationId((int) $filterData['location_id']));
            })
            ->when((int) $filterData['brand_id'] > 0, function ($query) use ($filterData): void {
                $query->whereHas('sale', function ($query) use ($filterData): void {
                    $query->whereHas('saleItems', function ($query) use ($filterData): void {
                        $query->whereHas('product', function ($query) use ($filterData): void {
                            $query->where('brand_id', $filterData['brand_id']);
                        });
                    });
                });
            })
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['start_date']))
            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['end_date']))
            ->get();
    }

    public function getSaleDiscountBasedOnFilterForSaleSeasonalSum(array $filterData, int $companyId): float
    {
        $saleQueries = new SaleQueries();

        return (float) SaleDiscount::query()
            ->select('id', 'amount')
            ->whereHas('sale', $saleQueries->filterByCompanyId($companyId))
            ->when((int) $filterData['location_id'] > 0, function ($query) use ($saleQueries, $filterData): void {
                $query->whereHas('sale', $saleQueries->filterByLocationId((int) $filterData['location_id']));
            })
            ->when((int) $filterData['brand_id'] > 0, function ($query) use ($filterData): void {
                $query->whereHas('sale', function ($query) use ($filterData): void {
                    $query->whereHas('saleItems', function ($query) use ($filterData): void {
                        $query->whereHas('product', function ($query) use ($filterData): void {
                            $query->where('brand_id', $filterData['brand_id']);
                        });
                    });
                });
            })
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['start_date']))
            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['end_date']))
            ->sum('amount');
    }

    public function getSaleDiscounts(array $filterData): Collection
    {
        $saleQueries = resolve(SaleQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $cashierQueries = resolve(CashierQueries::class);

        return SaleDiscount::query()
            ->select('id', 'sale_id', 'discountable_id', 'discountable_type', 'amount', 'promo_code')
            ->with([
                'sale:' . $saleQueries->getBasicColumnNames(),
                'sale.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'sale.counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'sale.counterUpdate.counter.location:' . $locationQueries->getNameColumnName(),
                'sale.counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'sale.counterUpdate.cashier.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                'discountable' => function (MorphTo $morphTo) use ($employeeQueries): void {
                    $morphTo->constrain([
                        SalePriceOverride::class => function ($query) use ($employeeQueries): void {
                            $query->select('id', 'negotiator_id', 'negotiator_type')
                            ->with([
                                'negotiator',
                                'negotiator.employee:' . $employeeQueries->getNameAndStaffIdColumns(),
                            ]);
                        },
                    ]);
                },
            ])
            ->whereNotNull('amount')
            ->whereHas('sale', function ($query) use ($filterData, $saleQueries): void {
                $query->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->where($saleQueries->filterByStoreIds($filterData['location_ids']))
                    ->where($saleQueries->filterByHappenedAtWithinDateRange($filterData['date_range']));
            })
            ->when(
                (int) $filterData['report_type'] === SaleDiscountTypeReports::VOUCHER->value,
                function ($query): void {
                    $query->where('discountable_type', SaleDiscountTypeReports::VOUCHER->name);
                }
            )
            ->when(
                (int) $filterData['report_type'] === SaleDiscountTypeReports::CASHBACK->value,
                function ($query): void {
                    $query->where('discountable_type', SaleDiscountTypeReports::CASHBACK->name);
                }
            )
            ->when(
                (int) $filterData['report_type'] === SaleDiscountTypeReports::PROMOTION->value,
                function ($query): void {
                    $query->where('discountable_type', SaleDiscountTypeReports::PROMOTION->name);
                }
            )
            ->when(
                (int) $filterData['report_type'] === SaleDiscountTypeReports::SALE_PRICE_OVERRIDE->value,
                function ($query): void {
                    $query->where('discountable_type', SaleDiscountTypeReports::SALE_PRICE_OVERRIDE->name);
                }
            )
            ->when(
                (int) $filterData['report_type'] === SaleDiscountTypeReports::SALE_LOYALTY_POINT->value,
                function ($query): void {
                    $query->where('discountable_type', SaleDiscountTypeReports::SALE_LOYALTY_POINT->name);
                }
            )
            ->get();
    }
}
