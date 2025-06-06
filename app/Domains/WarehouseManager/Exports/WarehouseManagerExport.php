<?php

declare(strict_types=1);

namespace App\Domains\WarehouseManager\Exports;

use App\Models\Employee;
use App\Models\WarehouseManager;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WarehouseManagerExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $warehouseManagers
    ) {
    }

    public function collection(): Collection
    {
        return $this->warehouseManagers->map(function (WarehouseManager $warehouseManager): array {
            /** @var Employee $employee */
            $employee = $warehouseManager->employee;
            /** @var Collection $locations */
            $locations = $warehouseManager->locations;
            $locationNames = $locations->map(fn ($location): string => $location->name)->implode(', ');

            return [
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'mobile_number' => $employee->mobile_number,
                'staff_id' => $employee->staff_id,
                'ic_number' => $employee->ic_number,
                'locations' => $locationNames,
            ];
        });
    }

    public function headings(): array
    {
        return ['First Name', 'Last Name', 'Email', 'Mobile Number', 'Staff Id', 'Ic Number', 'Locations'];
    }
}
