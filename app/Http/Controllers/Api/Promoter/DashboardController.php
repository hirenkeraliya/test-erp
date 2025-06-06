<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Promoter;

use App\CommonFunctions;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterCommissionUpdate\PromoterCommissionUpdateQueries;
use App\Http\Controllers\Controller;
use App\Models\Promoter;
use App\Models\PromoterCommissionUpdate;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getDashboardData(Request $request): array
    {
        /** @var Promoter $promoter */
        $promoter = $request->user();

        $request->validate([
            'store_id' => ['required_without_all:location_id', 'integer', 'exists:locations,id'],
            'location_id' => ['required_without_all:store_id', 'integer', 'exists:locations,id'],
        ]);

        $locationId = (int) $request->store_id > 0 ? $request->store_id : $request->location_id;

        [$currentMonthItems, $currentMonthReturnItems, $currentMonthReturnAmount, $currentMonthNetSales] = $this->getCurrentMonthItemSold(
            Carbon::now(),
            (int) $locationId,
            $promoter->getKey()
        );

        [$todayItems, $todayReturnItems, $todayReturnAmount, $todayNetSales] = $this->getTodayItemSold(
            Carbon::now(),
            (int) $locationId,
            $promoter->getKey()
        );

        [$previousMonthItemSold, $previousMonthReturnItemSold, $previousCommissionAmount, $previousMonthReturnAmount, $previousMonthNetSales] = $this->getPreviousMonthItemSoldAndCommissionAmountTotal(
            Carbon::now()->subMonth()->startOfMonth(),
            (int) $locationId,
            $promoter->getKey()
        );

        return [
            'today_item_sold' => CommonFunctions::truncateDecimal((float) $todayItems),
            'today_item_return' => CommonFunctions::truncateDecimal((float) $todayReturnItems),
            'today_return_amount' => CommonFunctions::truncateDecimal((float) $todayReturnAmount),
            'today_unit_sold' => CommonFunctions::numberFormat((float) $todayItems - $todayReturnItems),
            'today_net_sales' => CommonFunctions::currencyFormat((float) $todayNetSales),
            'this_month_item_sold' => CommonFunctions::truncateDecimal((float) $currentMonthItems),
            'this_month_item_return' => CommonFunctions::truncateDecimal((float) $currentMonthReturnItems),
            'this_month_return_amount' => CommonFunctions::truncateDecimal((float) $currentMonthReturnAmount),
            'this_month_unit_sold' => CommonFunctions::numberFormat(
                (float) $currentMonthItems - $currentMonthReturnItems
            ),
            'this_month_net_sales' => CommonFunctions::currencyFormat((float) $currentMonthNetSales),
            'last_month_item_sold' => CommonFunctions::truncateDecimal((float) $previousMonthItemSold),
            'last_month_item_return' => CommonFunctions::truncateDecimal((float) $previousMonthReturnItemSold),
            'last_month_unit_sold' => CommonFunctions::numberFormat(
                (float) $previousMonthItemSold - $previousMonthReturnItemSold
            ),
            'last_month_net_sales' => CommonFunctions::currencyFormat((float) $previousMonthNetSales),
            'last_month_return_amount' => CommonFunctions::currencyFormat((float) $previousMonthReturnAmount),
            'last_month_commission_amount' => CommonFunctions::currencyFormat((float) $previousCommissionAmount, 4),
        ];
    }

    private function getTodayItemSold(Carbon $todayDate, int $locationId, int $promoterId): array
    {
        $todayDateRange = [$todayDate->format('Y-m-d'), $todayDate->format('Y-m-d')];

        $promoterQueries = resolve(PromoterQueries::class);

        $promoterData = $promoterQueries->getItemSoldCountForTheGivenPromoter(
            $todayDateRange,
            $locationId,
            $promoterId
        );

        if (! $promoterData instanceof Promoter) {
            return [0, 0, 0, 0];
        }

        return [
            (float) $promoterData->total_units_sold, // @phpstan-ignore-line
            (float) $promoterData->total_units_returned, // @phpstan-ignore-line
            (float) $promoterData->total_amount_return, // @phpstan-ignore-line
            (float) $promoterData->net_sales, // @phpstan-ignore-line
        ];
    }

    private function getCurrentMonthItemSold(Carbon $thisMonthDate, int $locationId, int $promoterId): array
    {
        $thisMonthDateRange = [
            $thisMonthDate->startOfMonth()->format('Y-m-d'),
            $thisMonthDate->endOfMonth()->format('Y-m-d'),
        ];

        $promoterQueries = resolve(PromoterQueries::class);

        $promoterData = $promoterQueries->getItemSoldCountForTheGivenPromoter(
            $thisMonthDateRange,
            $locationId,
            $promoterId
        );

        if (! $promoterData instanceof Promoter) {
            return [0, 0, 0, 0];
        }

        return [
            (float) $promoterData->total_units_sold, // @phpstan-ignore-line
            (float) $promoterData->total_units_returned, // @phpstan-ignore-line
            (float) $promoterData->total_amount_return, // @phpstan-ignore-line
            (float) $promoterData->net_sales, // @phpstan-ignore-line
        ];
    }

    private function getPreviousMonthItemSoldAndCommissionAmountTotal(
        Carbon $previousMonthDate,
        int $locationId,
        int $promoterId
    ): array {
        $previousMonthDateRange = [
            $previousMonthDate->format('Y-m-d H:i:s'),
            $previousMonthDate->endOfMonth()->format('Y-m-d H:i:s'),
        ];

        $promoterCommissionUpdateQueries = resolve(PromoterCommissionUpdateQueries::class);

        $promoterCommissionUpdate = $promoterCommissionUpdateQueries->getItemsSoldCountAndCommissionAmountTotal(
            $previousMonthDateRange,
            $locationId,
            $promoterId
        );

        if (! $promoterCommissionUpdate instanceof PromoterCommissionUpdate) {
            return [0, 0, 0, 0, 0];
        }

        return [
            /* @phpstan-ignore-next-line */
            $promoterCommissionUpdate->total_units_sold,
            /* @phpstan-ignore-next-line */
            $promoterCommissionUpdate->total_units_returned,
            /* @phpstan-ignore-next-line */
            $promoterCommissionUpdate->total_commission_amount,
            /* @phpstan-ignore-next-line */
            $promoterCommissionUpdate->total_return_amount,
            /* @phpstan-ignore-next-line */
            $promoterCommissionUpdate->net_sales,
        ];
    }
}
