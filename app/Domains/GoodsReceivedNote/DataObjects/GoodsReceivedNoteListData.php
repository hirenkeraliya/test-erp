<?php

declare(strict_types=1);

namespace App\Domains\GoodsReceivedNote\DataObjects;

use App\Domains\ImportRecord\Enums\Status;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\ImportRecord;
use App\Models\Location;
use App\Models\Vendor;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

class GoodsReceivedNoteListData extends Data
{
    public string $location_name;

    public function __construct(
        public int $id,
        public string $grn_reference,
        public ?string $purchase_order_reference,
        public ?string $delivery_order_reference,
        public ?string $notes,
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'd-m-Y h:i:s A')]
        public Carbon $created_at,
        public int $location_id,
        public Location $location,
        public ?Vendor $vendor,
        public ?ImportRecord $import_record,
        public ?string $upload_status,
        public ?string $upload_file_url,
        public ?string $failed_records_file_url,
        public ?string $cancelled_at,
        public ?string $remarks,
    ) {
        $this->location_name = $this->location->name . ' (' . LocationTypes::getFormattedCaseName(
            $this->location->type_id
        ) . ')';
        $this->upload_status = $this->import_record instanceof ImportRecord ? Status::getFormattedCaseName(
            $this->import_record->status
        ) : 'N/A';
        $this->failed_records_file_url = $this->import_record instanceof ImportRecord ? $this->import_record->getDiskBasedFirstMediaUrl(
            'failed_rows_file'
        ) : null;
        $this->upload_file_url = $this->import_record instanceof ImportRecord ? $this->import_record->getDiskBasedFirstMediaUrl(
            'upload_file'
        ) : null;
    }
}
