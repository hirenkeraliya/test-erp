<?php

declare(strict_types=1);

namespace App\Domains\Sequence\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SequenceTypes: int
{
    use PrepareEnumDataMethods;

    case RO = 1; // Request Order
    case TO = 2; // Transfer Order
    case TIN = 3; // Transfer In
    case TOUT = 4; // Transfer Out
    case PR = 5; // Purchase Request
    case TR = 6; // Transfer Request
    case SO = 7; // Sale Order
    case PO = 8; // Purchase Order
    case OR = 9; // Wholesale Order
    case ORR = 10; // Wholesale Order Return
    case PODO = 11; // Purchase Order Delivery Order
    case SODO = 12; // Sale Order Delivery Order
    case IN = 13; // Purchase Order Invoice Number
    case OP = 14; // Order Picking List Number
    case SS = 15; // Store Sales
    case OS = 16; // Online Sales (orders)
    case BP = 17; // Booking Payment
    case SR = 18; // Sale Return
    case ORT = 19; // Order Return
    case CN = 20; // Credit Notes
    case PP = 21; // Purchase Plan
    case EPO = 22; // External Purchase Order
    case SODOPR = 23; // Sale Order Delivery Order Partially Receive

    public static function formattedForDigitalInvoice(): array
    {
        return collect(self::cases())->map(
            function ($type) {
                if ($type->value === self::SS->value || $type->value === self::BP->value || $type->value === self::SR->value || $type->value === self::CN->value) {
                    return [
                        'id' => $type->name,
                        'name' => self::getFullNameById($type->value),
                    ];
                }
            }
        )->filter()->values()->toArray();
    }

    public static function getFullNameById(int $type): string
    {
        if ($type === self::SS->value) {
            return 'Store Sales';
        }

        if ($type === self::BP->value) {
            return 'Booking Payments';
        }

        if ($type === self::SR->value) {
            return 'Sales Return';
        }

        if ($type === self::CN->value) {
            return 'Credit Notes';
        }

        return '';
    }
}
