<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\Resources;

use App\CommonFunctions;
use App\Domains\ImportRecord\Enums\Status;
use App\Models\DreamPrice;
use App\Models\ImportRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminDreamPriceListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var DreamPrice $dreamPrice */
        $dreamPrice = $this;

        $saleDiscount = $dreamPrice->saleDiscountDreamPrice;
        $saleItemDiscount = $dreamPrice->saleItemDiscountDreamPrice;

        /** @var Carbon $startDateFormat */
        $startDateFormat = Carbon::createFromFormat('Y-m-d', $dreamPrice->start_date);
        /** @var Carbon $endDateFormat */
        $endDateFormat = Carbon::createFromFormat('Y-m-d', $dreamPrice->end_date);
        $startDate = $startDateFormat->format('d-m-Y');
        $endDate = $endDateFormat->format('d-m-Y');

        /** @var ?ImportRecord $importRecord */
        $importRecord = $dreamPrice->importRecord;

        return [
            'id' => $dreamPrice->id,
            'name' => $dreamPrice->name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'dream_price_products_count' => $dreamPrice->dreamPriceProducts->count(),
            'upload_status' => $importRecord instanceof ImportRecord ? Status::getFormattedCaseName(
                $importRecord->status
            ) : 'N/A',
            'import_record_id' => $importRecord->id ?? null,
            'total_records' => $importRecord instanceof ImportRecord ? $importRecord->records_in_file : null,
            'total_records_imported' => $importRecord instanceof ImportRecord ? $importRecord->records_imported : null,
            'total_records_failed' => $importRecord instanceof ImportRecord ? $importRecord->records_failed : null,
            'upload_file_url' => $importRecord instanceof ImportRecord ? $importRecord->getDiskBasedFirstMediaUrl(
                'upload_file'
            ) : null,
            'status' => $dreamPrice->status,
            'total_used_counts' => ($saleDiscount->count() + $saleItemDiscount->count()),
            'total_discount_amount' => CommonFunctions::numberFormat(
                $saleDiscount->sum('amount') + $saleItemDiscount->sum('amount')
            ),
        ];
    }
}
