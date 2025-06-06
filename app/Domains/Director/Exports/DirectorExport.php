<?php

declare(strict_types=1);

namespace App\Domains\Director\Exports;

use App\Models\Director;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DirectorExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $directors
    ) {
    }

    public function collection(): Collection
    {
        return $this->directors->map(function (Director $director): array {
            /** @var Employee $employee */
            $employee = $director->employee;
            /** @var Collection $locations */
            $locations = $director->locations;
            $locationNames = $locations->map(fn ($location): string => $location->name)->implode(', ');

            return [
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'mobile_number' => $employee->mobile_number,
                'staff_id' => $employee->staff_id,
                'ic_number' => $employee->ic_number,
                'price_override_limit_percentage_for_item' => $director->price_override_limit_percentage_for_item,
                'price_override_type' => $director->price_override_type,
                'price_override_limit_percentage_for_cart' => $director->price_override_limit_percentage_for_cart,
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
            'Price override limit percentage For Item',
            'Price override type',
            'Price override limit percentage For Cart',
            'locations',
        ];
    }
}
