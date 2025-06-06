<?php

declare(strict_types=1);

namespace App\Domains\Vendor\DataObjects;

use Spatie\LaravelData\Data;

class VendorListForWarehouseManagerAppData extends Data
{
    public function __construct(
        public ?int $warehouse_id,
        public ?int $location_id,
        public ?string $search_text = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'warehouse_id' => ['required_without:location_id', 'integer'],
            'location_id' => ['required_without:warehouse_id', 'integer'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
