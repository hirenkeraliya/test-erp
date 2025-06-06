<?php

declare(strict_types=1);

namespace App\Domains\ImportRecord\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Status: int
{
    use PrepareEnumDataMethods;

    case PENDING = 1;
    case IN_PROGRESS = 2;
    case COMPLETED = 3;

    public static function getStatuses(): array
    {
        return [
            'pending' => self::getFormattedCaseName(self::PENDING->value),
            'progress' => self::getFormattedCaseName(self::IN_PROGRESS->value),
            'completed' => self::getFormattedCaseName(self::COMPLETED->value),
        ];
    }
}
