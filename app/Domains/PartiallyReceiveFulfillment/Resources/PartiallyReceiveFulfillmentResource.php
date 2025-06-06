<?php

declare(strict_types=1);

namespace App\Domains\PartiallyReceiveFulfillment\Resources;

use App\Domains\PartiallyReceiveFulfillment\Enums\PartiallyReceiveFulfillmentStatuses;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PartiallyReceiveFulfillmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $partiallyReceiveFulfillment = $this->resource;

        /** @var Admin|StoreManager|WarehouseManager $user */
        $user = $partiallyReceiveFulfillment->receivedByUser;

        /** @var Employee $employee */
        $employee = $user->employee;

        $user = $employee->getFullName();

        return [
            'id' => $partiallyReceiveFulfillment->id,
            'received_by_user' => $user,
            'received_by_user_type' => Str::title(
                str_replace('_', ' ', $partiallyReceiveFulfillment->received_by_user_type)
            ),
            'status' => PartiallyReceiveFulfillmentStatuses::getFormattedCaseName($partiallyReceiveFulfillment->status),
            'status_id' => $partiallyReceiveFulfillment->status,
        ];
    }
}
