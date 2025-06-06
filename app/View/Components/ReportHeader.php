<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Company;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ReportHeader extends Component
{
    public function __construct(
        public Company $company,
        public string $reportName,
        public string $date,
        public string $reportType,
        public ?string $filterBy = null,
        public ?array $dateRange = []
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.report-header');
    }
}
