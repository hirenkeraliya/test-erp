<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use App\Domains\Sale\Enums\SeasonalReportTypes;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SeasonalSalesByDetailsExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $seasonalSalesData,
        protected Company $company,
        protected array $grandTotal,
        protected array $columns,
        protected array $dateRange,
        protected int $reportType,
        protected string $saleSeasonName,
        protected string $currencySymbol,
    ) {
    }

    public function view(): View
    {
        return view('prints.seasonal_sales_by_details', [
            'seasonalSalesData' => $this->seasonalSalesData,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'dateRange' => $this->dateRange,
            'columns' => $this->columns,
            'grandTotal' => $this->grandTotal,
            'reportType' => SeasonalReportTypes::getFormattedCaseName($this->reportType),
            'saleSeasonName' => $this->saleSeasonName,
            'currencySymbol' => $this->currencySymbol,
        ]);
    }
}
