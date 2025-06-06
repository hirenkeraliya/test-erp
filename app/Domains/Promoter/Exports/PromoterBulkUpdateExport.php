<?php

declare(strict_types=1);

namespace App\Domains\Promoter\Exports;

use App\Domains\Company\Enums\CommissionTypes;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Promoter;
use App\Models\PromoterGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PromoterBulkUpdateExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected Collection $promoters,
        protected Company $company
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
        return $this->promoters->map(function (Promoter $promoter): array {
            /** @var Collection $locations */
            $locations = $promoter->locations;

            $names = $locations->pluck('name')->toArray();

            /** @var Employee $employee */
            $employee = $promoter->employee;

            /** @var ?PromoterGroup $promoterGroup */
            $promoterGroup = $promoter->promoterGroup;
            $groupName = $promoterGroup instanceof PromoterGroup ? $promoterGroup->name : '';

            $return = [
                'first_name' => $employee->first_name,
                'mobile_number' => $employee->mobile_number,
                'username' => $promoter->username,
                'code' => $promoter->code,
                'group' => $groupName,
                'locations' => implode(', ', $names),
            ];

            if ($this->company->commission_type_id->value === CommissionTypes::BY_PROMOTER->value) {
                $return['monthly_sales_target'] = $promoter->monthly_sales_target;
                $return['default_commission_amount_percentage'] = $promoter->default_commission_amount_percentage;
                $return['monthly_target_commission_percentage'] = $promoter->monthly_target_commission_percentage;
            }

            return $return;
        });
    }

    public function headings(): array
    {
        if ($this->company->commission_type_id->value === CommissionTypes::BY_PROMOTER->value) {
            return [
                'first_name',
                'mobile_number',
                'username',
                'code',
                'group',
                'locations',
                'monthly_sales_target',
                'default_commission_amount_percentage',
                'monthly_target_commission_percentage',
            ];
        }

        return ['first_name', 'mobile_number', 'username', 'code', 'group', 'locations'];
    }

    /**
     * @return string[]
     */
    private function getRequiredFieldsHeaderCellNumbers(): array
    {
        return ['A1', 'B1', 'C1', 'F1'];
    }
}
