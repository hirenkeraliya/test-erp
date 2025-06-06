<?php

declare(strict_types=1);

namespace App\Domains\StoreManager\Exports;

use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Models\Employee;
use App\Models\StoreManager;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StoreManagerBulkUpdateExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected Collection $storeManagers
    ) {
    }

    public function styles(Worksheet $sheet): void
    {
        $headerCellNumbers = $this->getRequiredFieldsHeaderCellNumbers();

        foreach ($headerCellNumbers as $headerCellNumber) {
            $sheet->getStyle($headerCellNumber)
                ->getFont()
                ->getColor()
                ->setARGB(Color::COLOR_RED);
        }
    }

    public function collection(): Collection
    {
        return $this->storeManagers->map(function (StoreManager $storeManager): array {
            /** @var Employee $employee */
            $employee = $storeManager->employee;
            /** @var Collection $locations */
            $locations = $storeManager->locations;
            $locationNames = $locations->map(fn ($location): string => $location->name)->implode(', ');
            /** @var Collection $brands */
            $brands = $storeManager->brands;
            $brandNames = $brands->map(fn ($brand): string => $brand->name)->implode(', ');
            /** @var Collection $roles */
            $roles = $storeManager->roles;
            $roleNames = $roles->map(fn ($role): string => $role->name)->implode(', ');

            return [
                'first_name' => $employee->first_name,
                'mobile_number' => $employee->mobile_number,
                'username' => $storeManager->username,
                'price_override_type' => PriceOverrideTypes::getFormattedCaseName($storeManager->price_override_type),
                'price_override_limit_percentage_for_cart' => $storeManager->price_override_limit_percentage_for_cart,
                'price_override_limit_percentage_for_item' => $storeManager->price_override_limit_percentage_for_item,
                'can_manage_wholesale' => $storeManager->can_manage_wholesale ? 'Yes' : 'No',
                'brands' => $brandNames,
                'locations' => $locationNames,
                'roles' => $roleNames,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'first_name',
            'mobile_number',
            'username',
            'price_override_type',
            'price_override_limit_percentage_for_cart',
            'price_override_limit_percentage_for_item',
            'can_manage_wholesale',
            'brands',
            'locations',
            'roles',
        ];
    }

    /**
     * @return string[]
     */
    private function getRequiredFieldsHeaderCellNumbers(): array
    {
        return ['A1', 'B1', 'C1', 'D1', 'I1', 'J1'];
    }
}
