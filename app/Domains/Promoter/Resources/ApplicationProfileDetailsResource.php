<?php

declare(strict_types=1);

namespace App\Domains\Promoter\Resources;

use App\Models\Employee;
use App\Models\Promoter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationProfileDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Promoter $promoter */
        $promoter = $this;

        /** @var Employee $employee */
        $employee = $promoter->employee;

        return [
            'id' => $promoter->id,
            'username' => $promoter->username,
            'employee_id' => $promoter->employee_id,
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
