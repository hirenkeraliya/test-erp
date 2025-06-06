<?php

namespace App\Domains\Batch\Exports;

use App\Domains\Batch\Resources\BatchExpiryReportResource;
use App\Domains\Common\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BatchExpiryExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $batches,
        protected Collection $filteredColumns
    ) {
    }

    public function collection()
    {
        $data = BatchExpiryReportResource::collection($this->batches);
        /** @var array $resourceData */
        $resourceData = $data->toArray(new Request());
        $records = collect($resourceData);

        $batchExpiryReportData = $records->transform(function (array $record): array {
            $categories = $record['categories']->pluck('name')->toArray();
            $record['categories'] = empty($categories)
            ? 'N/A'
            : str_replace(',', ' > ', implode(',', $categories));

            unset($record['is_expired']);
            unset($record['is_expired_soon']);

            return $record;
        });

        $exportService = resolve(ExportService::class);

        return $exportService->exportDataMapping($batchExpiryReportData, $this->filteredColumns);
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
