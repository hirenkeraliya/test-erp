<?php

declare(strict_types=1);

namespace App\Domains\Employee\DataObjects;

use App\Domains\Employee\EmployeeQueries;
use App\Models\WarehouseManager;
use App\Rules\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class WarehouseManagerApplicationData extends Data
{
    public function __construct(
        public string $username,
        public string $first_name,
        public ?string $last_name,
        public ?string $email,
        public string $mobile_number,
        public ?UploadedFile $photo,
        public string $address_line_1,
        public ?string $address_line_2,
        public ?string $home_contact,
        public ?string $city,
        public ?string $area_code,
        public ?string $primary_contact_name,
        public ?string $primary_contact_phone,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $employeeId = null;
        $employeeQueries = new EmployeeQueries();

        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $employeeId = $warehouseManager->employee_id;

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('employees', 'email')->ignore($employeeId)
                    ->where($employeeQueries->filterByCompany((int) $request->input('company_id'))),
            ],
            'mobile_number' => [
                'required',
                new MobileNumber(),
                Rule::unique('employees', 'mobile_number')->ignore($employeeId)
                    ->where($employeeQueries->filterByCompany((int) $request->input('company_id'))),
            ],
            'home_contact' => ['nullable', 'string', 'max:12'],
            'username' => [
                'required',
                'string',
                'min:4',
                'max:255',
                new Unique('warehouse_managers', 'username', ignore: (string) $warehouseManager->getKey()),
            ],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'area_code' => ['nullable', 'string', 'max:255'],
            'primary_contact_name' => ['nullable', 'string', 'max:255'],
            'primary_contact_phone' => ['nullable', 'string', 'max:12'],
            'photo' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/gif,image/png',
                'max:' . config('services.max_upload_size'),
            ],
        ];
    }
}
