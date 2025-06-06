<?php

declare(strict_types=1);

namespace App\Domains\PaymentType\Exports;

use App\Models\PaymentType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentTypeReportExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $paymentTypeReportExportRecords
    ) {
    }

    public function collection(): Collection
    {
        return $this->paymentTypeReportExportRecords->map(fn (PaymentType $paymentType): array => [
            'number' => $paymentType->id,
            'payment_type' => $paymentType->name,
            'transactions' => $paymentType->total_transactions ?? 0,
            'amount' => $paymentType->total_amount ?? 0,
        ]);
    }

    public function headings(): array
    {
        return ['Number', 'Payment type', 'Transactions', 'Amount'];
    }
}
