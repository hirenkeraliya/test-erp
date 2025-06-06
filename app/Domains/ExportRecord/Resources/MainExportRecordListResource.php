<?php

declare(strict_types=1);

namespace App\Domains\ExportRecord\Resources;

use App\CommonFunctions;
use App\Domains\ExportRecord\Enums\ExportRecordStatuses;
use App\Domains\ExportRecord\Enums\ExportRecordTypes;
use App\Models\ExportRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MainExportRecordListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var ExportRecord $exportRecord */
        $exportRecord = $this;

        return [
            'id' => $exportRecord->id,
            'export_type' => ExportRecordTypes::getFormattedCaseName($exportRecord->type_id),
            'status' => ExportRecordStatuses::getFormattedCaseName($exportRecord->status),
            'total_records' => $exportRecord->total_records,
            'total_exported_records' => $exportRecord->total_exported_records,
            'file_exported_at' => $exportRecord->created_at ? $exportRecord->created_at->format('d-m-Y h:i:s A') : null,
            'export_file_url' => $exportRecord->getDiskBasedFirstMediaUrl('export_file'),
            'created_by_type' => CommonFunctions::stringTitleLowerCase($exportRecord->created_by_type),
            'staff_id' => $exportRecord->createdBy->employee->staff_id,
            'module_type' => $exportRecord->module_type ? CommonFunctions::stringTitleLowerCase(
                $exportRecord->module_type
            ) : 'N/A',
        ];
    }
}
