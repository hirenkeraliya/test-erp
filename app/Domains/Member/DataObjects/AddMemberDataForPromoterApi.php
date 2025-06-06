<?php

declare(strict_types=1);

namespace App\Domains\Member\DataObjects;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Enums\Types;
use App\Domains\Member\MemberQueries;
use App\Models\Promoter;
use App\Rules\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class AddMemberDataForPromoterApi extends Data
{
    public function __construct(
        public int $type_id,
        public string $first_name,
        public ?string $last_name,
        public string $mobile_number,
        public ?string $card_number,
        public ?int $created_store_id,
        public ?int $created_location_id,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        /** @var Promoter $promoter */
        $promoter = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $memberQueries = resolve(MemberQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($promoter->employee_id);

        return [
            'type_id' => ['required', 'integer', 'in:'. Types::getValues()],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'mobile_number' => ['required', 'unique:members,mobile_number', new MobileNumber()],
            'created_store_id' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id')
                    ->where($locationQueries->filterByCompany($companyId)),
            ],
            'created_location_id' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id')
                    ->where($locationQueries->filterByCompany($companyId)),
            ],
            'card_number' => ['nullable', 'string', 'max:255', Rule::unique('members', 'card_number')
                    ->where($memberQueries->filterByCompany($companyId)), ],
        ];
    }
}
