<?php

declare(strict_types=1);

namespace App\Domains\ProductAgeingReport\Exports;

use App\Domains\ProductAgeingReport\Services\ProductAgeingReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductAgeingReportByMonthAndYearExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $products,
        protected int $ageOfProductType,
        protected Collection $filteredColumns,
    ) {
    }

    public function collection(): Collection
    {
        $productAgeingReportService = resolve(ProductAgeingReportService::class);

        return $productAgeingReportService->preparedDataByMonthAndYear(
            $this->products,
            $this->ageOfProductType,
            $this->filteredColumns
        );
    }

    public function headings(): array
    {
        $productAgeingReportService = resolve(ProductAgeingReportService::class);

        return $productAgeingReportService->getProductAgeingByMonthAndYearHeadings($this->filteredColumns);
    }
}
