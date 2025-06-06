<?php

declare(strict_types=1);

namespace App\Domains\Member\DataObjects;

use App\Rules\MobileNumber;
use Spatie\LaravelData\Data;

class PromoterMemberData extends Data
{
    public function __construct(
        public string $first_name,
        public string $mobile_number,
        public ?string $email,
        public ?string $date_of_birth,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'unique:members,mobile_number', new MobileNumber()],
            'email' => ['sometimes', 'nullable', 'email'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d'],
        ];
    }
}
