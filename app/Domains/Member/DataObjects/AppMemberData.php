<?php

declare(strict_types=1);

namespace App\Domains\Member\DataObjects;

use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\Titles;
use Spatie\LaravelData\Data;

class AppMemberData extends Data
{
    public function __construct(
        public ?int $title_id,
        public ?int $race_id,
        public string $first_name,
        public ?string $last_name,
        public ?int $gender_id,
        public ?string $email,
        public ?string $address_line_1,
        public ?string $address_line_2,
        public ?string $city,
        public ?string $area_code,
    ) {
    }

    public static function rules(): array
    {
        return [
            'title_id' => ['nullable', 'integer', 'in:' . Titles::getValues()],
            'race_id' => ['nullable', 'integer', 'in:' . Races::getValues()],
            'gender_id' => ['nullable', 'integer', 'in:' . Genders::getValues()],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'area_code' => ['nullable', 'string', 'max:255'],
        ];
    }
}
