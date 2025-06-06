<?php

namespace App\Domains\StoreManagerAuthorizationCodeUsage\Enum;

enum StoreManagerAuthorizationCodeUsageTypes: int
{
    case SALE_ITEM_PRICE_OVERRIDE = 1;
    case SALE_PRICE_OVERRIDE = 2;
    case LAYAWAY_SALE = 3;
    case CREDIT_SALE = 4;
    case COMPLIMENTARY_ITEM = 5;
    case BOOKING_PAYMENT = 6;
    case HAPPY_HOUR_DISCOUNT = 7;
    case VOID_SALE = 8;
    case CREDIT_NOTE_REFUND = 9;
    case HOLD_SALE_CANCEL = 10;
    case CASH_MOVEMENT = 11;
    case CANCEL_LAYAWAY_SALE = 12;
    case CANCEL_CREDIT_SALE = 13;
}
