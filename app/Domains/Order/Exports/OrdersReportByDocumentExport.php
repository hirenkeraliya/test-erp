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

class OrdersReportByDocumentExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected Collection $orderData,
        protected Location $location,
        protected array $dateRange,
        protected Company $company,
        protected array $columns,
    ) {
    }

    public function view(): View
    {
        return view('prints.order_by_document', [
            'ordersData' => $this->orderData,
            'location' => $this->location,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
        ]);
    }
}
