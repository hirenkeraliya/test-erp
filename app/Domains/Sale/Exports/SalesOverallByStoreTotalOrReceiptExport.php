<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SalesOverallByStoreTotalOrReceiptExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $locationSaleDetails,
        protected array $columns,
        protected Company $company,
        protected array $dateRange,
        protected string $reportType,
    ) {
    }

    public function view(): View
    {
        return view('prints.sales_overall_by_store', [
            'allLocations' => $this->locationSaleDetails,
            'dateRange' => $this->dateRange,
            'reportType' => $this->reportType,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
        ]);
    }
}
