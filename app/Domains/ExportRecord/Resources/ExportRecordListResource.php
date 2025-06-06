<?php

declare(strict_types=1);

namespace App\Domains\ExportRecord\Resources;

use App\CommonFunctions;
use App\Domains\ExportRecord\Enums\ExportRecordStatuses;
use App\Domains\ExportRecord\Enums\ExportRecordTypes;
use App\Models\ExportRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExportRecordListResource extends JsonResource
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
            'export_record_type' => ExportRecordTypes::getFormattedCaseName($exportRecord->type_id),
            'status' => ExportRecordStatuses::getFormattedCaseName($exportRecord->status),
            'export_file_url' => $exportRecord->getDiskBasedFirstMediaUrl('export_file'),
            'created_by_type' => $exportRecord->created_by_type ? CommonFunctions::stringTitleLowerCase(
                $exportRecord->created_by_type
            ) : '',
        ];
    }
}
