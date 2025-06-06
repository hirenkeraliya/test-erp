<?php

declare(strict_types=1);

namespace App\Domains\MemberAddress\DataObjects;

use App\Rules\MobileNumber;
use Spatie\LaravelData\Data;

class EcommerceMemberAddressData extends Data
{
    public function __construct(
        public int $external_member_id,
        public int $external_member_address_id,
        public string $contact_mobile_number,
        public ?string $first_name,
        public ?string $last_name,
        public ?string $contact_email,
        public ?string $address_line_1,
        public ?string $address_line_2,
        public ?string $city,
        public string $area_code,
        public bool $is_primary,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'external_member_id' => ['required', 'integer'],
            'external_member_address_id' => ['required', 'integer'],
            'contact_mobile_number' => ['required', new MobileNumber()],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'area_code' => ['required', 'string', 'max:255'],
            'is_primary' => ['required', 'boolean'],
        ];
    }
}
