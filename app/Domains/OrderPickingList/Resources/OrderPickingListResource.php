<?php

namespace App\Domains\OrderPickingList\Resources;

use App\Domains\Order\Enums\OrderPickingStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderPickingListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $orderPickingList = $this->resource;

        return [
            'id' => $orderPickingList->id,
            'number' => $orderPickingList->number,
            'status' => OrderPickingStatus::getFormattedCaseName($orderPickingList->status),
            'status_id' => $orderPickingList->status,
        ];
    }
}
