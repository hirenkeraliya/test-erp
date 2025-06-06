<?php

declare(strict_types=1);

namespace App\Domains\Member\DataObjects;

use Spatie\LaravelData\Data;

class SaleChannelMemberData extends Data
{
    public function __construct(
        public ?string $mobile_number,
        public ?string $email,
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'email' => ['required_without_all:mobile_number', 'nullable'],
            'mobile_number' => ['required_without_all:email', 'nullable'],
        ];
    }
}
