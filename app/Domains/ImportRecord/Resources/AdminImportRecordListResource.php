<?php

declare(strict_types=1);

namespace App\Domains\ImportRecord\Resources;

use App\CommonFunctions;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Models\ImportRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminImportRecordListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var ImportRecord $importRecord */
        $importRecord = $this;

        return [
            'id' => $importRecord->id,
            'import_type' => ImportTypes::getFormattedCaseName($importRecord->type_id),
            'status' => Status::getFormattedCaseName($importRecord->status),
            'records_in_file' => $importRecord->records_in_file,
            'records_imported' => $importRecord->records_imported,
            'records_failed' => $importRecord->records_failed,
            'file_uploaded_at' => $importRecord->created_at ? $importRecord->created_at->format('d-m-Y h:i:s A') : null,
            'failed_records_file_url' => $importRecord->getDiskBasedFirstMediaUrl('failed_rows_file'),
            'upload_file_url' => $importRecord->getDiskBasedFirstMediaUrl('upload_file'),
            'created_by_type' => CommonFunctions::stringTitleLowerCase($importRecord->created_by_type),
            'staff_id' => $importRecord->createdBy->employee->staff_id,
            'module_type' => $importRecord->module_type ? CommonFunctions::stringTitleLowerCase(
                $importRecord->module_type
            ) : 'N/A',
        ];
    }
}
