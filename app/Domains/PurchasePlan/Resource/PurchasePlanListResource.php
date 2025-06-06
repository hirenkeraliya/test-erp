<?php

declare(strict_types=1);

namespace App\Domains\PurchasePlan\Resource;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\PurchasePlan\Enums\Statuses;
use App\Models\Location;
use App\Models\PurchasePlan;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchasePlanListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $purchasePlan = $this->resource;

        $locationType = LocationTypes::getFormattedCaseName($purchasePlan->location->type_id);

        /** @var Carbon $createdAtFormat */
        $createdAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $purchasePlan->created_at);
        $createdAt = $createdAtFormat->format('d-m-y h:i:s A');

        /** @var Vendor $vendor */
        $vendor = $purchasePlan->vendor;

        return [
            'created_at' => $createdAt,
            'id' => $purchasePlan->id,
            'from' => $vendor->name,
            'to' => $this->getToLocation($purchasePlan),
            'status' => Statuses::getFormattedCaseName($purchasePlan->status),
            'status_id' => $purchasePlan->status,
            'reference_number' => $purchasePlan->reference_number,
            'plan_number' => $purchasePlan->plan_number,
            'remarks' => $purchasePlan->remarks,
            'location_id' => $purchasePlan->location_id,
            'location_type' => $locationType,
            'total_amount' => $purchasePlan->total_amount ?? 0,
        ];
    }

    public function getToLocation(PurchasePlan $purchasePlan): string
    {
        /** @var Location $location */
        $location = $purchasePlan->location;

        $locationType = LocationTypes::getFormattedCaseName($location->type_id);

        return $location->name . ' (' . $locationType . ')';
    }
}
