<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\DataObjects;

use App\Domains\MemberGroup\Enums\DateConditionTypes;
use App\Domains\MemberGroup\Enums\GroupTypes;
use App\Domains\MemberGroup\Enums\NumberConditionTypes;
use App\Domains\MemberGroup\Enums\SmartGroupTypes;
use App\Domains\MemberGroup\MemberGroupQueries;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class MemberGroupData extends Data
{
    public function __construct(
        public string $name,
        public ?string $code,
        public int $type_id,
        public ?int $smart_group_type_id,
        public ?int $date_condition_type_id,
        public ?int $element_condition_type_id,
        public ?int $number_condition_type_id,
        public ?string $date,
        public ?string $max_date,
        public ?string $value,
        public ?string $max_value,
        public ?int $members_count,
        public ?array $product_ids,
        public ?array $category_ids,
        public ?UploadedFile $member_file = null,
        public ?UploadedFile $product_file = null,
    ) {
    }

    public static function rules(Request $request): array
    {
        $memberGroupId = null;
        $memberCount = 1;
        $productCount = 1;
        $memberGroupQueries = new MemberGroupQueries();
        $memberUploadFileValidation = true;
        $productUploadFileValidation = false;

        if ($request->type_id == GroupTypes::SMART_GROUP->value) {
            $memberUploadFileValidation = false;
            if ($request->smart_group_type_id === SmartGroupTypes::ITEM->value) {
                $productUploadFileValidation = true;
            }
        }

        if ('admin.member_groups.update' === $request->route()?->getName()) {
            /** @var int $memberGroupId */
            $memberGroupId = $request->route()->parameter('memberGroupId');

            if ($request->type_id === GroupTypes::MANUAL_GROUP->value) {
                $memberGroup = $memberGroupQueries->getByIdForImportRecord(
                    (int) $memberGroupId,
                    session('admin_company_id')
                );
                $memberCount = $memberGroup->memberGroupMembers->count();
            }

            if ($request->type_id === GroupTypes::SMART_GROUP->value && $request->smart_group_type_id === SmartGroupTypes::ITEM->value) {
                $memberGroup = $memberGroupQueries->getByIdForImportRecord(
                    $memberGroupId,
                    session('admin_company_id')
                );
                $productCount = $memberGroup->products->count();
            }

            $memberUploadFileValidation = ! (bool) $memberCount;
            $productUploadFileValidation = ! (bool) $productCount;
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('member_groups', 'name')->ignore($memberGroupId)
                    ->where($memberGroupQueries->filterByCompany(session('admin_company_id'))),
            ],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('member_groups', 'code')->ignore($memberGroupId)
                    ->where($memberGroupQueries->filterByCompany(session('admin_company_id'))),
            ],
            'type_id' => ['nullable', 'integer', 'in:' . GroupTypes::getValues()],
            'member_file' => [
                Rule::requiredIf($memberUploadFileValidation),
                'nullable',
                'file',
                'max:' . config('services.max_upload_size'),
            ],
            'smart_group_type_id' => [
                'nullable',
                'required_if:type_id,' . GroupTypes::SMART_GROUP->value,
                'integer',
            ],
            'category_ids' => [
                'nullable',
                'required_if:smart_group_type_id,' . SmartGroupTypes::CATEGORY->value,
                'array',
                Rule::exists('categories', 'id'),
            ],
            'product_file' => [
                Rule::requiredIf($productUploadFileValidation),
                'nullable',
                'file',
                'max:' . config('services.max_upload_size'),
            ],
            'date_condition_type_id' => [
                'nullable',
                'required_if:smart_group_type_id,' . SmartGroupTypes::PURCHASE_DATE->value,
                'required_if:smart_group_type_id,' . SmartGroupTypes::FIRST_VISIT_DATE->value,
                'required_if:smart_group_type_id,' . SmartGroupTypes::LAST_VISIT_DATE->value,
                'integer',
            ],
            'date' => [
                'nullable',
                'required_if:date_condition_type_id,' . DateConditionTypes::MORE_THAN->value,
                'required_if:date_condition_type_id,' . DateConditionTypes::LESS_THAN->value,
                'required_if:date_condition_type_id,' . DateConditionTypes::BETWEEN->value,
                'required_if:date_condition_type_id,in:' . DateConditionTypes::EXACTLY_ON->value,
                'date',
            ],
            'max_date' => [
                'nullable',
                'required_if:date_condition_type_id,' . DateConditionTypes::BETWEEN->value,
                'date',
            ],
            'element_condition_type_id' => [
                'nullable',
                'required_if:smart_group_type_id,' . SmartGroupTypes::CATEGORY->value,
                'required_if:smart_group_type_id,' . SmartGroupTypes::ITEM->value,
                'integer',
            ],
            'number_condition_type_id' => [
                'nullable',
                'required_if:smart_group_type_id,' . SmartGroupTypes::PURCHASE_COUNT->value,
                'required_if:smart_group_type_id,' . SmartGroupTypes::LIFETIME_SPENT->value,
                'integer',
            ],
            'value' => [
                'nullable',
                'required_if:number_condition_type_id,' . NumberConditionTypes::GREATER_THAN->value,
                'required_if:number_condition_type_id,' . NumberConditionTypes::LESS_THAN->value,
                'required_if:number_condition_type_id,' . NumberConditionTypes::BETWEEN->value,
                'required_if:number_condition_type_id,' . NumberConditionTypes::EXACTLY_TO->value,
                'numeric',
                'min:0.01',
            ],
            'max_value' => [
                'nullable',
                'required_if:number_condition_type_id,' . NumberConditionTypes::BETWEEN->value,
                'numeric',
                'min:0.01',
            ],
        ];
    }
}
