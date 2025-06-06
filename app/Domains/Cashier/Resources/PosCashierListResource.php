<?php

declare(strict_types=1);

namespace App\Domains\Cashier\Resources;

use App\Models\Cashier;
use App\Models\CashierGroup;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosCashierListResource extends JsonResource
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
        $employee = $cashier->employee;

        /** @var CashierGroup $cashierGroup */
        $cashierGroup = $cashier->cashierGroup;

        return [
            'id' => $cashier->id,
            'employee_id' => $cashier->employee_id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'staff_id' => $employee->staff_id,
            'username' => $cashier->username,
            'pin' => $cashier->pin,
            'group_name' => $cashierGroup->name,
            'price_override_limit_percentage_for_item' => (float) $cashierGroup->price_override_limit_percentage_for_item,
            'price_override_type' => $cashierGroup->price_override_type,
            'price_override_limit_percentage_for_cart' => (float) $cashierGroup->price_override_limit_percentage_for_cart,
        ];
    }
}
