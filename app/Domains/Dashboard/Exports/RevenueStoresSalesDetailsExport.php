<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RevenueStoresSalesDetailsExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $locationSales,
        protected array $salesTotalData,
        protected string $brandName,
        protected Company $company,
        protected string $currencySymbol,
        protected array $date,
    ) {
    }

    public function view(): View
    {
        return view('prints.revenue_dashboard_store_sales_details', [
            'locationSales' => $this->locationSales,
            'salesTotalData' => $this->salesTotalData,
            'company' => $this->company,
            'brandName' => $this->brandName,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'dateRange' => $this->date,
            'currencySymbol' => $this->currencySymbol,
        ]);
    }
}
