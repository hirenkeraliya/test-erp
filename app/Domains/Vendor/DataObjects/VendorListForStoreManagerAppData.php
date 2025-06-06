<?php

declare(strict_types=1);

namespace App\Domains\Vendor\DataObjects;

use Spatie\LaravelData\Data;

class VendorListForStoreManagerAppData extends Data
{
    public function __construct(
        public ?int $store_id,
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
            'store_id' => ['required_without_all:location_id', 'integer'],
            'location_id' => ['required_without_all:store_id', 'integer'],
            'search_text' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
