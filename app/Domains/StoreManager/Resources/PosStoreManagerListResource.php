<?php

declare(strict_types=1);

namespace App\Domains\StoreManager\Resources;

use App\Models\Employee;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosStoreManagerListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $this;

        /** @var Employee $employee */
        $employee = $storeManager->employee;

        return [
            'id' => $storeManager->id,
            'employee_id' => $storeManager->employee_id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'staff_id' => $employee->staff_id,
            'email' => $employee->email,
            'price_override_limit_percentage_for_item' => (float) $storeManager->price_override_limit_percentage_for_item,
            'price_override_type' => $storeManager->price_override_type,
            'price_override_limit_percentage_for_cart' => (float) $storeManager->price_override_limit_percentage_for_cart,
            'passcode' => $storeManager->passcode,
            'stores' => implode(',', $storeManager->locations->pluck('name')->toArray()),
            'locations' => implode(',', $storeManager->locations->pluck('name')->toArray()),
            'brands' => implode(',', $storeManager->brands->pluck('name')->toArray()),
            'store_ids' => $storeManager->locations->pluck('id')->toArray(),
            'location_ids' => $storeManager->locations->pluck('id')->toArray(),
            'brand_ids' => $storeManager->brands->pluck('id')->toArray(),
        ];
    }
}
