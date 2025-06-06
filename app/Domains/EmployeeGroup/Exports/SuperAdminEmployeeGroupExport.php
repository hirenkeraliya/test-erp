<?php

declare(strict_types=1);

namespace App\Domains\EmployeeGroup\Exports;

use App\Domains\EmployeeGroup\Enums\LimitResetDays;
use App\Domains\EmployeeGroup\Enums\LimitResetTypes;
use App\Domains\EmployeeGroup\Enums\PurchaseLimitTypes;
use App\Models\Company;
use App\Models\EmployeeGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SuperAdminEmployeeGroupExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $employeeGroups
    ) {
    }

    public function collection(): Collection
    {
        return $this->employeeGroups->map(function (EmployeeGroup $employeeGroup): array {
            /** @var Company $company */
            $company = $employeeGroup->company;

            return [
                'company' => $company->name,
                'name' => $employeeGroup->name,
                'code' => $employeeGroup->code,
                'purchase_limit_type' => PurchaseLimitTypes::getFormattedCaseName(
                    (int) $employeeGroup->purchase_limit_type_id
                ),
                'item_purchase_limit' => $employeeGroup->item_purchase_limit,
                'limit_reset_type' => LimitResetTypes::getFormattedCaseName((int) $employeeGroup->limit_reset_type_id),
                'limit_reset' => $this->getLimitReset($employeeGroup->limit_reset_type_id, $employeeGroup->limit_reset),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Company',
            'Name',
            'Code',
            'Purchase Limit Type',
            'Item Purchase Limit',
            'Limit Reset Type',
            'Limit Reset',
        ];
    }

    private function getLimitReset(int $limitResetTypeId, int $limitReset): int|string
    {
        if ($limitResetTypeId === LimitResetTypes::BY_WEEK->value) {
            return LimitResetDays::getFormattedCaseName($limitReset);
        }

        return $limitReset;
    }
}
