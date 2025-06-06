<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderReceive\Resource;

use App\Domains\ExternalPurchaseOrderReceive\Enums\Statuses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalPurchaseOrderReceiveListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $externalPurchaseOrderReceive = $this->resource;

        /** @var Carbon $createdAtFormat */
        $createdAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $externalPurchaseOrderReceive->received_date);
        $date = $createdAtFormat->format('d-m-y H:i:s A');

        return [
            'id' => $externalPurchaseOrderReceive->id,
            'received_date' => $date,
            'notes' => $externalPurchaseOrderReceive->notes,
            'is_grn' => $externalPurchaseOrderReceive->is_grn,
            'status_id' => $externalPurchaseOrderReceive->status,
            'status' => Statuses::getFormattedCaseName($externalPurchaseOrderReceive->status),
        ];
    }
}
