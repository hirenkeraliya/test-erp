<?php

declare(strict_types=1);

namespace App\Domains\User\Resources;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserApplicationProfileDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $user = $this->resource;

        /** @var Employee $employee */
        $employee = $user->employee;

        return [
            'id' => $user->id,
            'username' => $user->username,
            'employee_id' => $user->employee_id,
            'staff_id' => $employee->staff_id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'mobile_number' => $employee->mobile_number,
            'home_contact' => $employee->home_contact,
            'address_line_1' => $employee->address_line_1,
            'address_line_2' => $employee->address_line_2,
            'city' => $employee->city,
            'area_code' => $employee->area_code,
            'primary_contact_name' => $employee->primary_contact_name,
            'primary_contact_phone' => $employee->primary_contact_phone,
            'photo' => $employee->getDiskBasedFirstMediaUrl('photo'),
        ];
    }
}
