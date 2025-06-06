<?php

declare(strict_types=1);

namespace App\Domains\Vendor\DataObjects;

use App\Domains\Vendor\VendorQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class VendorData extends Data
{
    public function __construct(
        public string $name,
        public ?string $code,
        public ?string $registration_number,
        public ?string $sst_number,
        public string $email,
        public string $phone,
        public ?string $mobile,
        public ?string $fax,
        public string $address_line_1,
        public ?string $address_line_2,
        public string $city,
        public string $area_code,
        public ?string $website,
        public bool $is_consignment,
        public ?int $commission_percentage,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $vendorId = null;
        $vendorQueries = new VendorQueries();

        if ('admin.vendors.update' === $request->route()?->getName()) {
            $vendorId = $request->route()->parameter('vendorId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vendors', 'name')->ignore($vendorId)
                    ->where($vendorQueries->filterByCompany(session('admin_company_id'))),
            ],
            'code' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'sst_number' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'phone' => [
                'required',
                'numeric',
                'digits_between:8,12',
                Rule::unique('vendors', 'phone')->ignore($vendorId)
                    ->where($vendorQueries->filterByCompany(session('admin_company_id'))),
            ],
            'mobile' => ['nullable', 'numeric', 'digits_between:8,12'],
            'fax' => ['nullable', 'string', 'max:255'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'area_code' => ['required', 'numeric', 'digits_between:1,20'],
            'website' => ['nullable', 'url', 'max:255'],
            'is_consignment' => ['required', 'boolean'],
            'commission_percentage' => [
                Rule::requiredIf($request->is_consignment),
                'nullable',
                'numeric',
                'min:1',
                'max:100',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'phone.digits' => 'Please enter a valid phone number.',
        ];
    }
}
