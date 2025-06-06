<?php

declare(strict_types=1);

namespace App\Domains\SalePayment\Exports;

use App\CommonFunctions;
use App\Models\PaymentType;
use App\Models\SalePayment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalePaymentExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $salePayments
    ) {
    }

    public function collection(): Collection
    {
        return $this->salePayments->map(function (SalePayment $salePayment, int $index): array {
            /** @var PaymentType $paymentType */
            $paymentType = $salePayment->paymentType;

            return [
                'number' => $index + 1,
                'name' => $paymentType->name,
                /* @phpstan-ignore-next-line */
                'total_transactions' => (string) $salePayment->total_count,
                /* @phpstan-ignore-next-line */
                'total_amount' => CommonFunctions::currencyFormat((float) $salePayment->total_amount),
            ];
        });
    }

    public function headings(): array
    {
        return ['Number', 'Name', 'Transactions', 'Amount'];
    }
}
