<?php

declare(strict_types=1);

namespace App\Domains\CashierGroup\Exports;

use App\Domains\CashierGroup\Enums\PermissionTypes;
use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Models\CashierGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashierGroupExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected Collection $cashierGroups
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
        return $this->cashierGroups->map(function (CashierGroup $cashierGroup): array {
            /** @var Collection $permissions */
            $permissions = $cashierGroup->permissions;
            $permissionIds = $permissions->pluck('permission_id')->toArray();
            $collection = collect(PermissionTypes::getListByIds($permissionIds));
            $permissionNames = $collection->pluck('name')->implode(', ');

            return [
                'name' => $cashierGroup->name,
                'permissions' => $permissionNames,
                'price_override_limit_percentage_for_cart' => $cashierGroup->price_override_limit_percentage_for_cart,
                'price_override_limit_percentage_for_item' => $cashierGroup->price_override_limit_percentage_for_item,
                'price_override_type' => PriceOverrideTypes::getCaseName($cashierGroup->price_override_type),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'name',
            'permissions',
            'price_override_limit_percentage_for_cart',
            'price_override_limit_percentage_for_item',
            'price_override_type',
        ];
    }

    /**
     * @return string[]
     */
    private function getRequiredFieldsHeaderCellNumbers(): array
    {
        return ['A1', 'B1', 'C1', 'D1', 'E1'];
    }
}
