<?php

declare(strict_types=1);

namespace App\Domains\HoldSale\Exports;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SuspendAndResumeExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $locationsHoldSales,
        protected array $dateRange,
        protected Company $company,
        protected string $filterBy,
        protected string $currencySymbol,
    ) {
    }

    public function view(): View
    {
        return view('prints.suspend_and_resume', [
            'locationHoldSales' => $this->locationsHoldSales,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'filterBy' => $this->filterBy,
            'currencySymbol' => $this->currencySymbol,
        ]);
    }
}
