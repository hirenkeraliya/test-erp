<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Services;

use App\Domains\Member\MemberQueries;
use App\Domains\MemberGroup\Enums\SmartGroupTypes;
use Illuminate\Http\Request;

class MemberGroupService
{
    public function isPurchaseCountType(Request $request): bool
    {
        return $request->smartGroupType == SmartGroupTypes::PURCHASE_COUNT->value && null != $request->value;
    }

    public function isTotalSpentType(Request $request): bool
    {
        return $request->smartGroupType == SmartGroupTypes::LIFETIME_SPENT->value && null != $request->value;
    }

    public function isDateType(Request $request): bool
    {
        $dateTypes = [
            SmartGroupTypes::PURCHASE_DATE->value,
            SmartGroupTypes::FIRST_VISIT_DATE->value,
            SmartGroupTypes::LAST_VISIT_DATE->value,
        ];

        return in_array($request->smartGroupType, $dateTypes) && $request->date;
    }

    public function isProductType(Request $request): bool
    {
        return $request->smartGroupType == SmartGroupTypes::ITEM->value && $request->productIds;
    }

    public function isCategoryType(Request $request): bool
    {
        return $request->smartGroupType == SmartGroupTypes::CATEGORY->value && $request->categoryIds;
    }

    public function getDateByMemberCount(string $date, ?string $maxDate, int $dateCondition, int $smartGroupType): int
    {
        $memberQueries = resolve(MemberQueries::class);

        if ($smartGroupType == SmartGroupTypes::PURCHASE_DATE->value) {
            return $memberQueries->getPurchaseDateByMemberCount($date, $maxDate, $dateCondition);
        }

        if ($smartGroupType == SmartGroupTypes::FIRST_VISIT_DATE->value) {
            return $memberQueries->getFirstVisitDateByMemberCount($date, $maxDate, $dateCondition);
        }

        if ($smartGroupType == SmartGroupTypes::LAST_VISIT_DATE->value) {
            return $memberQueries->getLastVisitDateByMemberCount($date, $maxDate, $dateCondition);
        }

        return 0;
    }
}
