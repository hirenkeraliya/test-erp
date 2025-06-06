<?php

declare(strict_types=1);

namespace App\Domains\Product\Exports;

use App\Domains\ExportRecord\Interfaces\ExportRecordClassInterface;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductService;
use App\Models\ExportRecord;
use Illuminate\Support\Collection;

class ExportProduct implements ExportRecordClassInterface
{
    public function export(int $exportRecordId, int $companyId): void
    {
    }

    public function fetch(ExportRecord $exportRecord, int $insertedRows, int $nextRecords): Collection
    {
        $productQueries = resolve(ProductQueries::class);

        $productService = resolve(ProductService::class);
        $filters = $exportRecord->filters ?? [];
        unset($filters['all_permission_lists']);
        $products = $productQueries->exportProductRecords(
            $filters,
            $exportRecord->company_id,
            $insertedRows,
            $nextRecords
        );

        $headerColumns = collect($exportRecord->headers);

        return $productService->preparedProductRecords(
            $products,
            $headerColumns,
            $exportRecord->filters['all_permission_lists'] ?? [],
        );
    }
}
