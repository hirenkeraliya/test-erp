<?php

declare(strict_types=1);

namespace App\Domains\Order\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum OrderStatus: int
{
    use PrepareEnumDataMethods;

    case PLACED = 1;
    case ACCEPTED = 2;
    case PACKING = 3;
    case READY_FOR_PICKUP = 4;
    case OUT_FOR_DELIVERY = 5;
    case DELIVERED = 6;
    case CANCELLED = 7;
    case DECLINED = 8;
    case RETURNED = 9;
    case REFUNDED = 10;
    case UNDELIVERED = 11;

    public function next(): ?array
    {
        return match ($this) {
            self::PLACED => [self::ACCEPTED, self::PACKING, self::CANCELLED],
            self::ACCEPTED => [self::PACKING, self::CANCELLED, self::DECLINED],
            self::PACKING => [self::READY_FOR_PICKUP],
            self::READY_FOR_PICKUP => [self::OUT_FOR_DELIVERY],
            self::OUT_FOR_DELIVERY => [self::DELIVERED, self::UNDELIVERED],
            self::DELIVERED => [self::RETURNED],
            self::CANCELLED => [self::REFUNDED],
            self::RETURNED => [self::REFUNDED],
            self::DECLINED => [self::REFUNDED],
            self::REFUNDED => null,
            self::UNDELIVERED => null,
        };
    }

    public static function isValidTransition(self $currentOrderStatus, string $nextOrderStatus): bool
    {
        return in_array(self::getCaseWithName($nextOrderStatus), $currentOrderStatus->next() ?? [], true);
    }
}
