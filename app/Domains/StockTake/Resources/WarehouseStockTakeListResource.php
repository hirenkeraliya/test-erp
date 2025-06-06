<?php

declare(strict_types=1);

namespace App\Domains\StockTake\Resources;

use App\Domains\ImportRecord\Enums\Status;
use App\Models\Employee;
use App\Models\ImportRecord;
use App\Models\Location;
use App\Models\StockTake;
use App\Models\WarehouseManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseStockTakeListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var StockTake $stockTake */
        $stockTake = $this;

        /** @var WarehouseManager $requestedWarehouseManager */
        $requestedWarehouseManager = $stockTake->requestedBy;

        /** @var ?WarehouseManager $submittedWarehouseManager */
        $submittedWarehouseManager = $stockTake->submittedBy;

        /** @var Employee $requestedEmployee */
        $requestedEmployee = $requestedWarehouseManager->employee;

        /** @var ?Employee $submittedEmployee */
        $submittedEmployee = $submittedWarehouseManager instanceof WarehouseManager ? $submittedWarehouseManager->employee : null;

        /** @var Location $location */
        $location = $stockTake->location;

        /** @var Carbon|string $submittedAt */
        $submittedAt = 'N/A';

        /** @var ?ImportRecord $importRecord */
        $importRecord = $stockTake->importRecord;

        if ($stockTake->submitted_at) {
            /** @var Carbon $submittedAtFormat */
            $submittedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $stockTake->submitted_at);
            $submittedAt = $submittedAtFormat->format('d-m-Y h:i:s A');
        }

        /** @var Carbon $stockRecordDateFormat */
        $stockRecordDateFormat = Carbon::createFromFormat('Y-m-d', $stockTake->stock_record_date);
        $stockRecordDate = $stockRecordDateFormat->format('d-m-Y');

        return [
            'id' => $stockTake->id,
            'stock_record_date' => $stockRecordDate,
            'compare_stock_date' => $stockTake->getStockCompareDate(),
            'requested_warehouse_manager' => $requestedEmployee->getFullName(),
            'location' => $location->name,
            'submitted_warehouse_manager' => $submittedEmployee instanceof Employee ? $submittedEmployee->getFullName() : 'N/A',
            'submitted_at' => $submittedAt,
            'upload_status' => $importRecord instanceof ImportRecord ? Status::getFormattedCaseName(
                $importRecord->status
            ) : 'N/A',
            'import_record_id' => $importRecord->id ?? null,
            'total_records' => $importRecord instanceof ImportRecord ? $importRecord->records_in_file : null,
            'total_records_imported' => $importRecord instanceof ImportRecord ? $importRecord->records_imported : null,
            'total_records_failed' => $importRecord instanceof ImportRecord ? $importRecord->records_failed : null,
            'is_uploaded_products' => $stockTake->is_uploaded_products,
        ];
    }
}
