<?php

declare(strict_types=1);

namespace App\Domains\WarehouseManager\DataObjects;

use Illuminate\Http\Request;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class WarehouseManagerProfileData extends Data
{
    public function __construct(
        public int $employee_id,
        public string $username,
        public ?string $two_factor_secret,
        public ?string $two_factor_recovery_codes,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $warehouseManagerId = null;

        if ('warehouse_manager.update' === $request->route()?->getName()) {
            /** @var string $warehouseManagerId */
            $warehouseManagerId = $request->route()->parameter('warehouseManagerId');
        }

        return [
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
        ];
    }
}
