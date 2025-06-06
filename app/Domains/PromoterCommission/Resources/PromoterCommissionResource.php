<?php

declare(strict_types=1);

namespace App\Domains\PromoterCommission\Resources;

use App\Models\Employee;
use App\Models\Promoter;
use App\Models\PromoterCommission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PromoterCommissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var PromoterCommission $promoterCommission */
        $promoterCommission = $this;

        /** @var Promoter $promoter */
        $promoter = $promoterCommission->promoter;

        /** @var Employee $employee */
        $employee = $promoter->employee;
        $promoterCommissionUpdates = $promoterCommission->promoterCommissionUpdates;

        /** @var Collection $locations */
        $locations = $promoter->locations;

        $locationNames = $locations->map(fn ($location): string => $location->name)->implode(', ');

        return [
            'id' => $promoterCommission->id,
            'staff_id' => $employee->staff_id,
            'designation' => $employee->designation?->name,
            'promoter' => $employee->getFullName(),
            'locations' => $locationNames,
            'commission_date' => $promoterCommission->commission_date,
            'monthly_sales_target' => $promoterCommission->monthly_sales_target,
            'total_sales_amount' => $promoterCommissionUpdates->sum('amount'),
            'commission_amount' => $promoterCommissionUpdates->sum('commission_amount'),
            'promoter_id' => $promoterCommission->promoter_id,
        ];
    }
}
