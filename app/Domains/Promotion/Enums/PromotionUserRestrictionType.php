<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PromotionUserRestrictionType: int
{
    use PrepareEnumDataMethods;

    case ALL = 1;
    case ALLOW_REGISTERED_MEMBER = 2;
    case ALLOW_EMPLOYEE = 3;
    case ALLOW_WALK_IN_MEMBER = 4;

    /**
     * @return array<string, int>
     */
    public static function getPromotionUseRestrictionCondition(int $promotionUserRestrictionTypeId): array
    {
        $conditions = [
            PromotionUserRestrictionType::ALLOW_WALK_IN_MEMBER->value => [
                'allow_registered_member' => 0,
                'allow_employee' => 0,
            ],
            PromotionUserRestrictionType::ALLOW_REGISTERED_MEMBER->value => [
                'allow_registered_member' => 1,
                'allow_employee' => 0,
            ],
            PromotionUserRestrictionType::ALLOW_EMPLOYEE->value => [
                'allow_registered_member' => 0,
                'allow_employee' => 1,
            ],
        ];

        return $conditions[$promotionUserRestrictionTypeId] ?? [];
    }
}
