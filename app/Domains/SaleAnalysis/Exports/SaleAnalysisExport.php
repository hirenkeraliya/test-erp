<?php

declare(strict_types=1);

namespace App\Domains\SaleAnalysis\Exports;

use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SaleAnalysisExport implements FromView, ShouldAutoSize
{
    public function __construct(
        protected array $saleAnalysis,
        protected array $saleAnalysisTotals,
        protected Company $company,
        protected ?Location $location,
        protected string $dateRange,
        protected array $columns,
        protected array $filterHeaderData,
    ) {
    }

    public function view(): View
    {
        return view('prints.sale_analysis', [
            'saleAnalysis' => $this->saleAnalysis,
            'saleAnalysisTotals' => $this->saleAnalysisTotals,
            'dateRange' => $this->dateRange,
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'location' => $this->location,
            'company' => $this->company,
            'columns' => $this->columns,
            'filter_header_data' => $this->filterHeaderData,
        ]);
    }
}
