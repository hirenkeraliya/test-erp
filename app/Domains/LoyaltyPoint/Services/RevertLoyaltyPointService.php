<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyPoint\Services;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\MemberQueries;
use App\Models\Member;
use App\Models\Model;

class RevertLoyaltyPointService
{
    public function increaseLoyaltyPoints(
        Member $member,
        Model $affectedBy,
        int $applicableLoyaltyPoints,
        string $happenedAt,
        ?string $loyaltyExpiryDate,
    ): void {
        $this->updateUserLoyaltyPoints($member, $applicableLoyaltyPoints);

        $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);
        $loyaltyPoint = $loyaltyPointQueries->addNew([
            'member_id' => $member->id,
            'points' => $applicableLoyaltyPoints,
            'available_points' => $applicableLoyaltyPoints,
            'minimum_spend_amount' => 0,
            'expiry_date' => $loyaltyExpiryDate,
        ]);

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $loyaltyPointUpdateQueries->addNew([
            'member_id' => $member->id,
            'loyalty_point_id' => $loyaltyPoint->id,
            'affected_by_id' => $affectedBy->id,
            'affected_by_type' => ModelMapping::getCaseName($affectedBy::class),
            'type_id' => LoyaltyPointUpdateTypes::REVERT->value,
            'points' => $applicableLoyaltyPoints,
            'closing_loyalty_points_balance' => $member->loyalty_points,
            'happened_at' => $happenedAt,
        ]);
    }

    public function updateUserLoyaltyPoints(Member $member, int $loyaltyPoints): void
    {
        $memberQuery = resolve(MemberQueries::class);
        $memberQuery->increaseLoyaltyPoints($member, $loyaltyPoints);
    }
}
