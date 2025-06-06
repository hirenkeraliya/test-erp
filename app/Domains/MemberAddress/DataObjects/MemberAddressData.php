<?php

declare(strict_types=1);

namespace App\Domains\MemberAddress\DataObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class MemberAddressData extends Data
{
    public function __construct(
        public readonly int $member_id,
        public readonly string $name,
        public readonly ?string $contact_mobile_number,
        public readonly ?string $contact_email,
        public readonly string $address_line_1,
        public readonly ?string $address_line_2,
        #[MapInputName('city')]
        public readonly ?string $city_name,
        public readonly ?string $area_code,
        public readonly bool $is_primary,
        public readonly ?int $country_id = null,
        public readonly ?int $state_id = null,
        public readonly ?int $city_id = null,
    ) {
    }

    public static function rules(): array
    {
        return [
            'member_id' => ['required', 'integer'],
            'name' => ['required', 'string'],
            'contact_mobile_number' => ['nullable', 'string'],
            'contact_email' => ['nullable', 'string', 'email'],
            'address_line_1' => ['required', 'string'],
            'address_line_2' => ['nullable', 'string'],
            'city_name' => ['nullable', 'string'],
            'area_code' => ['nullable', 'string'],
            'is_primary' => ['required', 'boolean'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ];
    }
}
