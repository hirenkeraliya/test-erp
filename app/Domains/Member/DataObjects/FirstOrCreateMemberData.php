<?php

declare(strict_types=1);

namespace App\Domains\Member\DataObjects;

use App\Rules\MobileNumber;
use Spatie\LaravelData\Data;

class FirstOrCreateMemberData extends Data
{
    public function __construct(
        public string $first_name,
        public string $mobile_number,
        public string $email,
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
            'mobile_number' => ['required', new MobileNumber()],
            'email' => ['required', 'email', 'max:255'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'max:255', 'date_format:Y-m-d', 'before:today'],
        ];
    }
}
