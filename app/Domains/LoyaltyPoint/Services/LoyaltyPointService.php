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
        $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $loyaltyPointsRecords = $loyaltyPointQueries->getByUserSortByExpiryDate($member->id);

        foreach ($loyaltyPointsRecords as $loyaltyPointRecord) {
            if ($loyaltyPointsToBeUsed <= 0) {
                return;
            }

            if ($loyaltyPointsToBeUsed > $loyaltyPointRecord->available_points) {
                $loyaltyPointsToBeUsed -= $loyaltyPointRecord->available_points;
                $userLoyaltyPoints -= $loyaltyPointRecord->available_points;

                $decreasePoints = $loyaltyPointRecord->available_points;
                $loyaltyPointQueries->decreasePoints($loyaltyPointRecord, $loyaltyPointRecord->available_points);

                $loyaltyPointUpdateQueries->addNew([
                    'member_id' => $member->id,
                    'loyalty_point_id' => $loyaltyPointRecord->id,
                    'affected_by_id' => $affectedById,
                    'affected_by_type' => $affectedByType,
                    'type_id' => $typeId,
                    'points' => (int) ('-' . $decreasePoints),
                    'closing_loyalty_points_balance' => $userLoyaltyPoints,
                    'happened_at' => $happenedAt,
                    'remarks' => $remarks,
                ]);

                continue;
            }

            $loyaltyPointQueries->decreasePoints($loyaltyPointRecord, $loyaltyPointsToBeUsed);
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
        }
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
