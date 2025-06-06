<?php

declare(strict_types=1);

namespace App\Domains\MysteryGift\DataObjects;

use App\Domains\MysteryGift\Enums\Statuses;
use App\Domains\MysteryGift\MysteryGiftQueries;
use App\Models\MysteryGift;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class MysteryGiftData extends Data
{
    public function __construct(
        public string $name,
        public string $start_date,
        public string $end_date,
        public ?float $max_flat_amount,
        public ?float $max_percentage,
        public ?array $uploaded_products,
        public ?float $minimum_spend,
        public ?float $minimum_spend_amount_for_flat_amount = 0,
        public ?float $minimum_spend_amount_for_percentage = 0,
        public ?float $minimum_spend_amount_for_free_product = 0,
        public ?bool $is_flat_amount = false,
        public ?bool $is_percentage = false,
        public ?bool $is_free_product = false,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $mysteryGiftId = null;
        $mysteryGiftQueries = new MysteryGiftQueries();

        if ('admin.mystery_gifts.update' === $request->route()?->getName()) {
            $mysteryGiftId = $request->route()->parameter('mysteryGiftId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mystery_gifts', 'name')->ignore($mysteryGiftId)
                    ->where($mysteryGiftQueries->filterByCompany(session('admin_company_id'))),
            ],
            'is_flat_amount' => [
                'required',
                'boolean',
                function ($attribute, $value, $fail) use ($request): void {
                    if (! self::atLeastOneSelected($request)) {
                        $fail('At least one of the options (Flat Amount, Percentage, or Free Product) must be selected.');
                    }
                },
            ],
            'is_percentage' => ['required', 'boolean'],
            'is_free_product' => ['required', 'boolean'],
            'max_flat_amount' => [
                'sometimes',
                'nullable',
                'required_if:is_flat_amount,true',
                'numeric',
                'min:0.01',
            ],
            'max_percentage' => [
                'sometimes',
                'nullable',
                'required_if:is_percentage,true',
                'numeric',
                'max:100',
                'min:0.01',
            ],
            'start_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                function ($attribute, $value, $fail) use ($request): void {
                    if (self::isDateRangeOverlapping($request)) {
                        $fail('The selected date range overlaps with an existing promotion.');
                    }
                },
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
                'date_format:Y-m-d',
                function ($attribute, $value, $fail) use ($request): void {
                    if (self::isDateRangeOverlapping($request)) {
                        $fail('The selected date range overlaps with an existing promotion.');
                    }
                },
            ],
            'uploaded_products' => ['required_if:is_free_product,true', 'nullable', 'array'],
            'uploaded_products.*.id' => ['required ', ' integer '],
            'uploaded_products.*.quantity' => ['required', 'numeric', 'min:0'],
            'minimum_spend' => ['required', 'numeric', 'min:0.00'],
            'minimum_spend_amount_for_flat_amount' => [
                'sometimes',
                'nullable',
                'required_if:is_flat_amount,true',
                'numeric',
                'min:0.00',
            ],
            'minimum_spend_amount_for_percentage' => [
                'sometimes',
                'nullable',
                'required_if:is_percentage,true',
                'numeric',
                'min:0.00',
            ],
            'minimum_spend_amount_for_free_product' => [
                'sometimes',
                'nullable',
                'required_if:is_free_product,true',
                'numeric',
                'min:0.00',
            ],
        ];
    }

    private static function isDateRangeOverlapping(Request $request): bool
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $mysteryGiftId = null;
        if ('admin.mystery_gifts.update' === $request->route()?->getName()) {
            $mysteryGiftId = $request->route()->parameter('mysteryGiftId');
        }

        $companyId = session('admin_company_id');

        return MysteryGift::query()
            ->select('id', 'start_date', 'end_date')
            ->where('company_id', $companyId)
            ->where('status', Statuses::ACTIVE->value)
            ->where(function ($query) use ($startDate, $endDate): void {
                $query->where('start_date', '<=', $endDate)
                    ->where('end_date', '>=', $startDate);
            })
            ->when($mysteryGiftId, function ($query) use ($mysteryGiftId): void {
                $query->where('id', '!=', $mysteryGiftId);
            })
            ->exists();
    }

    private static function atLeastOneSelected(Request $request): bool
    {
        if ($request->input('is_flat_amount')) {
            return true;
        }

        if ($request->input('is_percentage')) {
            return true;
        }

        return (bool) $request->input('is_free_product');
    }
}
