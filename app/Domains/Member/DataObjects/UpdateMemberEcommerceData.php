<?php

declare(strict_types=1);

namespace App\Domains\Member\DataObjects;

use App\Rules\MobileNumber;
use Spatie\LaravelData\Data;

class UpdateMemberEcommerceData extends Data
{
    public function __construct(
        public string $first_name,
        public ?string $last_name,
        public ?string $mobile_number,
        public string $email,
        public ?string $date_of_birth,
        public ?string $image_url,
        public ?string $gender,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'mobile_number' => ['nullable', new MobileNumber()],
            'date_of_birth' => ['nullable', 'date', 'max:255', 'before:' . now()->format('Y-m-d')],
            'image_url' => ['nullable', 'string'],
            'gender' => ['nullable', 'string', 'max:255'],
        ];
    }
}
