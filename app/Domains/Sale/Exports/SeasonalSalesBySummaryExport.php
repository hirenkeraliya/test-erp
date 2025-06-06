<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SeasonalSalesBySummaryExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $seasonalSalesData,
        protected Company $company,
        protected float $grandTotal,
        protected float $grandTotalCompare,
        protected string $saleSeasonName,
        protected string $compareSaleSeasonName,
        protected string $currencySymbol,
    ) {
    }

    public function view(): View
    {
        return view('prints.seasonal_sales_by_summary', [
            'seasonalSalesData' => $this->seasonalSalesData,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'grandTotal' => $this->grandTotal,
            'grandTotalCompare' => $this->grandTotalCompare,
            'saleSeasonName' => $this->saleSeasonName,
            'compareSaleSeasonName' => $this->compareSaleSeasonName,
            'currencySymbol' => $this->currencySymbol,
        ]);
    }
}
