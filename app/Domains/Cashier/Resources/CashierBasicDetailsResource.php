<?php

declare(strict_types=1);

namespace App\Domains\Cashier\Resources;

use App\Models\Cashier;
use App\Models\CashierGroup;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashierBasicDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Cashier $cashier */
        $cashier = $this;
        /** @var Employee $employee */
        $employee = $cashier->refresh()->getEmployee();
        /** @var CashierGroup $cashierGroup */
        $cashierGroup = $cashier->cashierGroup;

        return [
            'id' => $cashier->id,
            'username' => $cashier->username,
            'last_login_at' => $cashier->last_login_at,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'mobile_number' => $employee->mobile_number,
            'address_line_1' => $employee->address_line_1,
            'address_line_2' => $employee->address_line_2,
            'city' => $employee->city,
            'area_code' => $employee->area_code,
            'date_of_joining' => $employee->date_of_joining,
            'staff_id' => $employee->staff_id,
            'permissions' => $cashierGroup->permissions->pluck('permission_id'),
            'price_override_limit_percentage_for_item' => (float) $cashierGroup->price_override_limit_percentage_for_item,
            'price_override_type' => $cashierGroup->price_override_type,
            'price_override_limit_percentage_for_cart' => (float) $cashierGroup->price_override_limit_percentage_for_cart,
        ];
    }
}
