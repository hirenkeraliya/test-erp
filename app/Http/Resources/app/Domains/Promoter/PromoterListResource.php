<?php

declare(strict_types=1);

namespace App\Http\Resources\app\Domains\Promoter;

use App\Models\Employee;
use App\Models\Promoter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromoterListResource extends JsonResource
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
            'name' => $employee->getFullName(),
        ];
    }
}
