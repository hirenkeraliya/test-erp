<?php

declare(strict_types=1);

namespace App\Domains\StoreDayClose\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StoreDayCloseFtpExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $exportRows
    ) {
    }

    public function collection(): Collection
    {
        return $this->exportRows->map(fn (array $row): array => [
            $row['supplier_code'],
            $row['buyer_code'],
            $row['annex_k1'],
            $row['annex_incoterms'],
            $row['annex_fta'],
            $row['annex_atiga'],
            $row['annex_k2'],
            $row['annex_other_charges_description'],
            $row['annex_other_charges_amount'],
            $row['header_source'],
            $row['header_einvoice_type'],
            $row['header_invoice_number'],
            $row['header_original_invoice_number'],
            $row['header_invoice_date'],
            $row['header_outlet_code'],
            $row['header_currency_code'],
            $row['header_currency_rate'],
            $row['header_discount_amount'],
            $row['header_charge_amount'],
            $row['header_total_payable_amount'],
            $row['header_payment_terms'],
            $row['item_classification'],
            $row['item_code'],
            $row['item_description'],
            $row['item_quantity'],
            $row['item_unit_price'],
            $row['item_discount_amount'],
            $row['item_charge_amount'],
            $row['item_tax_type'],
            $row['item_tax_rate'],
            $row['item_details_tax_exemption'],
            $row['item_exempt_tax_amount'],
        ]);
    }

    public function headings(): array
    {
        return [
            'SUPPLIER_Supplier code',
            'BUYER_Buyer code',
            'ANNEX_K1',
            'ANNEX_incoterms',
            'ANNEX_FTA',
            'ANNEX_ATIGA',
            'ANNEX_K2',
            'ANNEX_other charges description ',
            'ANNEX_other charges amount',
            'HEADER_source',
            'HEADER_einvoice_type',
            'HEADER_invoice_number',
            'HEADER_reference_number',
            'HEADER_Invoice Date',
            'HEADER_outlet_code',
            'HEADER_currency_code',
            'HEADER_currency_rate',
            'HEADER_discount_amount',
            'HEADER_charge_amount',
            'HEADER_total_payable_amount',
            'HEADER_payment_terms',
            'ITEM_classification',
            'ITEM_code',
            'ITEM_description',
            'ITEM_quantity',
            'ITEM_unit_price',
            'ITEM_discount_amount',
            'ITEM_charge_amount',
            'ITEM_tax_type',
            'ITEM_tax_rate',
            'ITEM_details_tax_exemption',
            'ITEM_exempt_tax_amount',
        ];
    }
}
