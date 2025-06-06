<?php

declare(strict_types=1);

namespace App\Domains\Promoter\DataPreparer;

use App\Models\Employee;
use App\Models\Promoter;
use Illuminate\Support\Collection;

class PromoterDataPreparer
{
    public function getPromoters(Collection $promoters): ?array
    {
        if ($promoters->isEmpty()) {
            return null;
        }

        return $promoters->map(function (Promoter $promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'staff_id' => $employee->staff_id,
            ];
        })->toArray();
    }
}
