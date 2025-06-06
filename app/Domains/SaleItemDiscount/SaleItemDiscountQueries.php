<?php

declare(strict_types=1);

namespace App\Domains\SaleItemDiscount;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ComplimentaryItemReason\ComplimentaryItemReasonQueries;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\Promotion\PromotionQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleItemPriceOverride\SaleItemPriceOverrideQueries;
use App\Models\ComplimentaryItemReason;
use App\Models\DreamPrice;
use App\Models\Promotion;
use App\Models\SaleItemDiscount;
use App\Models\SaleItemPriceOverride;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class SaleItemDiscountQueries
{
    public function addNew(
        int $saleItemId,
        int $discountableId,
        string $discountableType,
        float $amount,
        ?string $promoCode = null
    ): void {
        SaleItemDiscount::create([
            'sale_item_id' => $saleItemId,
            'discountable_id' => $discountableId,
            'discountable_type' => $discountableType,
            'amount' => $amount,
            'promo_code' => $promoCode,
        ]);
    }

    public function getSaleItemDiscountByCounterUpdateId(int $counterUpdateId): Collection
    {
        $saleItemQueries = resolve(SaleItemQueries::class);

        return SaleItemDiscount::query()
            ->whereHas(
                'saleItem',
                $saleItemQueries->filterByRegularCreditAndLayawaySaleByCounterUpdateId($counterUpdateId)
            )
            ->get();
    }

    public function fetchSaleItemDiscountByPromotionAndPromoCode(int $promotionId, string $promoCode): ?SaleItemDiscount
    {
        return SaleItemDiscount::query()
            ->select('id', 'discountable_type', 'discountable_id', 'promo_code')
            ->where('discountable_type', ModelMapping::PROMOTION->name)
            ->where('discountable_id', $promotionId)
            ->where('promo_code', $promoCode)
            ->first();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_item_id,discountable_type,discountable_id,amount,promo_code';
    }

    public function getSaleItemDiscountBasedOnFilterForSaleSeasonal(array $filterData, int $companyId): Collection
    {
        $saleQueries = new SaleQueries();
        $saleItemPriceOverrideQueries = new SaleItemPriceOverrideQueries();
        $promotionQueries = new PromotionQueries();
        $dreamPriceQueries = new DreamPriceQueries();
        $complimentaryItemReasonQueries = new ComplimentaryItemReasonQueries();

        return SaleItemDiscount::query()
            ->select('id', 'discountable_id', 'discountable_type', 'amount')
            ->with([
                'discountable' => function (MorphTo $morphTo) use (
                    $saleItemPriceOverrideQueries,
                    $promotionQueries,
                    $dreamPriceQueries,
                    $complimentaryItemReasonQueries
                ): void {
                    $morphTo->constrain([
                        SaleItemPriceOverride::class => $saleItemPriceOverrideQueries->getSeasonalSalesBasicColumns(),
                        Promotion::class => $promotionQueries->getSeasonalSalesBasicColumns(),
                        DreamPrice::class => $dreamPriceQueries->getSeasonalSalesBasicColumns(),
                        ComplimentaryItemReason::class => $complimentaryItemReasonQueries->getSeasonalSalesBasicColumns(),
                    ]);
                },
            ])
            ->whereHas('saleItem', function ($query) use ($companyId, $saleQueries): void {
                $query->whereHas('sale', $saleQueries->filterByCompanyId($companyId));
            })
            ->whereNotIn('discountable_type', [ModelMapping::SALE_ITEM_EXCHANGE->name])
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['start_date']))
            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['end_date']))
            ->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData, $saleQueries): void {
                $query->whereHas('saleItem', function ($query) use ($filterData, $saleQueries): void {
                    $query->whereHas('sale', $saleQueries->filterByLocationId((int) $filterData['location_id']));
                });
            })
            ->when((int) $filterData['brand_id'] > 0, function ($query) use ($filterData): void {
                $query->whereHas('saleItem', function ($query) use ($filterData): void {
                    $query->whereHas('product', function ($query) use ($filterData): void {
                        $query->where('brand_id', $filterData['brand_id']);
                    });
                });
            })
            ->get();
    }

    public function getSaleItemDiscountBasedOnFilterForSaleSeasonalSum(array $filterData, int $companyId): float
    {
        $saleQueries = new SaleQueries();

        return (float) SaleItemDiscount::query()
            ->select('id', 'amount')
            ->whereHas('saleItem', function ($query) use ($companyId, $saleQueries): void {
                $query->whereHas('sale', $saleQueries->filterByCompanyId($companyId));
            })
            ->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData, $saleQueries): void {
                $query->whereHas('saleItem', function ($query) use ($filterData, $saleQueries): void {
                    $query->whereHas('sale', $saleQueries->filterByLocationId((int) $filterData['location_id']));
                });
            })
            ->when((int) $filterData['brand_id'] > 0, function ($query) use ($filterData): void {
                $query->whereHas('saleItem', function ($query) use ($filterData): void {
                    $query->whereHas('product', function ($query) use ($filterData): void {
                        $query->where('brand_id', $filterData['brand_id']);
                    });
                });
            })
            ->whereNotIn('discountable_type', [ModelMapping::SALE_ITEM_EXCHANGE->name])
            ->where('created_at', '>=', CommonFunctions::addStartTime($filterData['start_date']))
            ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['end_date']))
            ->sum('amount');
    }
}
