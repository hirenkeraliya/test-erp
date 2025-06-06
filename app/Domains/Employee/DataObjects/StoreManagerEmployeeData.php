<?php

declare(strict_types=1);

namespace App\Domains\Employee\DataObjects;

use App\Domains\Designation\DesignationQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Employee\Enums\JobTypes;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Models\StoreManager;
use App\Rules\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Spatie\LaravelData\Data;

class StoreManagerEmployeeData extends Data
{
    public function __construct(
        public int $designation_id,
        public string $first_name,
        public ?string $last_name,
        public ?string $email,
        public string $mobile_number,
        public ?string $home_contact,
        public string $address_line_1,
        public ?string $address_line_2,
        public ?string $city,
        public ?string $area_code,
        public ?string $date_of_joining,
        public ?string $primary_contact_name,
        public ?string $primary_contact_phone,
        public string $staff_id,
        public ?string $ic_number,
        public int $job_type,
        public bool $status,
        public ?UploadedFile $photo,
        public ?int $group_id = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $employeeId = null;
        $employeeQueries = new EmployeeQueries();
        $designationQueries = new DesignationQueries();
        $employeeGroupQueries = new EmployeeGroupQueries();

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        if ('store_manager.employee.update' === $request->route()?->getName()) {
            $employeeId = $request->route()->parameter('employeeId');
        }

        return [
            'designation_id' => [
                'required',
                'integer',
                Rule::exists('designations', 'id')
                    ->where($designationQueries->filterByCompany($companyId)),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email:rfc,dns',
                'max:255',
                Rule::unique('employees', 'email')->ignore($employeeId)
                    ->where($employeeQueries->filterByCompany($companyId)),
            ],
            'mobile_number' => [
                'required',
                new MobileNumber(),
                Rule::unique('employees', 'mobile_number')->ignore($employeeId)
                    ->where($employeeQueries->filterByCompany($companyId)),
            ],
            'home_contact' => ['nullable', 'string', 'max:12'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'area_code' => ['nullable', 'string', 'max:255'],
            'date_of_joining' => ['nullable', 'string', 'max:255', 'before_or_equal:' . now()->format('Y-m-d')],
            'primary_contact_name' => ['nullable', 'string', 'max:255'],
            'primary_contact_phone' => ['nullable', 'string', 'max:12'],
            'staff_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employees', 'staff_id')->ignore($employeeId)->where(
                    $employeeQueries->filterByCompany($companyId)
                ),
            ],
            'ic_number' => ['nullable', 'string', 'max:255'],
            'job_type' => ['required', 'integer', 'in:' . JobTypes::getValues()],
            'status' => ['required', 'boolean'],
            'photo' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(300)->maxHeight(300)),
                'max:' . config('services.max_upload_size'),
            ],
            'group_id' => [
                'nullable',
                'integer',
                Rule::exists('employee_groups', 'id')
                    ->where($employeeGroupQueries->filterByCompany($companyId)),
            ],
        ];
    }
}
