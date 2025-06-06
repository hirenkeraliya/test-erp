<?php

declare(strict_types=1);

namespace App\Domains\Cashier\Resources;

use App\Models\Cashier;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreManagerAppCashierListResource extends JsonResource
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

        return [
            'id' => $cashier->id,
            'employee_id' => $cashier->employee_id,
            'name' => $employee->getFullName(),
            'staff_id' => $employee->staff_id,
        ];
    }
}
