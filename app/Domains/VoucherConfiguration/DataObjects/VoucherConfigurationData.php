<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfiguration\DataObjects;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\VoucherConfiguration\Enums\ExcludeByTypes;
use App\Domains\VoucherConfiguration\Enums\RestrictedByTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Spatie\LaravelData\Data;

class VoucherConfigurationData extends Data
{
    public function __construct(
        public int $restricted_by_type,
        public int $voucher_type,
        public int $exclude_by_type,
        public ?float $issue_minimum_spend_amount,
        public float $use_minimum_spend_amount,
        public int $validity_days,
        public int $discount_type,
        public ?float $get_value,
        public string $start_date,
        public string $end_date,
        public ?array $category_ids,
        public ?array $product_ids,
        public ?array $tiers,
        public bool $dream_price_applicable,
        public bool $item_wise_promotion_applicable,
        public bool $cart_wide_promotion_applicable,
        public ?array $membership_ids,
        public ?string $redemption_foot_note,
        public ?string $handover_foot_note,
        public string $title,
        public ?string $description,
        public ?string $terms_and_conditions,
        public ?UploadedFile $image,
        public ?UploadedFile $thumbnail,
        public ?int $mystery_gift_id = null,
        public bool $is_available_in_ecommerce = false,
        public ?array $sale_channel_ids = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        return [
            'restricted_by_type' => ['required', 'integer', 'in:' . RestrictedByTypes::getValues()],
            'voucher_type' => ['required', 'integer', 'in:' . VoucherTypes::getValues()],
            'exclude_by_type' => ['required', 'integer', 'in:' . ExcludeByTypes::getValues()],
            'issue_minimum_spend_amount' => [
                'required_if:voucher_type,' . VoucherTypes::MULTIPLE_VOUCHER->value,
                'nullable',
                'numeric',
                self::validateIssueMinimumSpendAmount($request),
            ],
            'use_minimum_spend_amount' => ['required', 'numeric', 'min:0.01'],
            'validity_days' => ['required', 'integer', 'min:0.00'],
            'discount_type' => ['required', 'integer', 'in:' . DiscountTypes::getValues()],
            'get_value' => [
                'required_if:voucher_type,' . VoucherTypes::BIRTHDAY_VOUCHER->value,
                'required_if:voucher_type,' . VoucherTypes::MULTIPLE_VOUCHER->value,
                'nullable',
                'numeric',
                self::validatePercentage($request),
            ],
            'start_date' => ['required', 'date', 'max:255', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'max:255', 'after:start_date', 'date_format:Y-m-d'],
            'category_ids' => [
                'required_if:exclude_by_type,' . ExcludeByTypes::CATEGORIES->value,
                'nullable',
                'array',
            ],
            'category_ids.*' => ['required', 'integer'],
            'product_ids' => [
                'required_if:exclude_by_type,' . ExcludeByTypes::PRODUCTS->value,
                'nullable',
                'array',
            ],
            'product_ids.*' => ['required', 'integer'],
            'tiers' => ['required_if:voucher_type,' . VoucherTypes::TIER_VOUCHER->value, 'nullable', 'array'],
            'tiers.*.minimum_spend_amount' => ['required', 'numeric', 'min:0.01'],
            'tiers.*.maximum_spend_amount' => [
                'required_if:voucher_type,' . VoucherTypes::TIER_VOUCHER->value,
                'nullable',
                'numeric',
                'min:0.01',
                'gt:tiers.*.minimum_spend_amount',
            ],
            'tiers.*.get_value' => ['required', 'numeric', self::validatePercentage($request)],
            'dream_price_applicable' => ['required', 'boolean'],
            'item_wise_promotion_applicable' => ['required', 'boolean'],
            'cart_wide_promotion_applicable' => ['required', 'boolean'],
            'membership_ids' => [
                'required_if:voucher_type,' . VoucherTypes::LOYALTY_POINT->value,
                'nullable',
                'array',
            ],
            'membership_ids.*' => ['required', 'integer'],
            'redemption_foot_note' => ['nullable', 'string'],
            'handover_foot_note' => ['nullable', 'string'],
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'terms_and_conditions' => ['nullable', 'string'],
            'image' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/gif,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(343)->maxHeight(260)),
                'max:' . config('services.max_upload_size'),
            ],
            'thumbnail' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/gif,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(343)->maxHeight(72)),
                'max:' . config('services.max_upload_size'),
            ],
            'is_available_in_ecommerce' => ['required', 'boolean'],
            'sale_channel_ids' => ['required_if:is_available_in_ecommerce,true', 'nullable', 'array'],
            'sale_channel_ids.*' => ['integer'],
        ];
    }

    /**
     * @return mixed[]
     */
    private static function validateIssueMinimumSpendAmount(Request $request): array
    {
        if ($request->input('voucher_type') === VoucherTypes::MULTIPLE_VOUCHER->value) {
            return [
                'issue_minimum_spend_amount' => ['between:0.01,99999999.99'],
            ];
        }

        return [];
    }

    /**
     * @return mixed[]
     */
    private static function validatePercentage(Request $request): array
    {
        if ($request->input('voucher_type') === VoucherTypes::TIER_VOUCHER->value) {
            return [
                'tiers.*.get_value' => ['min:0.01', 'max:100'],
            ];
        }

        if (
            $request->input('voucher_type') === VoucherTypes::LOYALTY_POINT->value
            && $request->input('discount_type') === DiscountTypes::PERCENTAGE->value
        ) {
            return [
                'tiers.*.get_value' => ['min:0.01', 'max:100'],
            ];
        }

        if ((
            $request->input('voucher_type') === VoucherTypes::BIRTHDAY_VOUCHER->value ||
            $request->input('voucher_type') === VoucherTypes::MULTIPLE_VOUCHER->value
        ) &&
            $request->input('discount_type') === DiscountTypes::PERCENTAGE->value
        ) {
            return [
                'get_value' => ['min:0.01', 'max:100'],
            ];
        }

        return ['numeric', 'min:0.01', 'max:99999999.99'];
    }
}
