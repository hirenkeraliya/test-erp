<?php

declare(strict_types=1);

namespace App\Domains\Employee\Exports;

use App\Domains\Employee\Enums\JobTypes;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployeeExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $employees
    ) {
    }

    public function collection(): Collection
    {
        return $this->employees->map(function (Employee $employee): array {
            /** @var Designation $designation */
            $designation = $employee->designation;

            return [
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'mobile_number' => $employee->mobile_number,
                'home_contact' => $employee->home_contact,
                'address_line_1' => $employee->address_line_1,
                'address_line_2' => $employee->address_line_2,
                'area_code' => $employee->area_code,
                'date_of_joining' => $employee->date_of_joining,
                'primary_contact_name' => $employee->primary_contact_name,
                'primary_contact_phone' => $employee->primary_contact_phone,
                'designation_id' => $designation->name,
                'staff_id' => $employee->staff_id,
                'ic_number' => $employee->ic_number,
                'job_type' => JobTypes::getCaseNameByValue($employee->job_type),
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
            'Home Contact',
            'Address Line 1',
            'Address Line 2',
            'Area Code',
            'Date Of Joining',
            'Primary Contact Name',
            'Primary Contact Phone',
            'Designation',
            'Staff Id',
            'Ic Number',
            'Job Type',
        ];
    }
}
