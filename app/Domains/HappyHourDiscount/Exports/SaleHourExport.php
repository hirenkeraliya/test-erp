<?php

declare(strict_types=1);

namespace App\Domains\HappyHourDiscount\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SaleHourExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $saleHours,
        protected Company $company,
        protected array $dateRange,
        protected array $columns,
        protected string $location,
        protected string $currencySymbol,
    ) {
    }

    public function view(): View
    {
        return view('prints.sale_hour', [
            'saleHours' => $this->saleHours,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
            'locationName' => $this->location,
            'currencySymbol' => $this->currencySymbol,
        ]);
    }
}
