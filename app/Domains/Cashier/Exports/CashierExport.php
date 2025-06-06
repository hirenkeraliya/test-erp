<?php

declare(strict_types=1);

namespace App\Domains\Cashier\Exports;

use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Models\Cashier;
use App\Models\CashierGroup;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CashierExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $cashiers
    ) {
    }

    public function collection(): Collection
    {
        return $this->cashiers->map(function (Cashier $cashier): array {
            /** @var Employee $employee */
            $employee = $cashier->employee;
            /** @var CashierGroup $cashierGroup */
            $cashierGroup = $cashier->cashierGroup;
            /** @var Collection $locations */
            $locations = $cashier->locations;
            $locationNames = $locations->map(fn ($location): string => $location->name)->implode(', ');

            return [
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'mobile_number' => $employee->mobile_number,
                'staff_id' => $employee->staff_id,
                'ic_number' => $employee->ic_number,
                'cashier_group ' => $cashierGroup->name,
                'price_override_limit_percentage_for_item' => (float) $cashierGroup->price_override_limit_percentage_for_item,
                'price_override_type' => PriceOverrideTypes::getCaseName($cashierGroup->price_override_type),
                'price_override_limit_percentage_for_cart' => (float) $cashierGroup->price_override_limit_percentage_for_cart,
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
            'IC Number',
            'Cashier Group',
            'Price Override Limit Percentage for Item',
            'Price Override Type',
            'Price Override Limit Percentage for Cart',
            'Locations',
        ];
    }
}
