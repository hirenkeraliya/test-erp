<?php

declare(strict_types=1);

namespace App\Domains\Member\DataObjects;

use App\Models\Member;
use App\Rules\MobileNumber;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class SaleChannelRegisterMemberData extends Data
{
    public function __construct(
        public ?int $id,
        public string $first_name,
        public string $last_name,
        public string $mobile_number,
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
            'id' => ['nullable', 'integer'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', Rule::unique(Member::class, 'mobile_number'), new MobileNumber()],
            'email' => ['required', 'email', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'max:255', 'date_format:Y-m-d', 'before:today'],
            'image_url' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:255'],
        ];
    }
}
