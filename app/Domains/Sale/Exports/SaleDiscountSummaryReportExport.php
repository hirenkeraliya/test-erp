<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SaleDiscountSummaryReportExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $saleDiscounts,
        protected array $dateRange,
        protected Company $company,
        protected array $columns,
        protected string $reportType,
        protected array $grandTotal,
    ) {
    }

    public function view(): View
    {
        return view('prints.sale_discount_summary_report', [
            'saleDiscounts' => $this->saleDiscounts,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
            'reportType' => $this->reportType,
            'grandTotal' => $this->grandTotal,
        ]);
    }
}
