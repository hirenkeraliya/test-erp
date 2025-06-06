<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustment\Resources;

use App\Domains\ImportRecord\Enums\Status;
use App\Domains\StockAdjustment\Enums\StockAdjustmentTypes;
use App\Models\ImportRecord;
use App\Models\StockAdjustment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var StockAdjustment $stockAdjustment */
        $stockAdjustment = $this;

        /** @var Carbon|string $adjustmentDate */
        $adjustmentDate = 'N/A';

        if ($stockAdjustment->adjustment_date) {
            /** @var Carbon $adjustmentDateFormat */
            $adjustmentDateFormat = Carbon::createFromFormat('Y-m-d', $stockAdjustment->adjustment_date);
            $adjustmentDate = $adjustmentDateFormat->format('d-m-Y');
        }

        /** @var ?ImportRecord $importRecord */
        $importRecord = $this->whenLoaded('importRecord');

        return [
            'id' => $stockAdjustment->id,
            'adjustment_date' => $adjustmentDate,
            'type' => StockAdjustmentTypes::tryFrom($stockAdjustment->type_id)?->name,
            'reason' => $stockAdjustment->reason,
            'approved_by' => $stockAdjustment->employee?->first_name . ' ' . $stockAdjustment->employee?->last_name,
            'upload_status' => $importRecord instanceof ImportRecord ? Status::getFormattedCaseName(
                $importRecord->status
            ) : 'N/A',
            'import_record_id' => $importRecord->id ?? null,
            'total_records' => $importRecord instanceof ImportRecord ? $importRecord->records_in_file : null,
            'total_records_imported' => $importRecord instanceof ImportRecord ? $importRecord->records_imported : null,
            'total_records_failed' => $importRecord instanceof ImportRecord ? $importRecord->records_failed : null,
            'failed_records_file_url' => $importRecord instanceof ImportRecord ? $importRecord->getDiskBasedFirstMediaUrl(
                'failed_rows_file'
            ) : null,
            'upload_file_url' => $importRecord instanceof ImportRecord ? $importRecord->getDiskBasedFirstMediaUrl(
                'upload_file'
            ) : null,
        ];
    }
}
