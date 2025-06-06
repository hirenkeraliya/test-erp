<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Enums;

use App\CommonFunctions;
use App\Http\Traits\PrepareEnumDataMethods;

enum TransferTypeForReport: int
{
    use PrepareEnumDataMethods;

    case TRANSFER_IN = 1;
    case TRANSFER_OUT = 2;
    case TRANSFER_ORDER = 3;
    case REQUEST_ORDER = 4;

    public static function getTransferInAndOutOnly(): array
    {
        return collect(self::cases())->map(function ($type) {
            if ($type->value === self::TRANSFER_ORDER->value) {
                return;
            }

            if ($type->value === self::REQUEST_ORDER->value) {
                return;
            }

            return [
                'id' => $type->value,
                'name' => CommonFunctions::stringTitleLowerCase($type->name),
            ];
        })->filter()->toArray();
    }
}
