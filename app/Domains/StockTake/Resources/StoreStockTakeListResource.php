<?php

declare(strict_types=1);

namespace App\Domains\StockTake\Resources;

use App\Domains\ImportRecord\Enums\Status;
use App\Models\Employee;
use App\Models\ImportRecord;
use App\Models\Location;
use App\Models\StockTake;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreStockTakeListResource extends JsonResource
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

        /** @var StoreManager $requestedStoreManager */
        $requestedStoreManager = $stockTake->requestedBy;

        /** @var ?StoreManager $submittedStoreManager */
        $submittedStoreManager = $stockTake->submittedBy;

        /** @var Employee $requestedEmployee */
        $requestedEmployee = $requestedStoreManager->employee;

        /** @var ?Employee $submittedEmployee */
        $submittedEmployee = $submittedStoreManager instanceof StoreManager ? $submittedStoreManager->employee : null;

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
            'requested_store_manager' => $requestedEmployee->getFullName(),
            'location' => $location->name,
            'submitted_store_manager' => $submittedEmployee instanceof Employee ? $submittedEmployee->getFullName() : 'N/A',
            'submitted_at' => $submittedAt,
            'compare_stock_date' => $stockTake->getStockCompareDate(),
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
