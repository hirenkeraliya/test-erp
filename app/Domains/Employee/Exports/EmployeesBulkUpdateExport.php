<?php

declare(strict_types=1);

namespace App\Domains\Employee\Exports;

use App\Domains\Employee\Enums\JobTypes;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeesBulkUpdateExport implements FromCollection, WithHeadings, WithStyles
{
    public function __construct(
        protected Collection $employees
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
        return $this->employees->map(function (Employee $employee): array {
            /** @var Designation $designation */
            $designation = $employee->designation;

            /** @var ?EmployeeGroup $employeeGroup */
            $employeeGroup = $employee->employeeGroup;

            return [
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'mobile_number' => $employee->mobile_number,
                'home_contact' => $employee->home_contact,
                'address_line_1' => $employee->address_line_1,
                'address_line_2' => $employee->address_line_2,
                'city' => $employee->city,
                'area_code' => $employee->area_code,
                'date_of_joining' => $employee->date_of_joining,
                'primary_contact_name' => $employee->primary_contact_name,
                'primary_contact_phone' => $employee->primary_contact_phone,
                'designation_id' => $designation->name,
                'group_id' => $employeeGroup?->name,
                'staff_id' => $employee->staff_id,
                'ic_number' => $employee->ic_number,
                'job_type' => JobTypes::getCaseNameByValue($employee->job_type),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'first_name',
            'last_name',
            'email',
            'mobile_number',
            'home_contact',
            'address_line_1',
            'address_line_2',
            'city',
            'area_code',
            'date_of_joining',
            'primary_contact_name',
            'primary_contact_phone',
            'designation_name',
            'group_name',
            'staff_id',
            'ic_number',
            'job_type',
        ];
    }

    /**
     * @return string[]
     */
    private function getRequiredFieldsHeaderCellNumbers(): array
    {
        return ['A1', 'D1', 'F1', 'M1', 'O1', 'Q1'];
    }
}
