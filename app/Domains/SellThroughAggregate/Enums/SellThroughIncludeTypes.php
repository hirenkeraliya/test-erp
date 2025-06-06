<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SellThroughIncludeTypes: int
{
    use PrepareEnumDataMethods;

    case GOODS_RECEIVE_NOTE_IN = 1;
    case GOODS_RECEIVE_NOTE_OUT = 2;
    case STOCK_ADJUSTMENT_IN = 3;
    case STOCK_ADJUSTMENT_OUT = 4;
    case STOCK_TRANSFER_IN = 5;
    case STOCK_TRANSFER_OUT = 6;
    case DELIVERY_ORDER_IN = 7;
    case DELIVERY_ORDER_OUT = 8;

    public static function getModuleBasedGroupedTypes(): array
    {
        return [
            'goods_received_notes' => [self::GOODS_RECEIVE_NOTE_IN->value, self::GOODS_RECEIVE_NOTE_OUT->value],
            'stock_adjustment' => [self::STOCK_ADJUSTMENT_IN->value, self::STOCK_ADJUSTMENT_OUT->value],
            'stock_transfer' => [self::STOCK_TRANSFER_IN->value, self::STOCK_TRANSFER_OUT->value],
            'delivery_order' => [self::DELIVERY_ORDER_IN->value, self::DELIVERY_ORDER_OUT->value],
        ];
    }
}
