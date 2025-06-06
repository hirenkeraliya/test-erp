<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SalesCollectionBySummaryDetailsExport implements FromView
{
    public function __construct(
        protected array $locationsSales,
        protected float $totalQuantity,
        protected float $totalGrossSales,
        protected float $totalDiscountAmount,
        protected float $totalNetSaleExclusiveTax,
        protected float $totalNetSaleInclusiveTax,
        protected float $totalTaxAmount,
        protected Company $company,
        protected array $dateRange,
        protected string $filterBy,
        protected ?int $eInvoiceSubmitted,
    ) {
    }

    public function view(): View
    {
        return view('prints.sales_by_summary_details', [
            'locationSales' => $this->locationsSales,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'totalQty' => $this->totalQuantity,
            'totalGross' => $this->totalGrossSales,
            'totalDiscount' => $this->totalDiscountAmount,
            'totalNetSaleEx' => $this->totalNetSaleExclusiveTax,
            'totalTaxAmount' => $this->totalTaxAmount,
            'totalNetSaleIn' => $this->totalNetSaleInclusiveTax,
            'filterBy' => $this->filterBy,
            'excludeByEInvoiceFilter' => $this->eInvoiceSubmitted,
        ]);
    }
}
