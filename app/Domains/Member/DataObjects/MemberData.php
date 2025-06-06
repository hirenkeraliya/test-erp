<?php

declare(strict_types=1);

namespace App\Domains\Member\DataObjects;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Domains\Member\MemberQueries;
use App\Rules\MobileNumber;
use App\Rules\ValidatePrimaryAddress;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Spatie\LaravelData\Data;

class MemberData extends Data
{
    public function __construct(
        public int $type_id,
        public int $title_id,
        public int $race_id,
        public string $first_name,
        public ?string $last_name,
        public int $gender_id,
        public ?string $date_of_birth,
        public string $mobile_number,
        public string $email,
        public ?string $company_name,
        public ?string $company_registration_number,
        public ?string $company_tax_number,
        public ?string $company_address,
        public ?string $company_phone,
        public ?string $pic_name,
        public ?string $pic_contact,
        public int $created_location_id,
        public ?string $notes,
        public ?UploadedFile $photo,
        public ?string $card_number,
        public ?string $loyalty_points = null,
        public ?array $member_addresses = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $memberId = null;
        $memberQueries = new MemberQueries();
        $locationQueries = new LocationQueries();
        $companyId = session('admin_company_id');

        if ('admin.members.update' === $request->route()?->getName()) {
            $memberId = $request->route()->parameter('memberId');
        }

        if ('store_manager.members.store' === $request->route()?->getName()) {
            $companyId = session('store_manager_selected_location_company_id');
        }

        if ('store_manager.members.update' === $request->route()?->getName()) {
            $memberId = $request->route()->parameter('memberId');
            $companyId = session('store_manager_selected_location_company_id');
        }

        return [
            'type_id' => ['required', 'integer', 'in:' . Types::getValues()],
            'title_id' => ['required', 'integer', 'in:' . Titles::getValues()],
            'race_id' => ['required', 'integer', 'in:' . Races::getValues()],
            'gender_id' => ['required', 'integer', 'in:' . Genders::getValues()],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'mobile_number' => [
                'required',
                new MobileNumber(),
                Rule::unique('members', 'mobile_number')->ignore($memberId)
                    ->where($memberQueries->filterByCompany($companyId)),
            ],
            'date_of_birth' => ['nullable', 'date', 'max:255', 'before:' . now()->format('Y-m-d')],
            'created_location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')
                    ->where($locationQueries->filterByCompanyAndTypeId($companyId, LocationTypes::STORE->value)),
            ],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_registration_number' => ['nullable', 'string', 'max:255'],
            'company_tax_number' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:12'],
            'pic_name' => ['nullable', 'string'],
            'pic_contact' => ['nullable', 'string', 'max:12'],
            'card_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('members', 'card_number')->ignore($memberId)
                    ->where($memberQueries->filterByCompany($companyId)),
            ],
            'notes' => ['nullable', 'string', 'max:255'],
            'photo' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(300)->maxHeight(300)),
                'max:' . config('services.max_upload_size'),
            ],
            'member_addresses' => ['nullable', 'array'],
            'member_addresses.*.name' => ['nullable', 'string', 'max:255'],
            'member_addresses.*.first_name' => ['nullable', 'string', 'max:255'],
            'member_addresses.*.last_name' => ['nullable', 'string', 'max:255'],
            'member_addresses.*.contact_mobile_number' => ['required', new MobileNumber()],
            'member_addresses.*.contact_email' => ['nullable', 'email', 'max:255'],
            'member_addresses.*.address_line_1' => ['required', 'string', 'max:255'],
            'member_addresses.*.address_line_2' => ['nullable', 'string', 'max:255'],
            'member_addresses.*.city' => ['nullable', 'string', 'max:255'],
            'member_addresses.*.area_code' => ['required', 'string', 'max:255'],
            'member_addresses.*.is_primary' => ['boolean', new ValidatePrimaryAddress()],
        ];
    }
}
