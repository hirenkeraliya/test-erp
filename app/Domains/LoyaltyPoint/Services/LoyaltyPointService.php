<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyPoint\Services;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\DataObjects\UpdateLoyaltyPointData;
use App\Domains\Member\MemberQueries;
use App\Models\Admin;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

class LoyaltyPointService
{
    public function decreaseLoyaltyPoints(
        Member $member,
        int $loyaltyPoints,
        int $typeId,
        int $affectedById,
        string $affectedByType,
        string $happenedAt,
        string $remarks = '',
    ): void {
        $userLoyaltyPoints = (int) $member->loyalty_points;

        $this->updateUserLoyaltyPoints($member, $loyaltyPoints);

        $this->decreaseLoyaltyPointsByFirstExpiryFirstOut(
            $member,
            $userLoyaltyPoints,
            $loyaltyPoints,
            $typeId,
            $affectedById,
            $affectedByType,
            $happenedAt,
            $remarks,
        );
    }

    public function updateUserLoyaltyPoints(Member $member, int $loyaltyPoints): void
    {
        $memberQuery = resolve(MemberQueries::class);
        $memberQuery->decreaseLoyaltyPoints($member->id, $loyaltyPoints);
    }

    public function decreaseLoyaltyPointsByFirstExpiryFirstOut(
        Member $member,
        int $userLoyaltyPoints,
        int $loyaltyPointsToBeUsed,
        int $typeId,
        int $affectedById,
        string $affectedByType,
        string $happenedAt,
        string $remarks = '',
    ): void {
        // Wrap the entire operation in a database transaction to ensure atomicity
        // and prevent race conditions with expiration jobs
        DB::transaction(function () use (
            $member,
            &$userLoyaltyPoints,
            &$loyaltyPointsToBeUsed,
            $typeId,
            $affectedById,
            $affectedByType,
            $happenedAt,
            $remarks
        ) {
            $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);
            $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
            
            // Get loyalty points with row-level locking to prevent concurrent modifications
            $loyaltyPointsRecords = DB::table('loyalty_points')
                ->where('member_id', $member->id)
                ->where('available_points', '>', 0)
                ->orderBy('expiry_date', 'asc')
                ->lockForUpdate() // This prevents other transactions from modifying these rows
                ->get()
                ->map(function ($record) {
                    return (object) $record;
                });

            foreach ($loyaltyPointsRecords as $loyaltyPointRecord) {
                if ($loyaltyPointsToBeUsed <= 0) {
                    return;
                }

                // Refresh the record to get the latest available_points value
                $currentRecord = DB::table('loyalty_points')
                    ->where('id', $loyaltyPointRecord->id)
                    ->first();
                
                if (!$currentRecord || $currentRecord->available_points <= 0) {
                    // Points may have been expired by another process, skip this record
                    continue;
                }

                $availablePoints = $currentRecord->available_points;

                if ($loyaltyPointsToBeUsed > $availablePoints) {
                    $loyaltyPointsToBeUsed -= $availablePoints;
                    $userLoyaltyPoints -= $availablePoints;

                    try {
                        // Use the atomic decreasePoints method
                        $loyaltyPoint = new \App\Models\LoyaltyPoint();
                        $loyaltyPoint->id = $loyaltyPointRecord->id;
                        $loyaltyPointQueries->decreasePoints($loyaltyPoint, $availablePoints);

                        $loyaltyPointUpdateQueries->addNew([
                            'member_id' => $member->id,
                            'loyalty_point_id' => $loyaltyPointRecord->id,
                            'affected_by_id' => $affectedById,
                            'affected_by_type' => $affectedByType,
                            'type_id' => $typeId,
                            'points' => (int) ('-' . $availablePoints),
                            'closing_loyalty_points_balance' => $userLoyaltyPoints,
                            'happened_at' => $happenedAt,
                            'remarks' => $remarks,
                        ]);
                    } catch (\RuntimeException $e) {
                        // Points were modified concurrently, skip this record
                        continue;
                    }

                    continue;
                }

                try {
                    // Use the atomic decreasePoints method
                    $loyaltyPoint = new \App\Models\LoyaltyPoint();
                    $loyaltyPoint->id = $loyaltyPointRecord->id;
                    $loyaltyPointQueries->decreasePoints($loyaltyPoint, $loyaltyPointsToBeUsed);
                    
                    $userLoyaltyPoints -= $loyaltyPointsToBeUsed;
                    $loyaltyPointUpdateQueries->addNew([
                        'member_id' => $member->id,
                        'loyalty_point_id' => $loyaltyPointRecord->id,
                        'affected_by_id' => $affectedById,
                        'affected_by_type' => $affectedByType,
                        'type_id' => $typeId,
                        'points' => (int) ('-' . $loyaltyPointsToBeUsed),
                        'closing_loyalty_points_balance' => $userLoyaltyPoints,
                        'happened_at' => $happenedAt,
                        'remarks' => $remarks,
                    ]);
                    $loyaltyPointsToBeUsed = 0;
                } catch (\RuntimeException $e) {
                    // Points were modified concurrently, continue to next record
                    continue;
                }
            }
        });
    }

    public function updateLoyaltyPointsForAdmin(
        Member $member,
        Admin $admin,
        UpdateLoyaltyPointData $updateLoyaltyPointData
    ): void {
        $userLoyaltyPoints = $member->loyalty_points;

        if ($userLoyaltyPoints < $updateLoyaltyPointData->loyalty_points) {
            $loyaltyPoints = $updateLoyaltyPointData->loyalty_points - $userLoyaltyPoints;

            $this->increaseLoyaltyPointsForAdmin(
                $member,
                $admin,
                $loyaltyPoints,
                $updateLoyaltyPointData->remarks,
                $userLoyaltyPoints
            );

            return;
        }

        if ($userLoyaltyPoints > $updateLoyaltyPointData->loyalty_points) {
            $loyaltyPoints = $userLoyaltyPoints - $updateLoyaltyPointData->loyalty_points;

            $this->decreaseLoyaltyPoints(
                $member,
                $loyaltyPoints,
                LoyaltyPointUpdateTypes::MANUAL_UPDATE->value,
                $admin->id,
                ModelMapping::ADMIN->name,
                now()->format('Y-m-d H:i:s'),
                $updateLoyaltyPointData->remarks,
            );
        }
    }

    public function increaseLoyaltyPointsForAdmin(
        Member $member,
        Admin $admin,
        int $applicableLoyaltyPoints,
        string $remarks,
        ?int $userLoyaltyPoints = 0
    ): void {
        $memberQuery = resolve(MemberQueries::class);
        $memberQuery->increaseLoyaltyPoints($member, $applicableLoyaltyPoints);

        $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);
        $loyaltyPoint = $loyaltyPointQueries->addNew([
            'member_id' => $member->id,
            'expiry_date' => null,
            'points' => $applicableLoyaltyPoints,
            'available_points' => $applicableLoyaltyPoints,
            'minimum_spend_amount' => 0,
        ]);

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $loyaltyPointUpdateQueries->addNew([
            'member_id' => $member->id,
            'loyalty_point_id' => $loyaltyPoint->id,
            'affected_by_id' => $admin->id,
            'affected_by_type' => ModelMapping::ADMIN->name,
            'type_id' => LoyaltyPointUpdateTypes::MANUAL_UPDATE->value,
            'points' => $applicableLoyaltyPoints,
            'closing_loyalty_points_balance' => $applicableLoyaltyPoints + $userLoyaltyPoints,
            'happened_at' => now()->format('Y-m-d H:i:s'),
            'remarks' => $remarks,
        ]);
    }

    public function mergeLoyaltyPoints(int $oldMemberId, int $newMemberId): void
    {
        $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);
        $loyaltyPointQueries->updateMember($oldMemberId, $newMemberId);

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $loyaltyPointUpdateQueries->updateMember($oldMemberId, $newMemberId);

        $loyaltyPointUpdates = $loyaltyPointUpdateQueries->getLoyaltyPointUpdates($newMemberId);

        $closingBalancePoints = 0;
        foreach ($loyaltyPointUpdates as $loyaltyPointUpdate) {
            $closingBalancePoints += $loyaltyPointUpdate->points;
            $loyaltyPointUpdateQueries->updateClosingBalance($loyaltyPointUpdate, $closingBalancePoints);
        }
    }
}
