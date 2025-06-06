<?php

declare(strict_types=1);

namespace App\Domains\Company\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum CommissionTypes: int
{
    use PrepareEnumDataMethods;

    case BY_PROMOTER = 1;
    case BY_DEPARTMENT = 2;

    /**
     * @return array<string, int>
     */
    public static function toArray(): array
    {
        return [
            'by_promoter' => CommissionTypes::BY_PROMOTER->value,
            'by_department' => CommissionTypes::BY_DEPARTMENT->value,
        ];
    }
}
