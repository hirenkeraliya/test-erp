<?php

declare(strict_types=1);

namespace App\Domains\Member\DataObjects;

use App\Rules\MobileNumber;
use App\Rules\ValidatePrimaryAddress;
use Spatie\LaravelData\Data;

class UpdateMemberAddressData extends Data
{
    public function __construct(
        public array $member_addresses,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'member_addresses' => ['required', 'array'],
            'member_addresses.*.name' => ['nullable', 'string', 'max:255'],
            'member_addresses.*.first_name' => ['nullable', 'string', 'max:255'],
            'member_addresses.*.last_name' => ['nullable', 'string', 'max:255'],
            'member_addresses.*.contact_mobile_number' => ['required', new MobileNumber()],
            'member_addresses.*.contact_email' => ['nullable', 'email', 'max:255'],
            'member_addresses.*.address_line_1' => ['required', 'string', 'max:255'],
            'member_addresses.*.address_line_2' => ['nullable', 'string', 'max:255'],
            'member_addresses.*.city_name' => ['nullable', 'string', 'max:255'],
            'member_addresses.*.country_id' => ['nullable', 'integer'],
            'member_addresses.*.state_id' => ['nullable', 'integer'],
            'member_addresses.*.city_id' => ['nullable', 'integer'],
            'member_addresses.*.area_code' => ['required', 'string', 'max:255'],
            'member_addresses.*.is_primary' => ['boolean', new ValidatePrimaryAddress()],
        ];
    }
}
