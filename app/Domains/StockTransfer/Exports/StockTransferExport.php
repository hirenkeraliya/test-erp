<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Exports;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Services\StockTransferService;
use App\Models\Location;
use App\Models\StockTransfer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockTransferExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $stockTransfers,
        protected array $filterData
    ) {
    }

    public function collection(): Collection
    {
        return $this->stockTransfers->map(function (StockTransfer $stockTransfer): array {
            /** @var Location $sourceLocation */
            $sourceLocation = $stockTransfer->sourceLocation;

            /** @var Location $destinationLocation */
            $destinationLocation = $stockTransfer->destinationLocation;

            $sourceLocationType = LocationTypes::getFormattedCaseName($sourceLocation->type_id);
            $destinationLocationType = LocationTypes::getFormattedCaseName($destinationLocation->type_id);

            $transferDate = null;

            $dateTimeStringFormat = 'd-m-Y h:i:s A';

            $dateParseFormat = 'Y-m-d';
            $dateStringFormat = 'd-m-Y';

            if ($stockTransfer->transfer_date) {
                /** @var Carbon $transferDateFormat */
                $transferDateFormat = Carbon::createFromFormat($dateParseFormat, $stockTransfer->transfer_date);
                $transferDate = $transferDateFormat->format($dateStringFormat);
            }

            /** @var Carbon $createdAt */
            $createdAt = $stockTransfer->created_at;

            $stockTransferService = resolve(StockTransferService::class);

            return [
                'transfer_date' => $transferDate ?? $createdAt->format($dateTimeStringFormat),
                'transfer_type' => $stockTransferService->getTransferType($stockTransfer, $this->filterData),
                'transfer_order_number' => $stockTransfer->transfer_order_number,
                'transfer_out_number' => $stockTransfer->transfer_out_number,
                'transfer_in_number' => $stockTransfer->transfer_in_number,
                'request_order_number' => $stockTransfer->request_order_number,
                'from' => $sourceLocation->name . ' (' . $sourceLocationType . ')',
                'to' => $destinationLocation->name . ' (' . $destinationLocationType . ')',
                'status' => StatusTypes::getFormattedCaseName($stockTransfer->getStatus()),
                'reference_number' => $stockTransfer->reference_number,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Transfer Date',
            'Transfer Type',
            'Transfer Order Number',
            'Transfer Out Number',
            'Transfer In Number',
            'Request Order Number',
            'From',
            'To',
            'Status',
            'Reference Number',
        ];
    }
}
