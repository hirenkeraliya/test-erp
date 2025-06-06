<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyPoint;

use App\Domains\Member\MemberQueries;
use App\Models\LoyaltyPoint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        // Use atomic database operation to prevent race conditions
        $affectedRows = DB::table('loyalty_points')
            ->where('id', $loyaltyPoint->id)
            ->where('available_points', '>=', $points) // Ensure sufficient points
            ->update([
                'available_points' => DB::raw('available_points - ' . $points),
                'updated_at' => now(),
            ]);

        if ($affectedRows === 0) {
            throw new \RuntimeException('Insufficient loyalty points or concurrent modification detected');
        }

        // Refresh the model to get updated values
        $loyaltyPoint->refresh();
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
        // Use atomic database operation to prevent race conditions
        $affectedRows = DB::table('loyalty_points')
            ->where('id', $loyaltyPoint->id)
            ->where('available_points', '>', 0) // Only update if there are points to expire
            ->update([
                'available_points' => 0,
                'updated_at' => now(),
            ]);

        // Refresh the model to get updated values
        $loyaltyPoint->refresh();
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
