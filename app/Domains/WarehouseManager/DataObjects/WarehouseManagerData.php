<?php

declare(strict_types=1);

namespace App\Domains\WarehouseManager\DataObjects;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class WarehouseManagerData extends Data
{
    public function __construct(
        public int $employee_id,
        public string $username,
        public ?string $password,
        public array $location_ids,
        public array $role_ids,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $warehouseManagerId = null;

        if ('admin.warehouse_managers.update' === $request->route()?->getName()) {
            /** @var string $warehouseManagerId */
            $warehouseManagerId = $request->route()->parameter('warehouseManagerId');
        }

        $rules = [
            'employee_id' => [
                'required',
                'integer',
                new Unique('warehouse_managers', 'employee_id', ignore: $warehouseManagerId),
            ],
            'username' => [
                'required',
                'string',
                'min:4',
                'max:255',
                new Unique('warehouse_managers', 'username', ignore: $warehouseManagerId),
            ],
            'location_ids' => ['required', 'array'],
            'location_ids.*' => ['required', 'integer'],
            'role_ids' => ['required', 'array'],
        ];

        if ('admin.warehouse_managers.store' === $request->route()?->getName()) {
            $rules['password'] = ['required', 'confirmed', 'string', 'max:20', Password::defaults()];
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'password' => 'A password must be at least 8 characters long and include a combination of uppercase and lowercase letters, numbers, and symbols.',
            'password.confirmed' => 'The confirmed password does not match the original password. Please re-enter your password and confirm it.',
        ];
    }
}
