<?php

declare(strict_types=1);

namespace App\Domains\ManualNotification\DataObjects;

use App\Domains\ManualNotification\Enums\ManualNotificationTypes;
use App\Domains\ManualNotification\Enums\MembersFilter;
use App\Domains\ManualNotification\Enums\PromotersFilter;
use Spatie\LaravelData\Data;

class ManualNotificationData extends Data
{
    public function __construct(
        public string $title,
        public string $message,
        public int $notification_type,
        public ?int $promoter_filter_type = null,
        public ?int $member_filter_type = null,
        public ?array $location_ids = null,
        public ?array $promoter_ids = null,
        public ?array $promoter_group_ids = null,
        public ?array $member_group_ids = null,
        public ?array $member_type_ids = null,
        public ?array $member_ids = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'message' => ['required', 'string'],
            'notification_type' => ['required', 'integer', 'in:' . ManualNotificationTypes::getValues()],
            'promoter_filter_type' => [
                'required_without:member_filter_type',
                'integer',
                'nullable',
                'in:' . PromotersFilter::getValues(),
            ],
            'member_filter_type' => [
                'required_without:promoter_filter_type',
                'integer',
                'nullable',
                'in:' . MembersFilter::getValues(),
            ],
            'location_ids' => [
                'required_if:promoter_filter_type,' . PromotersFilter::LOCATIONS->value,
                'required_if:member_filter_type,' . MembersFilter::STORES->value,
                'nullable',
                'array',
            ],
            'location_ids.*' => ['required', 'integer'],
            'promoter_ids' => [
                'required_if:promoter_filter_type,' . PromotersFilter::PROMOTERS->value,
                'nullable',
                'array',
            ],
            'promoter_ids.*' => ['required', 'integer'],
            'promoter_group_ids' => [
                'required_if:promoter_filter_type,' . PromotersFilter::GROUPS->value,
                'nullable',
                'array',
            ],
            'promoter_group_ids.*' => ['required', 'integer'],
            'member_group_ids' => [
                'required_if:member_filter_type,' . MembersFilter::GROUPS->value,
                'nullable',
                'array',
            ],
            'member_group_ids.*' => ['required', 'integer'],
            'member_type_ids' => [
                'required_if:member_filter_type,' . MembersFilter::TYPES->value,
                'nullable',
                'array',
            ],
            'member_type_ids.*' => ['required', 'integer'],
            'member_ids' => [
                'required_if:member_filter_type,' . MembersFilter::MEMBERS->value,
                'nullable',
                'array',
            ],
            'member_ids.*' => ['required', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'promoter_ids.required_if' => 'The promoter ids field is required when filter type is '.PromotersFilter::getFormattedCaseName(
                PromotersFilter::PROMOTERS->value
            ),
            'promoter_group_ids.required_if' => 'The promoter group ids field is required when filter type is '.PromotersFilter::getFormattedCaseName(
                PromotersFilter::GROUPS->value
            ),
            'location.required_if' => 'The location ids field is required when filter type is '.MembersFilter::getFormattedCaseName(
                MembersFilter::STORES->value
            ),
            'member_group_ids.required_if' => 'The member group ids field is required when filter type is '.MembersFilter::getFormattedCaseName(
                MembersFilter::GROUPS->value
            ),
            'member_type_ids.required_if' => 'The member types field is required when filter type is '.MembersFilter::getFormattedCaseName(
                MembersFilter::TYPES->value
            ),
            'member_ids.required_if' => 'The member field is required when filter type is '.MembersFilter::getFormattedCaseName(
                MembersFilter::MEMBERS->value
            ),
            'member_filter_type.required_without' => 'Filter type field is required.',
            'promoter_filter_type.required_without' => 'Filter type field is required.',
        ];
    }
}
