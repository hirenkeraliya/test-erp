<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\Domains\DigitalInvoice\DigitalInvoiceQueries;
use App\Models\BookingPayment;
use App\Models\CreditNote;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use Carbon\Carbon;

class PrintDigitalInvoiceService
{
    public function print(int $moduleId, string $moduleType): string
    {
        $digitalInvoiceQueries = resolve(DigitalInvoiceQueries::class);

        $digitalInvoice = $digitalInvoiceQueries->getInvoiceDetailsById($moduleId, $moduleType);

        /** @var Sale|SaleReturn|Order|OrderReturn|BookingPayment|CreditNote $module */
        $module = $digitalInvoice?->module;

        if ($module instanceof Sale) {
            $digitalInvoice['receipt_number'] = $module->offline_sale_id;
            $digitalInvoice['digital_invoice_number'] = $module->digital_invoice_number;
        }

        if ($module instanceof SaleReturn) {
            $digitalInvoice['receipt_number'] = $module->offline_sale_return_id;
            $digitalInvoice['digital_invoice_number'] = $module->digital_invoice_number;
        }

        if ($module instanceof Order) {
            $digitalInvoice['receipt_number'] = $module->receipt_number;
            $digitalInvoice['digital_invoice_number'] = $module->digital_invoice_number;
        }

        if ($module instanceof OrderReturn) {
            $digitalInvoice['receipt_number'] = $module->receipt_number;
            $digitalInvoice['digital_invoice_number'] = $module->digital_invoice_number;
        }

        if ($module instanceof BookingPayment) {
            $digitalInvoice['receipt_number'] = $module->offline_id;
            $digitalInvoice['digital_invoice_number'] = $module->digital_invoice_number;
        }

        if ($module instanceof CreditNote) {
            $digitalInvoice['receipt_number'] = $module->saleReturn->offline_sale_return_id ?? $module->cancelLayawaySale->sale->offline_sale_id ?? 'N/A';
            $digitalInvoice['digital_invoice_number'] = $module->digital_invoice_number;
        }

        return view('prints.digital_invoice', [
            'digitalInvoice' => $digitalInvoice,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
        ])->render();
    }
}
