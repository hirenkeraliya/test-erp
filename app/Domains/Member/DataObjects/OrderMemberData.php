<?php

declare(strict_types=1);

namespace App\Domains\Member\DataObjects;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\MemberQueries;
use App\Rules\MobileNumber;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class OrderMemberData extends Data
{
    public function __construct(
        public int $type_id,
        public string $first_name,
        public string $mobile_number,
        public ?string $email,
        public int $created_location_id,
        public ?string $card_number = null,
        public ?string $company_name = null,
        public ?string $company_address = null,
        public ?string $pic_name = null,
        public ?string $pic_contact = null,
    ) {
    }

    public static function rules(int $companyId): array
    {
        $memberId = null;
        $memberQueries = new MemberQueries();
        $locationQueries = new LocationQueries();

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'mobile_number' => [
                'required',
                'string',
                'max:12',
                new MobileNumber(),
                Rule::unique('members', 'mobile_number')->ignore($memberId)
                    ->where($memberQueries->filterByCompany($companyId)),
            ],
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
            'card_number' => [
                'nullable',
                'string',
                Rule::unique('members', 'card_number')
                    ->where($memberQueries->filterByCompany($companyId)),
            ],
            'company_name' => ['nullable', 'string'],
            'company_address' => ['nullable', 'string'],
            'pic_name' => ['nullable', 'string'],
            'pic_contact' => ['nullable', 'string', 'max:12'],
            'created_location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')
                    ->where($locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value)),
            ],
        ];
    }
}
