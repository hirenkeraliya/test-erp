<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Enums;

use App\CommonFunctions;
use App\Http\Traits\PrepareEnumDataMethods;
use Illuminate\Support\Str;

enum StockTransferTypes: int
{
    use PrepareEnumDataMethods;

    case REQUEST_ORDER = 1;
    case TRANSFER_ORDER = 2;

    /**
     * @return array<string, string>
     */
    public static function getTransferNames(): array
    {
        return [
            'request_order' => Str::lower(self::REQUEST_ORDER->name),
            'transfer_order' => Str::lower(self::TRANSFER_ORDER->name),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getTitleTransferNames(): array
    {
        return [
            'request_order' => CommonFunctions::stringTitleLowerCase(self::REQUEST_ORDER->name),
            'transfer_order' => CommonFunctions::stringTitleLowerCase(self::TRANSFER_ORDER->name),
        ];
    }
}
