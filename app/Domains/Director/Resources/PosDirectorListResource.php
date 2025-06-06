<?php

declare(strict_types=1);

namespace App\Domains\Director\Resources;

use App\Models\Director;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosDirectorListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Director $director */
        $director = $this;

        /** @var Employee $employee */
        $employee = $director->employee;

        return [
            'id' => $director->id,
            'employee_id' => $director->employee_id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'staff_id' => $employee->staff_id,
            'email' => $employee->email,
            'passcode' => $director->passcode,
            'price_override_limit_percentage_for_item' => (float) $director->price_override_limit_percentage_for_item,
            'price_override_type' => $director->price_override_type,
            'price_override_limit_percentage_for_cart' => (float) $director->price_override_limit_percentage_for_cart,
        ];
    }
}
