<?php

declare(strict_types=1);

namespace App\Domains\ProductAgeingReport\Exports;

use App\Domains\Common\Services\ExportService;
use App\Domains\ProductAgeingReport\Services\ProductAgeingReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductAgeingReportByUpcExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $productAgeings,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        $productAgeingReportService = resolve(ProductAgeingReportService::class);

        return $productAgeingReportService->preparedDataByUpc($this->productAgeings, $this->filteredColumns);
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
