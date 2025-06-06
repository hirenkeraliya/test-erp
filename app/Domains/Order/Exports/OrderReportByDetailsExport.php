<?php

declare(strict_types=1);

namespace App\Domains\Order\Exports;

use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class OrderReportByDetailsExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $orderData,
        protected Location $location,
        protected array $dateRange,
        protected Company $company,
    ) {
    }

    public function view(): View
    {
        return view('prints.order_by_details_for_export', [
            'ordersData' => $this->orderData,
            'location' => $this->location,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
        ]);
    }
}
