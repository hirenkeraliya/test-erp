<?php

declare(strict_types=1);

namespace App\Domains\StockTake\Exports;

use App\Domains\Common\Services\ExportService;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StockTake;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockTakeExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $stockTakes,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->stockTakes->map(function (StockTake $stockTake): array {
            /** @var StoreManager|WarehouseManager $requestedManager */
            $requestedManager = $stockTake->requestedBy;
            /** @var StoreManager|WarehouseManager|null $submittedManager */
            $submittedManager = $stockTake->submittedBy;
            /** @var Employee $requestedEmployee */
            $requestedEmployee = $requestedManager->employee;
            /** @var ?Employee $submittedEmployee */
            $submittedEmployee = null !== $submittedManager ? $submittedManager->employee : null;
            /** @var Location $location */
            $location = $stockTake->location;
            /** @var Carbon|string $submittedAt */
            $submittedAt = 'N/A';
            if ($stockTake->submitted_at) {
                /** @var Carbon $submittedAtFormat */
                $submittedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $stockTake->submitted_at);
                $submittedAt = $submittedAtFormat->format('d-m-Y h:i:s A');
            }

            $stockTakeReportData = [
                'requested_manager' => $requestedEmployee->getFullName(),
                'location' => $location->name,
                'submitted_manager' => $submittedEmployee instanceof Employee ? $submittedEmployee->getFullName() : 'N/A',
                'submitted_at' => $submittedAt,
                'compare_stock_date' => $stockTake->getStockCompareDate(),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($stockTakeReportData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
