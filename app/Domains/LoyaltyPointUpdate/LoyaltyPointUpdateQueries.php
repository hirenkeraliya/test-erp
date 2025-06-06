<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyPointUpdate;

use App\Domains\Admin\AdminQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\Member\MemberQueries;
use App\Domains\Order\OrderQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Domains\VoidSale\VoidSaleQueries;
use App\Models\Admin;
use App\Models\CancelCreditSale;
use App\Models\CancelLayawaySale;
use App\Models\LoyaltyPointUpdate;
use App\Models\Order;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\VoidSale;
use App\Models\Voucher;
use Closure;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LoyaltyPointUpdateQueries
{
    public function addNew(array $data): void
    {
        LoyaltyPointUpdate::create($data);
    }

    public function getBasicColumns(): string
    {
        return 'id,member_id,affected_by_id,affected_by_type,type_id,points,closing_loyalty_points_balance,happened_at';
    }

    public function getBasicColumnsForManualUpdate(): string
    {
        return 'id,member_id,affected_by_id,affected_by_type,type_id,points,closing_loyalty_points_balance,happened_at,remarks';
    }

    public function getPaginatedTransactionListForMemberApi(array $filteredData, int $memberId): LengthAwarePaginator
    {
        return LoyaltyPointUpdate::query()
            ->select([
                'id',
                'member_id',
                'points',
                'closing_loyalty_points_balance',
                'type_id',
                'happened_at',
                'affected_by_type',
                'affected_by_id',
            ])
            ->with([
                'affectedBy' => function (MorphTo $morphTo): void {
                    $morphTo->constrain($this->getAffectedByMorphMap());
                },
            ])
            ->where('member_id', $memberId)
            ->when($filteredData['sort_by'], function ($query) use ($filteredData): void {
                $query->orderBy($filteredData['sort_by'], $filteredData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filteredData['per_page']);
    }

    public function getTotalPointRewarded(int $memberId): int
    {
        return (int) LoyaltyPointUpdate::query()
            ->where('member_id', $memberId)
            ->where('points', '>', 0)
            ->sum('points');
    }

    public function getTotalPointsRedeemed(int $memberId): int
    {
        return (int) LoyaltyPointUpdate::query()
            ->where('member_id', $memberId)
            ->where('points', '<', 0)
            ->sum('points');
    }

    public function getTotalPointsRedeemedForJob(int $memberId): int
    {
        return (int) LoyaltyPointUpdate::query()
            ->where('member_id', $memberId)
            ->where('points', '<', 0)
            ->whereNot('type_id', LoyaltyPointUpdateTypes::EXPIRED->value)
            ->sum('points');
    }

    public function getTotalPointsExpired(int $userId, string $userType): int
    {
        return (int) LoyaltyPointUpdate::query()
            ->when(ModelMapping::EMPLOYEE->name === $userType, function ($query) use ($userId): void {
                $query->whereHas('member', function ($query) use ($userId): void {
                    $query->where('employee_id', $userId);
                });
            })
            ->when(ModelMapping::MEMBER->name === $userType, function ($query) use ($userId): void {
                $query->where('member_id', $userId);
            })
            ->where('points', '<', 0)
            ->where('type_id', LoyaltyPointUpdateTypes::EXPIRED->value)
            ->sum('points');
    }

    public function getTotalPointRewardedForEmployee(int $employeeId): int
    {
        return (int) LoyaltyPointUpdate::query()
            ->whereHas('member', function ($query) use ($employeeId): void {
                $query->where('employee_id', $employeeId);
            })
            ->where('points', '>', 0)
            ->sum('points');
    }

    public function getTotalPointsRedeemedForEmployee(int $employeeId): int
    {
        return (int) LoyaltyPointUpdate::query()
            ->whereHas('member', function ($query) use ($employeeId): void {
                $query->where('employee_id', $employeeId);
            })
            ->where('points', '<', 0)
            ->sum('points');
    }

    public function getTotalPointsRedeemedForEmployeeForJob(int $employeeId): int
    {
        return (int) LoyaltyPointUpdate::query()
            ->whereHas('member', function ($query) use ($employeeId): void {
                $query->where('employee_id', $employeeId);
            })
            ->where('points', '<', 0)
            ->whereNot('type_id', LoyaltyPointUpdateTypes::EXPIRED->value)
            ->sum('points');
    }

    public function getUsedLoyaltyPoint(int $affectedById, string $affectedByType, int $typeId): Collection
    {
        return LoyaltyPointUpdate::select(
            'id',
            'loyalty_point_id',
            'member_id',
            'affected_by_id',
            'affected_by_type',
            'type_id',
            'points'
        )
            ->with('loyaltyPoint')
            ->where('affected_by_id', $affectedById)
            ->where('affected_by_type', $affectedByType)
            ->where('type_id', $typeId)
            ->get();
    }

    public function getSalesById(int $loyaltyPointUpdateId, int $memberId): LoyaltyPointUpdate
    {
        $saleQueries = resolve(SaleQueries::class);

        return LoyaltyPointUpdate::select(
            'id',
            'loyalty_point_id',
            'member_id',
            'affected_by_id',
            'affected_by_type',
            'type_id',
            'points',
            'happened_at',
            'closing_loyalty_points_balance',
        )
            ->with([
                'affectedBy' => function (MorphTo $morphTo) use ($saleQueries): void {
                    $morphTo->constrain([
                        Sale::class => $saleQueries->getSelectUsedLoyaltyPointColumn(),
                    ]);
                },
            ])

            ->where('affected_by_type', ModelMapping::SALE->name)
            ->where('member_id', $memberId)
            ->where(function ($query): void {
                $query->where('type_id', LoyaltyPointUpdateTypes::USED->value)
                ->orWhere('type_id', LoyaltyPointUpdateTypes::SALE->value);
            })
            ->findOrFail($loyaltyPointUpdateId);
    }

    public function getMemberLoyaltyPointDetails(int $memberId): Collection
    {
        $adminQueries = resolve(AdminQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $voidSaleQueries = resolve(VoidSaleQueries::class);

        return LoyaltyPointUpdate::query()
            ->select(
                'id',
                'member_id',
                'points',
                'closing_loyalty_points_balance',
                'type_id',
                'happened_at',
                'affected_by_id',
                'affected_by_type'
            )
            ->with([
                'affectedBy' => function (MorphTo $morphTo) use (
                    $adminQueries,
                    $saleQueries,
                    $saleItemQueries,
                    $saleReturnQueries,
                    $saleReturnItemQueries,
                    $voidSaleQueries
                ): void {
                    $morphTo->constrain([
                        Sale::class => $saleQueries->getSelectIdANdOfflineIdColumn(),
                        SaleItem::class => $saleItemQueries->getOfflineSaleWithRelation(),
                        SaleReturn::class => $saleReturnQueries->getSelectIdANdOfflineIdColumn(),
                        SaleReturnItem::class => $saleReturnItemQueries->getSelectIdANdOfflineIdColumn(),
                        VoidSale::class => $voidSaleQueries->getSelectIdAndSaleIdColumns(),
                        Admin::class => $adminQueries->getEmployeeWithRelation(),
                    ]);
                },
            ])
            ->where('member_id', $memberId)
            ->orderBy('happened_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getLoyaltyPointDetailsForEcommerceSyncByIdAndCompanyId(int $id, int $companyId): LoyaltyPointUpdate
    {
        $adminQueries = resolve(AdminQueries::class);
        $saleQueries = resolve(SaleQueries::class);
        $saleItemQueries = resolve(SaleItemQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturnItemQueries = resolve(SaleReturnItemQueries::class);
        $voidSaleQueries = resolve(VoidSaleQueries::class);
        $orderQueries = resolve(OrderQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return LoyaltyPointUpdate::query()
            ->select(
                'id',
                'member_id',
                'points',
                'closing_loyalty_points_balance',
                'type_id',
                'happened_at',
                'affected_by_id',
                'affected_by_type',
                'remarks'
            )
            ->with([
                'affectedBy' => function (MorphTo $morphTo) use (
                    $adminQueries,
                    $saleQueries,
                    $saleItemQueries,
                    $saleReturnQueries,
                    $saleReturnItemQueries,
                    $voidSaleQueries,
                    $orderQueries
                ): void {
                    $morphTo->constrain([
                        Sale::class => $saleQueries->getSelectIdANdOfflineIdColumn(),
                        SaleItem::class => $saleItemQueries->getOfflineSaleWithRelation(),
                        SaleReturn::class => $saleReturnQueries->getSelectIdANdOfflineIdColumn(),
                        SaleReturnItem::class => $saleReturnItemQueries->getSelectIdANdOfflineIdColumn(),
                        VoidSale::class => $voidSaleQueries->getSelectIdAndSaleIdColumns(),
                        Admin::class => $adminQueries->getEmployeeWithRelation(),
                        Order::class => $orderQueries->getReceiptNumberColumn(),
                    ]);
                },
            ])
            ->whereHas('member', $memberQueries->filterByCompany($companyId))
            ->findOrFail($id);
    }

    public function updateMember(int $oldMemberId, int $newMemberId): void
    {
        $loyaltyPointUpdates = LoyaltyPointUpdate::query()
            ->select('id', 'member_id', 'remarks')
            ->where('member_id', $oldMemberId)
            ->get();

        foreach ($loyaltyPointUpdates as $loyaltyPointUpdate) {
            $loyaltyPointUpdate->member_id = $newMemberId;
            $loyaltyPointUpdate->remarks = 'Member Merge Performed Old Member Id :'. $oldMemberId . ' New Member Id: ' . $newMemberId;
            $loyaltyPointUpdate->save();
        }
    }

    public function getLoyaltyPointUpdates(int $newMemberId): Collection
    {
        return LoyaltyPointUpdate::query()
            ->select('id', 'member_id', 'points', 'closing_loyalty_points_balance')
            ->where('member_id', $newMemberId)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function updateClosingBalance(LoyaltyPointUpdate $loyaltyPointUpdate, int $closingBalance): void
    {
        $loyaltyPointUpdate->closing_loyalty_points_balance = $closingBalance;
        $loyaltyPointUpdate->save();
    }

    public function getAffectedBy(): Closure
    {
        return fn (MorphTo $morphTo) => $morphTo->constrain($this->getAffectedByMorphMap());
    }

    private function getAffectedByMorphMap(): array
    {
        $locationQueries = resolve(LocationQueries::class);

        return [
            Voucher::class => fn ($query) => $query->select('id', 'created_by_location_id')
                ->with(['createdByLocation:' .$locationQueries->getNameColumnName()]),
            Order::class => fn ($query) => $query->select('id', 'location_id')
                ->with(['location:' . $locationQueries->getNameColumnName()]),
            Sale::class => fn ($query): mixed => $this->withCounterRelations($query),
            SaleReturn::class => fn ($query): mixed => $this->withCounterRelations($query),
            SaleReturnItem::class => fn ($query) => $query->select('id', 'sale_return_id')
                    ->with([
                        'saleReturn' => fn ($q): mixed => $this->withCounterRelations($q),
                    ]),
            SaleItem::class => fn ($query) => $query->select('id', 'sale_id')
                    ->with([
                        'sale' => fn ($q): mixed => $this->withCounterRelations($q),
                    ]),
            VoidSale::class => fn ($query) => $query->select('id', 'sale_id')
                    ->with([
                        'sale' => fn ($q): mixed => $this->withCounterRelations($q),
                    ]),
            CancelCreditSale::class => fn ($query) => $query->select('id', 'sale_id')
                    ->with([
                        'sale' => fn ($q): mixed => $this->withCounterRelations($q),
                    ]),
            CancelLayawaySale::class => fn ($query) => $query->select('id', 'sale_id')
                    ->with([
                        'sale' => fn ($q): mixed => $this->withCounterRelations($q),
                    ]),
        ];
    }

    private function withCounterRelations(mixed $query): mixed
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $counterQueries = resolve(CounterQueries::class);

        return $query->select('id', 'counter_update_id')
            ->with([
                'counterUpdate:' . $counterUpdateQueries->getCounterIdColumnName(),
                'counterUpdate.counter:' . $counterQueries->getLocationIdColumn(),
                'counterUpdate.counter.location:' . $locationQueries->getBasicColumnNamesOfReport(),
            ]);
    }
}
