<?php

declare(strict_types=1);

namespace App\Domains\ShippingZone\DataObjects;

use Spatie\LaravelData\Data;

class ShippingZoneData extends Data
{
    public function __construct(
        public string $name,
        public ?int $country_id,
        public array $state_ids = [],
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'state_ids' => ['required', 'array', 'exists:states,id'],
        ];
    }
}
