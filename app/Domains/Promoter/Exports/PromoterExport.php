<?php

declare(strict_types=1);

namespace App\Domains\Promoter\Exports;

use App\Models\Employee;
use App\Models\Promoter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PromoterExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $promoters
    ) {
    }

    public function collection(): Collection
    {
        return $this->promoters->map(function (Promoter $promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;
            /** @var Collection $locations */
            $locations = $promoter->locations;
            $locationNames = $locations->map(fn ($location): string => $location->name)->implode(', ');

            return [
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'mobile_number' => $employee->mobile_number,
                'staff_id' => $employee->staff_id,
                'ic_number' => $employee->ic_number,
                'code' => $promoter->code,
                'monthly_sales_target' => $promoter->monthly_sales_target,
                'default_commission_amount_percentage' => $promoter->default_commission_amount_percentage,
                'monthly_target_commission_percentage' => $promoter->monthly_target_commission_percentage,
                'locations' => $locationNames,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'First Name',
            'Last Name',
            'Email',
            'Mobile Number',
            'Staff Id',
            'Ic Number',
            'Code',
            'Monthly Sales Target',
            'Default  Commission Percentage',
            'Monthly Target Commission Percentage',
            'Locations',
        ];
    }
}
