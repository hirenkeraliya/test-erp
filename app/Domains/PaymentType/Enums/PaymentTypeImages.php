<?php

declare(strict_types=1);

namespace App\Domains\PaymentType\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PaymentTypeImages: string
{
    use PrepareEnumDataMethods;

    case CASH = 'cash.png';
    case CREDIT_CARD = 'credit-card.png';
    case DEBIT_CARD = 'debit-card.png';
    case E_BANK_TRANSFER = 'e-bank-transfer.png';
    case E_WALLET = 'e-wallet.png';
    case MOBILE_PAYMENT = 'mobile-payment.png';
}
