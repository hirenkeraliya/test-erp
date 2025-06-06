<?php

declare(strict_types=1);

namespace App\Domains\Vehicle\DataObjects;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class VehicleData extends Data
{
    public function __construct(
        public string $name,
        public string $plate_no,
        public ?string $type_of_vehicle,
        public bool $status = true,
    ) {
    }

    public static function rules(Request $request): array
    {
        $vehicleId = $request->route('vehicleId');
        $companyId = session('admin_company_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'plate_no' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vehicles')
                    ->where('company_id', $companyId)
                    ->ignore($vehicleId),
            ],
            'type_of_vehicle' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ];
    }
}
