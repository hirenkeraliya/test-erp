<?php

declare(strict_types=1);

namespace App\Domains\Member\DataObjects;

use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Domains\Member\MemberQueries;
use App\Rules\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class PosMemberData extends Data
{
    public function __construct(
        public ?int $type_id,
        public ?int $title_id,
        public ?int $race_id,
        public string $first_name,
        public ?string $last_name,
        public ?int $gender_id,
        public ?string $date_of_birth,
        public string $mobile_number,
        public ?string $email,
        public ?string $address_line_1,
        public ?string $address_line_2,
        public ?string $city_name,
        public ?string $area_code,
        public ?string $company_name,
        public ?string $company_registration_number,
        public ?string $company_tax_number,
        public ?string $company_phone,
        public ?string $notes,
        public ?string $card_number = null,
        public ?UploadedFile $photo = null,
        public ?int $created_location_id = null,
    ) {
    }

    public static function rules(Request $request, int $companyId): array
    {
        $memberId = null;
        $memberQueries = new MemberQueries();

        if ('pos.members.update' === $request->route()?->getName()) {
            $memberId = $request->route()->parameter('memberId');
        }

        return [
            'type_id' => ['required', 'integer', 'in:' . Types::getValues()],
            'title_id' => ['nullable', 'integer', 'in:' . Titles::getValues()],
            'race_id' => ['nullable', 'integer', 'in:' . Races::getValues()],
            'gender_id' => ['nullable', 'integer', 'in:' . Genders::getValues()],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile_number' => [
                'required',
                new MobileNumber(),
                Rule::unique('members', 'mobile_number')->ignore($memberId)
                    ->where($memberQueries->filterByCompany($companyId)),
            ],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city_name' => ['nullable', 'string', 'max:255'],
            'area_code' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_registration_number' => ['nullable', 'string', 'max:255'],
            'company_tax_number' => ['nullable', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:12'],
            'notes' => ['nullable', 'string', 'max:255'],
            'card_number' => [
                'sometimes',
                'nullable',
                'string',
                Rule::unique('members', 'card_number')
                    ->ignore($memberId)
                    ->where($memberQueries->filterByCompany($companyId)),
            ],
        ];
    }
}
