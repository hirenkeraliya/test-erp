<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Resources;

use App\CommonFunctions;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\Services\StockTransferService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class StockTransferListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $filterData = [
            'location_id' => $request->get('location_id'),
        ];

        $stockTransfer = $this->resource;

        /** @var Collection $stockTransferItems */
        $stockTransferItems = $stockTransfer->getItems();

        $sourceLocationType = LocationTypes::getFormattedCaseName($stockTransfer->sourceLocation->type_id);
        $destinationLocationType = LocationTypes::getFormattedCaseName($stockTransfer->destinationLocation->type_id);
        $transitLocationType = $stockTransfer->transitLocation ? LocationTypes::getFormattedCaseName(
            $stockTransfer->transitLocation->type_id
        ) : null;
        $openedAt = null;
        $approvedAt = null;
        $shippedAt = null;
        $receivedAt = null;
        $discrepancyAt = null;
        $closedAt = null;
        $cancelledAt = null;
        $rejectedAt = null;
        $transferDate = null;
        $requireDate = null;

        $dateTimeStringFormat = 'd-m-Y h:i:s A';
        $dateTimeParseFormat = 'Y-m-d H:i:s';

        $dateParseFormat = 'Y-m-d';
        $dateStringFormat = 'd-m-Y';

        if ($stockTransfer->opened_at) {
            /** @var Carbon $openedFormat */
            $openedFormat = Carbon::createFromFormat($dateTimeParseFormat, $stockTransfer->opened_at);
            $openedAt = $openedFormat->format($dateTimeStringFormat);
        }

        if ($stockTransfer->transfer_date) {
            /** @var Carbon $transferDateFormat */
            $transferDateFormat = Carbon::createFromFormat($dateParseFormat, $stockTransfer->transfer_date);
            $transferDate = $transferDateFormat->format($dateStringFormat);
        }

        if ($stockTransfer->require_date) {
            /** @var Carbon $requireDateFormat */
            $requireDateFormat = Carbon::createFromFormat($dateParseFormat, $stockTransfer->require_date);
            $requireDate = $requireDateFormat->format($dateStringFormat);
        }

        if ($stockTransfer->approved_at) {
            /** @var Carbon $approvedFormat */
            $approvedFormat = Carbon::createFromFormat($dateTimeParseFormat, $stockTransfer->approved_at);
            $approvedAt = $approvedFormat->format($dateTimeStringFormat);
        }

        if ($stockTransfer->shipped_at) {
            /** @var Carbon $shippedFormat */
            $shippedFormat = Carbon::createFromFormat($dateTimeParseFormat, $stockTransfer->shipped_at);
            $shippedAt = $shippedFormat->format($dateTimeStringFormat);
        }

        if ($stockTransfer->received_at) {
            /** @var Carbon $receivedFormat */
            $receivedFormat = Carbon::createFromFormat($dateTimeParseFormat, $stockTransfer->received_at);
            $receivedAt = $receivedFormat->format($dateTimeStringFormat);
        }

        if ($stockTransfer->discrepancy_at) {
            /** @var Carbon $discrepancyFormat */
            $discrepancyFormat = Carbon::createFromFormat($dateTimeParseFormat, $stockTransfer->discrepancy_at);
            $discrepancyAt = $discrepancyFormat->format($dateTimeStringFormat);
        }

        if ($stockTransfer->closed_at) {
            /** @var Carbon $closedFormat */
            $closedFormat = Carbon::createFromFormat($dateTimeParseFormat, $stockTransfer->closed_at);
            $closedAt = $closedFormat->format($dateTimeStringFormat);
        }

        if ($stockTransfer->cancelled_at) {
            /** @var Carbon $cancelledFormat */
            $cancelledFormat = Carbon::createFromFormat($dateTimeParseFormat, $stockTransfer->cancelled_at);
            $cancelledAt = $cancelledFormat->format($dateTimeStringFormat);
        }

        if ($stockTransfer->rejected_at) {
            /** @var Carbon $rejectedFormat */
            $rejectedFormat = Carbon::createFromFormat($dateTimeParseFormat, $stockTransfer->rejected_at);
            $rejectedAt = $rejectedFormat->format($dateTimeStringFormat);
        }

        /** @var Carbon $createdAt */
        $createdAt = $stockTransfer->created_at;

        $stockTransferService = resolve(StockTransferService::class);

        $travelingAverageLeadDays = [];

        if ($stockTransferService->statusIsShippedOrTransit($stockTransfer->status)) {
            $travelingAverageLeadDays = $stockTransferService->preparedShipmentProgressBar(
                $stockTransfer->average_days,
                $dateTimeParseFormat,
                $stockTransfer->shipped_at
            );
        }

        return [
            'created_at' => $createdAt->format($dateTimeStringFormat),
            'traveling_average_lead_days' => $travelingAverageLeadDays,
            'transfer_type' => StockTransferTypes::getCaseName($stockTransfer->transfer_type),
            'transfer_type_details' => $stockTransferService->getTransferType($stockTransfer, $filterData),
            'id' => $stockTransfer->id,
            'from' => $stockTransfer->sourceLocation->name . ' (' . $sourceLocationType . ')',
            'to' => $stockTransfer->destinationLocation->name . ' (' . $destinationLocationType . ')',
            'transit_location_id' => $stockTransfer->transit_location_id,
            'transit_location_name' => $stockTransfer->transitLocation ? $stockTransfer->transitLocation->name . ' (' . $transitLocationType . ')' : null,
            'status' => StatusTypes::getFormattedCaseName($stockTransfer->getStatus()),
            'status_id' => $stockTransfer->getStatus(),
            'reference_number' => $stockTransfer->reference_number,
            'transfer_out_number' => $stockTransfer->transfer_out_number,
            'transfer_in_number' => $stockTransfer->transfer_in_number,
            'request_order_number' => $stockTransfer->request_order_number,
            'transfer_order_number' => $stockTransfer->transfer_order_number,
            'source_id' => $stockTransfer->source_location_id,
            'source_type' => $sourceLocationType,
            'destination_id' => $stockTransfer->destination_location_id,
            'destination_type' => $destinationLocationType,
            'created_by_location_id' => $stockTransfer->created_by_location_id,
            'status_times' => array_filter([
                'Transfer Date' => $transferDate ?? null,
                'Require Date' => $requireDate,
                'divider' => 'divider',
                'Opened at' => $openedAt,
                'Approved at' => $approvedAt,
                'Shipped at' => $shippedAt,
                'Received at' => $receivedAt,
                'Discrepancy at' => $discrepancyAt,
                'Closed at' => $closedAt,
                'Cancelled at' => $cancelledAt,
                'Rejected at' => $rejectedAt,
            ]),
            'order_numbers' => array_filter([
                'transfer_order_number' => $stockTransfer->transfer_order_number,
                'request_order_number' => $stockTransfer->request_order_number,
                'transfer_out_number' => $stockTransfer->transfer_out_number,
                'transfer_in_number' => $stockTransfer->transfer_in_number,
            ]),
            'totals' => array_filter([
                'requested' => CommonFunctions::numberFormat($stockTransferItems->pluck('quantity')->sum()),
                'received' => CommonFunctions::numberFormat($stockTransferItems->pluck('received_quantity')->sum()),
            ]),
        ];
    }
}
