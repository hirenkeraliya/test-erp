<?php

declare(strict_types=1);

namespace App\Domains\Promoter\Resources;

use App\Models\Employee;
use App\Models\Promoter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosPromoterListResource extends JsonResource
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
            'employee_id' => $promoter->employee_id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'status' => (int) $employee->status,
            'email' => $employee->email,
            'staff_id' => $employee->staff_id,
        ];
    }
}
