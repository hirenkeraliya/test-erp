<?php

declare(strict_types=1);

namespace App\Domains\StoreDayClose\Exports;

use App\Domains\Common\Services\ExportService;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StoreDayClose;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StoreDayCloseExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $storeDayCloses,
        protected Collection $filteredColumns
    ) {
    }

    public function collection(): Collection
    {
        return $this->storeDayCloses->map(function (StoreDayClose $storeDayClose): array {
            /** @var Location $location */
            $location = $storeDayClose->location;
            /** @var ?StoreManager $storeManager */
            $storeManager = $storeDayClose->storeManager;
            /** @var ?Employee $employee */
            $employee = $storeManager instanceof StoreManager ? $storeManager->employee : null;
            /** @var Carbon $openedAtFormat */
            $openedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $storeDayClose->opened_at);
            $openedAt = $openedAtFormat->format('d-m-Y h:i:s A');
            /** @var Carbon $closedAtFormat */
            $closedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $storeDayClose->closed_at);
            $closedAt = $closedAtFormat->format('d-m-Y h:i:s A');

            $storeDayCloseData = [
                'id' => $storeDayClose->id,
                'location' => $location->name,
                'store_manager' => $employee instanceof Employee ? $employee->getFullName() : 'System Generated',
                'opened_at' => $openedAt,
                'closed_at' => $closedAt,
                'sales_collection_amount' => $storeDayClose->sales_collection_amount,
                'orders_collection_amount' => $storeDayClose->orders_collection_amount,
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($storeDayCloseData, $this->filteredColumns);
        });
    }

    public function headings(): array
    {
        $exportService = resolve(ExportService::class);

        return $exportService->getHeadings($this->filteredColumns);
    }
}
