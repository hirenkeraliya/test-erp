<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrder\Resource;

use App\Domains\ExternalPurchaseOrder\Enums\Statuses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalPurchaseOrderListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $externalPurchaseOrder = $this->resource;

        /** @var Carbon $createdAtFormat */
        $createdAtFormat = Carbon::createFromFormat('Y-m-d', $externalPurchaseOrder->date);
        $date = $createdAtFormat->format('d-m-y h:i:s A');

        return [
            'id' => $externalPurchaseOrder->id,
            'date' => $date,
            'order_number' => $externalPurchaseOrder->order_number,
            'notes' => $externalPurchaseOrder->notes,
            'status' => Statuses::getFormattedCaseName($externalPurchaseOrder->status),
            'status_id' => $externalPurchaseOrder->status,
            'total_amount' => $externalPurchaseOrder->total_amount,
        ];
    }
}
