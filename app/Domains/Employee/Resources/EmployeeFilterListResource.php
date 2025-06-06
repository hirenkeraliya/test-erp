<?php

declare(strict_types=1);

namespace App\Domains\Employee\Resources;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeFilterListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Employee $employee */
        $employee = $this;

        return [
            'id' => $employee->getKey(),
            'name' => $employee->getFullName(),
        ];
    }
}
