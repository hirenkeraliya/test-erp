<?php

declare(strict_types=1);

namespace App\Domains\StockTake\Exports;

use App\Models\Employee;
use App\Models\Location;
use App\Models\StockTake;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockTakeExportForStoreManager implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $stockTakes
    ) {
    }

    public function collection(): Collection
    {
        return $this->stockTakes->map(function (StockTake $stockTake): array {
            /** @var StoreManager $requestedManager */
            $requestedManager = $stockTake->requestedBy;
            /** @var StoreManager|null $submittedManager */
            $submittedManager = $stockTake->submittedBy;
            /** @var Employee $requestedEmployee */
            $requestedEmployee = $requestedManager->employee;
            /** @var ?Employee $submittedEmployee */
            $submittedEmployee = $submittedManager instanceof StoreManager ? $submittedManager->employee : null;
            /** @var Location $location */
            $location = $stockTake->location;
            /** @var Carbon|string $submittedAt */
            $submittedAt = 'N/A';
            if ($stockTake->submitted_at) {
                /** @var Carbon $submittedAtFormat */
                $submittedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $stockTake->submitted_at);
                $submittedAt = $submittedAtFormat->format('d-m-Y h:i:s A');
            }

            return [
                'stock_record-date' => $stockTake->stock_record_date,
                'requested_store_manager' => $requestedEmployee->getFullName(),
                'location' => $location->name,
                'submitted_store_manager' => $submittedEmployee instanceof Employee ? $submittedEmployee->getFullName() : 'N/A',
                'submitted_at' => $submittedAt,
            ];
        });
    }

    public function headings(): array
    {
        return ['Stock Record Date', 'Requested Store Manager', 'Location', 'Submitted Store Manager', 'Submitted At'];
    }
}
