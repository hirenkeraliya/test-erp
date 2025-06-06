<?php

declare(strict_types=1);

namespace App\Domains\DigitalInvoice\Services;

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderReturn\OrderReturnQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use InvalidArgumentException;

class DigitalInvoiceService
{
    public function getObject(
        string $moduleName
    ): SaleQueries|OrderQueries|BookingPaymentQueries|SaleReturnQueries|CreditNoteQueries|OrderReturnQueries {
        if ($moduleName === ModelMapping::SALE->name) {
            return resolve(SaleQueries::class);
        }

        if ($moduleName === ModelMapping::ORDER->name) {
            return resolve(OrderQueries::class);
        }

        if ($moduleName === ModelMapping::BOOKING_PAYMENT->name) {
            return resolve(BookingPaymentQueries::class);
        }

        if ($moduleName === ModelMapping::SALE_RETURN->name) {
            return resolve(SaleReturnQueries::class);
        }

        if ($moduleName === ModelMapping::CREDIT_NOTE->name) {
            return resolve(CreditNoteQueries::class);
        }

        if ($moduleName === ModelMapping::ORDER_RETURN->name) {
            return resolve(OrderReturnQueries::class);
        }

        throw new InvalidArgumentException('Invalid module name: ' . $moduleName);
    }
}
