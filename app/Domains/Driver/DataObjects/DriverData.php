<?php

declare(strict_types=1);

namespace App\Domains\Driver\DataObjects;

use App\Rules\ValidPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class DriverData extends Data
{
    public function __construct(
        public string $name,
        public string $id_number,
        public ?string $email,
        public string $mobile_number,
        public string $country_code,
        public bool $status = true,
    ) {
    }

    public static function rules(Request $request): array
    {
        $driverId = $request->route('driverId');
        $companyId = session('admin_company_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'id_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('drivers')
                    ->where('company_id', $companyId)
                    ->ignore($driverId),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile_number' => [
                'required',
                'string',
                'max:255',
                new ValidPhoneNumber($request->input('country_code')),
                Rule::unique('drivers')
                    ->where('company_id', $companyId)
                    ->ignore($driverId),
            ],
            'country_code' => ['required', 'string', 'max:10'],
            'status' => ['required', 'boolean'],
        ];
    }
}
