<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyPoint;

use App\Domains\Member\MemberQueries;
use App\Models\LoyaltyPoint;
use Illuminate\Support\Collection;

class LoyaltyPointQueries
{
    public function getByUserSortByExpiryDate(int $memberId): Collection
    {
        return LoyaltyPoint::query()
            ->where('member_id', $memberId)
            ->where('available_points', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    public function decreasePoints(LoyaltyPoint $loyaltyPoint, int $points): void
    {
        $loyaltyPoint->available_points -= $points;
        $loyaltyPoint->save();
    }

    public function addNew(array $data): LoyaltyPoint
    {
        return LoyaltyPoint::create($data);
    }

    public function getLoyaltyPointsDueForExpiry(string $date): Collection
    {
        $memberQueries = resolve(MemberQueries::class);

        return LoyaltyPoint::select(
            'id',
            'member_id',
            'sale_id',
            'order_id',
            'expiry_date',
            'points',
            'available_points',
            'minimum_spend_amount'
        )
            ->with(['member:' . $memberQueries->getCompanyIdColumn()])
            ->where('expiry_date', '<', $date)
            ->where('available_points', '>', 0)
            ->get();
    }

    public function decreaseLoyaltyPointsToZero(LoyaltyPoint $loyaltyPoint): void
    {
        $loyaltyPoint->available_points = 0;
        $loyaltyPoint->save();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,member_id,sale_id,order_id,loyalty_campaign_id,expiry_date,points,minimum_spend_amount,available_points';
    }

    public function getLoyaltyPointForGivenSale(int $saleId): Collection
    {
        return LoyaltyPoint::select('id', 'member_id', 'sale_id', 'points', 'available_points')
            ->where('sale_id', $saleId)
            ->get();
    }

    public function setNewAvailablePointsAndPoints(
        LoyaltyPoint $loyaltyPoint,
        int $points,
        int $availablePoints,
    ): void {
        $loyaltyPoint->available_points = $availablePoints;
        $loyaltyPoint->points = $points;
        $loyaltyPoint->save();
    }

    public function updateMember(int $oldMemberId, int $newMemberId): void
    {
        $loyaltyPoints = LoyaltyPoint::query()
            ->select('id', 'member_id')
            ->where('member_id', $oldMemberId)
            ->get();

        foreach ($loyaltyPoints as $loyaltyPoint) {
            $loyaltyPoint->member_id = $newMemberId;
            $loyaltyPoint->save();
        }
    }

    public function getLoyaltyPointForGivenOrder(int $orderId): Collection
    {
        return LoyaltyPoint::select('id', 'member_id', 'order_id', 'points', 'available_points')
            ->where('order_id', $orderId)
            ->get();
    }
}
