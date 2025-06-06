<?php

declare(strict_types=1);

namespace App\Domains\MemberAddress\DataObjects;

use App\Rules\MobileNumber;
use Spatie\LaravelData\Data;

class AppMemberAddressData extends Data
{
    public function __construct(
        public ?string $name,
        public string $contact_mobile_number,
        public ?string $contact_email,
        public ?string $address_line_1,
        public ?string $address_line_2,
        public ?string $city,
        public ?string $area_code,
        public bool $is_primary,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'contact_mobile_number' => ['required', new MobileNumber()],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'area_code' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['required', 'boolean'],
        ];
    }
}
