<?php

declare(strict_types=1);

namespace App\Domains\HoldSale;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\HoldBookingPaymentItem\HoldBookingPaymentItemQueries;
use App\Domains\HoldSaleDetail\HoldSaleDetailQueries;
use App\Domains\HoldSaleItem\HoldSaleItemQueries;
use App\Domains\HoldSaleReturnItem\HoldSaleReturnItemQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\MemberQueries;
use App\Models\HoldSale;
use Illuminate\Support\Collection;

class HoldSaleQueries
{
    public function addNew(string $offlineId, int $counterUpdateId, int $typeId): HoldSale
    {
        return HoldSale::create([
            'offline_id' => $offlineId,
            'counter_update_id' => $counterUpdateId,
            'type_id' => $typeId,
        ]);
    }

    public function loadRelations(HoldSale $holdSale): HoldSale
    {
        $holdSaleDetailQueries = resolve(HoldSaleDetailQueries::class);
        $holdSaleItemQueries = resolve(HoldSaleItemQueries::class);
        $holdSaleReturnItemQueries = resolve(HoldSaleReturnItemQueries::class);
        $holdBookingPaymentItemQueries = resolve(HoldBookingPaymentItemQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $holdSale->refresh();

        return $holdSale->load(
            'holdSaleDetails:' . $holdSaleDetailQueries->getColumnNamesForPos(),
            'holdSaleDetails.member:' . $memberQueries->getBasicColumnNamesForPosSale(),
            'holdSaleDetails.holdSaleItem:' . $holdSaleItemQueries->getColumnNamesForPos(),
            'holdSaleDetails.holdSaleReturnItem:' . $holdSaleReturnItemQueries->getColumnNamesForPos(),
            'holdSaleDetails.holdBookingPaymentItem:' . $holdBookingPaymentItemQueries->getColumnNamesForPos(),
        );
    }

    public function getNotCancelByOfflineId(string $offlineId): ?HoldSale
    {
        return HoldSale::where('offline_id', $offlineId)->whereNull('cancelled_at')->first();
    }

    public function doesOfflineIdExist(string $offlineId): bool
    {
        return HoldSale::query()
            ->select('id')
            ->where('offline_id', $offlineId)
            ->exists();
    }

    public function markAsCancel(HoldSale $holdSale, string $cancelledAt): void
    {
        $holdSale->cancelled_at = $cancelledAt;
        $holdSale->save();
    }

    public function getNotCompleteByOfflineId(string $offlineId): ?HoldSale
    {
        return HoldSale::where('offline_id', $offlineId)->whereNull('complete_at')->first();
    }

    public function markAsComplete(
        HoldSale $holdSale,
        string $completeAt,
        string $completeOfflineId,
        ?int $completeSaleId,
        ?int $completeSaleReturnId,
    ): void {
        $holdSale->complete_at = $completeAt;
        $holdSale->complete_offline_id = $completeOfflineId;
        $holdSale->complete_sale_id = $completeSaleId;
        $holdSale->complete_sale_return_id = $completeSaleReturnId;
        $holdSale->save();
    }

    public function getNotCompleteAndNotCancelByOfflineId(string $offlineId): ?HoldSale
    {
        return HoldSale::where('offline_id', $offlineId)
            ->whereNull('cancelled_at')
            ->whereNull('complete_at')
            ->first();
    }

    public function isCancelledHoldSale(string $offlineId): bool
    {
        return HoldSale::where('offline_id', $offlineId)->whereNotNull('cancelled_at')->exists();
    }

    public function isCompletedHoldSale(string $offlineId): bool
    {
        return HoldSale::where('offline_id', $offlineId)->whereNotNull('complete_at')->exists();
    }

    public function getSuspendAndResumeReport(array $filterData): Collection
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $holdSaleDetailQueries = resolve(HoldSaleDetailQueries::class);
        $cashierQueries = resolve(CashierQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        return HoldSale::query()
            ->select(
                'id',
                'offline_id',
                'counter_update_id',
                'type_id',
                'cancelled_at',
                'complete_sale_id',
                'complete_offline_id',
                'complete_sale_return_id',
                'complete_at',
            )
            ->with(
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getBasicColumnNames(),
                'counterUpdate.cashier:' . $cashierQueries->getEmployeeIdColumnNames(),
                'counterUpdate.cashier.employee:' . $employeeQueries->getBasicColumnNames(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesForAdminSaleReports(),
                'holdSaleDetails:' . $holdSaleDetailQueries->getColumnNamesForPos(),
            )
            ->whereHas('counterUpdate', function ($query) use (
                $filterData,
                $counterUpdateQueries,
                $counterQueries
            ): void {
                $query->whereHas('counter', function ($query) use ($counterQueries, $filterData): void {
                    $query->where($counterQueries->filterByLocations($filterData['location_ids']));
                })
                ->when(null !== $filterData['counter_ids'], function ($query) use (
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
            })
            ->when($filterData['date_range'], function ($query) use ($filterData, $holdSaleDetailQueries): void {
                $query->whereHas('holdSaleDetails', function ($query) use (
                    $holdSaleDetailQueries,
                    $filterData
                ): void {
                    $query->where($holdSaleDetailQueries->filterByHappenedAtWithinDateRange($filterData['date_range']));
                });
            })
            ->get();
    }
}
