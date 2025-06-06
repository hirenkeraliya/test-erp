<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommissionUpdate\Exports;

use App\Domains\Sale\Enums\PromoterCommissionReportTypes;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PromoterCommissionUpdateExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $promoterCommissions,
        protected array $columns,
        protected int $reportType,
        protected string $dateRange,
        protected Company $company,
        protected string $filterBy,
    ) {
    }

    public function view(): View
    {
        return view('prints.promoter_commission', [
            'promoterCommissionSales' => $this->promoterCommissions,
            'reportType' => PromoterCommissionReportTypes::getFormattedCaseName($this->reportType),
            'filterBy' => $this->filterBy,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $this->company,
            'columns' => $this->columns,
        ]);
    }
}
