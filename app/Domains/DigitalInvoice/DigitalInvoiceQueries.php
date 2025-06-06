<?php

declare(strict_types=1);

namespace App\Domains\DigitalInvoice;

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderReturn\OrderReturnQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Models\BookingPayment;
use App\Models\CreditNote;
use App\Models\DigitalInvoice;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DigitalInvoiceQueries
{
    public function addNew(array $data): void
    {
        DigitalInvoice::create($data);
    }

    public function getInvoiceDetailsById(int $moduleId, string $moduleType): ?DigitalInvoice
    {
        $saleQueries = resolve(SaleQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $orderQueries = resolve(OrderQueries::class);
        $orderReturnQueries = resolve(OrderReturnQueries::class);
        $bookingPaymentQueries = resolve(BookingPaymentQueries::class);
        $creditNoteQueries = resolve(CreditNoteQueries::class);

        return DigitalInvoice::query()
            ->select(
                'id',
                'module_type',
                'module_id',
                'buyer_name',
                'buyer_tin',
                'buyer_identification_number',
                'buyer_sst_number',
                'buyer_email',
                'buyer_address',
                'buyer_contact'
            )
            ->with([
                'module' => function (MorphTo $morphTo) use (
                    $saleQueries,
                    $saleReturnQueries,
                    $orderQueries,
                    $orderReturnQueries,
                    $bookingPaymentQueries,
                    $creditNoteQueries
                ): void {
                    $morphTo->constrain([
                        Sale::class => $saleQueries->getBasicColumnForDigitalInvoice(),
                        SaleReturn::class => $saleReturnQueries->getBasicColumnForDigitalInvoice(),
                        Order::class => $orderQueries->getBasicColumnForDigitalInvoice(),
                        OrderReturn::class => $orderReturnQueries->getBasicColumnForDigitalInvoice(),
                        BookingPayment::class => $bookingPaymentQueries->getBasicColumnForDigitalInvoice(),
                        CreditNote::class => $creditNoteQueries->getBasicColumnForDigitalInvoice(),
                    ]);
                },
            ])
            ->where('module_id', $moduleId)
            ->where('module_type', $moduleType)
            ->first();
    }
}
