<?php

declare(strict_types=1);

namespace App\Domains\Sale\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LayawaySalesBySummaryExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Company $company,
        protected array $layawaySalesData,
        protected array $grandTotal,
        protected array $columns,
        protected array $dateRange,
    ) {
    }

    public function view(): View
    {
        return view('prints.layaway_sales_by_summary', [
            'dateRange' => $this->dateRange,
            'layawaySalesData' => $this->layawaySalesData,
            'columns' => $this->columns,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'grandTotal' => $this->grandTotal,
        ]);
    }
}
