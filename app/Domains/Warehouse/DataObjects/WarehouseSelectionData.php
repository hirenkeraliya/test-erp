<?php

declare(strict_types=1);

namespace App\Domains\Warehouse\DataObjects;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Spatie\LaravelData\Data;

class WarehouseSelectionData extends Data
{
    public function __construct(
        public int $location_id,
    ) {
    }

    /**
     * @return array<string, array<Exists|string>>
     */
    public static function rules(): array
    {
        return [
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
        ];
    }
}
