<?php

declare(strict_types=1);

namespace App\Domains\StoreManager\Exports;

use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Models\Employee;
use App\Models\StoreManager;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StoreManagerExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $storeManagers
    ) {
    }

    public function collection(): Collection
    {
        return $this->storeManagers->map(function (StoreManager $storeManager): array {
            /** @var Employee $employee */
            $employee = $storeManager->employee;
            /** @var Collection $locations */
            $locations = $storeManager->locations;
            $locationNames = $locations->map(fn ($location): string => $location->name)->implode(', ');

            return [
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'mobile_number' => $employee->mobile_number,
                'staff_id' => $employee->staff_id,
                'ic_number' => $employee->ic_number,
                'price_override_limit_percentage_for_item' => $storeManager->price_override_limit_percentage_for_item,
                'price_override_type' => PriceOverrideTypes::getCaseName($storeManager->price_override_type),
                'price_override_limit_percentage_for_cart' => $storeManager->price_override_limit_percentage_for_cart,
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
            'Price Override Limit Percentage For Item',
            'Price Override Type',
            'Price Override Limit Percentage For Cart',
            'locations',
        ];
    }
}
