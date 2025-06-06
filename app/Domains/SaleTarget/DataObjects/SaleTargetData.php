<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\DataObjects;

use App\Domains\SaleTarget\Enums\SaleTargetAmountTypes;
use App\Domains\SaleTarget\Enums\SaleTargetPromoterTypes;
use App\Domains\SaleTarget\Enums\SaleTargetStoreTypes;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\SaleTargetQueries;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class SaleTargetData extends Data
{
    public function __construct(
        public string $name,
        public ?float $amount,
        public ?float $percentage,
        public int $amount_type,
        public int $target_type,
        public int $time_interval_type,
        public bool $status,
        public ?array $location_ids,
        public ?array $promoter_ids,
        public ?array $dates,
        public int $store_type,
        public int $promoter_type,
        public ?UploadedFile $upload_stores,
        public ?UploadedFile $upload_promoters,
        public ?array $month_tiers = null,
        public ?array $week_tiers = null,
        public ?int $year = null,
    ) {
    }

    public static function rules(Request $request): array
    {
        $saleTargetId = null;
        $saleTargetQueries = new SaleTargetQueries();

        if ('admin.sale_targets.update' === $request->route()?->getName()) {
            $saleTargetId = $request->route()->parameter('saleTargetId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sale_targets', 'name')->ignore($saleTargetId)
                    ->where($saleTargetQueries->filterByCompany(session('admin_company_id'))),
            ],
            'amount_type' => ['required', 'integer', 'in:' . SaleTargetAmountTypes::getValues()],
            'amount' => [
                Rule::requiredIf(
                    fn (): bool => $request->input('amount_type') === SaleTargetAmountTypes::AMOUNT->value
                ),
                'nullable',
                'numeric',
                'between:0,99999999.99',
            ],
            'percentage' => [
                Rule::requiredIf(
                    fn (): bool => $request->input('amount_type') === SaleTargetAmountTypes::PERCENTAGE->value
                ),
                'nullable',
                'numeric',
                'between:0,100',
            ],
            'target_type' => ['required', 'integer', 'in:' . TargetType::getValues()],
            'time_interval_type' => ['required', 'integer', 'in:' . TimeIntervalType::getValues()],
            'location_ids' => [
                Rule::requiredIf(fn (): bool => $request->input('target_type') === TargetType::STORE_WISE->value),
                'nullable',
                'array',
            ],
            'location_ids.*' => ['required', 'integer'],
            'promoter_ids' => [
                Rule::requiredIf(
                    fn (): bool => $request->input('target_type') === TargetType::PROMOTER_WISE->value
                ),
                'nullable',
                'array',
            ],
            'promoter_ids.*' => ['required', 'integer'],
            'dates' => [
                Rule::requiredIf(
                    fn (): bool => $request->input('time_interval_type') === TimeIntervalType::DAILY->value
                    || $request->input('time_interval_type') === TimeIntervalType::CUSTOM_PERIOD->value
                ),
                'nullable',
                'array',
            ],
            'month_tiers' => [
                Rule::requiredIf(
                    fn (): bool => $request->input('time_interval_type') === TimeIntervalType::MONTHLY->value
                ),
                'nullable',
                'array',
            ],
            'month_tiers.*.months' => ['required', 'array'],
            'week_tiers' => [
                Rule::requiredIf(
                    fn (): bool => $request->input('time_interval_type') === TimeIntervalType::WEEKLY->value
                ),
                'nullable',
                'array',
            ],
            'week_tiers.*.weeks' => ['required', 'array'],
            'week_tiers.*.amount' => [
                'required_if:amount_type,' . SaleTargetAmountTypes::AMOUNT->value,
                'numeric',
                'between:0,99999999.99',
            ],
            'week_tiers.*.percentage' => [
                'required_if:amount_type,' . SaleTargetAmountTypes::PERCENTAGE->value,
                'numeric',
                'between:0,100',
            ],
            'year' => [
                Rule::requiredIf(
                    fn (): bool => $request->input('time_interval_type') === TimeIntervalType::YEARLY->value
                ),
                'nullable',
                'numeric',
            ],
            'store_type' => ['required', 'integer', 'in:' . SaleTargetStoreTypes::getValues()],
            'upload_stores' => [
                'required_if:store_type,' . SaleTargetStoreTypes::UPLOAD->value,
                'nullable',
                'file',
                'max:' . config('services.max_upload_size'),
            ],
            'promoter_type' => ['required', 'integer', 'in:' . SaleTargetPromoterTypes::getValues()],
            'upload_promoters' => [
                'required_if:promoter_type,' . SaleTargetPromoterTypes::UPLOAD->value,
                'nullable',
                'file',
                'max:' . config('services.max_upload_size'),
            ],
        ];
    }
}
