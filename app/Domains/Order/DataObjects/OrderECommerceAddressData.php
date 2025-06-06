<?php

declare(strict_types=1);

namespace App\Domains\Order\DataObjects;

use App\Rules\MobileNumber;

class OrderECommerceAddressData
{
    public function __construct(
        public int $id,
        public ?string $first_name,
        public ?string $last_name,
        public string $phone,
        public string $area_code,
        public ?string $city_name,
        public string $address_line_1,
        public ?string $address_line_2,
    ) {
    }

    public static function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', new MobileNumber()],
            'area_code' => ['required', 'string', 'max:255'],
            'city_name' => ['nullable', 'string', 'max:255', 'exists:cities,name'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
        ];
    }
}
