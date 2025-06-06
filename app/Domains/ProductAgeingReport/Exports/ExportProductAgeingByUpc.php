<?php

declare(strict_types=1);

namespace App\Domains\ProductAgeingReport\Exports;

use App\Domains\ExportRecord\Interfaces\ExportRecordClassInterface;
use App\Domains\ProductAgeingReport\ProductAgeingQueries;
use App\Domains\ProductAgeingReport\Services\ProductAgeingReportService;
use App\Models\ExportRecord;
use Illuminate\Support\Collection;

class ExportProductAgeingByUpc implements ExportRecordClassInterface
{
    public function export(int $exportRecordId, int $companyId): void
    {
    }

    public function fetch(ExportRecord $exportRecord, int $insertedRows, int $nextRecords): Collection
    {
        $productAgeingQueries = resolve(ProductAgeingQueries::class);

        $productAgeingReportService = resolve(ProductAgeingReportService::class);

        $productAgeings = $productAgeingQueries->exportProductAgeingRecordsForUpc(
            $exportRecord->filters ?? [],
            $exportRecord->company_id,
            $insertedRows,
            $nextRecords
        );
        $headerColumns = collect($exportRecord->headers);

        return $productAgeingReportService->preparedDataByUpc($productAgeings, $headerColumns);
    }
}
