<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProductStoresSalesDetailsExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $data,
        protected array $totalData,
        protected string $brandName,
        protected Company $company,
        protected string $filterType,
        protected string $locationName,
        protected string $currencySymbol,
        protected string $date,
    ) {
    }

    public function view(): View
    {
        return view('prints.product_dashboard_store_sales_details', [
            'salesData' => $this->data,
            'totalData' => $this->totalData,
            'filterType' => $this->filterType,
            'company' => $this->company,
            'brandName' => $this->brandName,
            'locationName' => $this->locationName,
            'currencySymbol' => $this->currencySymbol,
            'selectedDate' => $this->date,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
        ]);
    }
}
