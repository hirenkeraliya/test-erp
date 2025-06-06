<?php

declare(strict_types=1);

namespace App\Domains\OrderAddress\DataObjects;

use App\Rules\MobileNumber;
use Spatie\LaravelData\Data;

class EcommerceOrderAddressData extends Data
{
    public function __construct(
        public int $order_id,
        public int $type_id,
        public string $phone,
        public string $first_name,
        public ?string $last_name,
        public string $address_line_1,
        public ?string $address_line_2,
        public string $city,
        public string $area_code,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'type_id' => ['required', 'integer'],
            'phone' => ['required', new MobileNumber()],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'area_code' => ['required', 'string', 'max:255'],
        ];
    }
}
