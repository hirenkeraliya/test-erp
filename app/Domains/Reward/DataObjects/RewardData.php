<?php

declare(strict_types=1);

namespace App\Domains\Reward\DataObjects;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Reward\Enums\RewardTargetTypes;
use App\Domains\Reward\Enums\RewardTypes;
use App\Domains\Reward\RewardQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class RewardData extends Data
{
    public function __construct(
        public string $title,
        public int $type,
        public bool $status,
        public ?int $target_type = null,
        public ?int $discount_type = null,
        public ?float $discount = null,
        public ?float $loyalty_point = null,
        public ?float $minimum_point = null,
        public ?float $maximum_point = null,
        public ?array $brand_ids = [],
        public ?array $category_ids = [],
        public ?array $department_ids = [],
        public ?array $product_ids = [],
        public ?array $location_ids = [],
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $rewardId = null;
        $rewardQueries = new RewardQueries();

        if ('admin.rewards.update' === $request->route()?->getName()) {
            $rewardId = $request->route()->parameter('rewardId');
        }

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rewards', 'title')->ignore($rewardId)
                    ->where($rewardQueries->filterByCompany(session('admin_company_id'))),
            ],
            'status' => ['required', 'boolean'],
            'type' => ['required', 'integer', 'in:' . RewardTypes::getValues()],
            'target_type' => [
                'required_if:type,' . RewardTypes::FREE_ITEM->value,
                'nullable',
                'integer',
                'in:' . RewardTargetTypes::getValues()],
            'minimum_point' => [
                'required_if:type,' . RewardTypes::DISCOUNT_ON_ENTIRE_SALE->value,
                'nullable',
                'numeric',
                'min:0.00',
            ],
            'maximum_point' => [
                'required_if:type,' . RewardTypes::DISCOUNT_ON_ENTIRE_SALE->value,
                'nullable',
                'numeric',
                'gte:minimum_point',
                'min:0.00',
            ],
            'loyalty_point' => [
                'required_if:type,' . RewardTypes::FREE_ITEM->value,
                'nullable',
                'numeric',
                'min:0.00',
            ],
            'discount' => [
                'required_if:type,' . RewardTypes::DISCOUNT_ON_ENTIRE_SALE->value,
                'nullable',
                'numeric',
                'min:0.00',
            ],
            'discount_type' => [
                'required_if:type,' . RewardTypes::DISCOUNT_ON_ENTIRE_SALE->value,
                'nullable',
                'integer',
                'in:' . DiscountTypes::getValues()],
            'location_ids' => ['sometimes', 'nullable', 'array'],
            'location_ids.*' => ['required', 'integer'],
            'brand_ids' => ['required_if:target_type,' . RewardTargetTypes::BRANDS->value, 'nullable', 'array'],
            'brand_ids.*' => ['required', 'integer'],
            'category_ids' => [
                'required_if:target_type,' . RewardTargetTypes::CATEGORIES->value,
                'nullable',
                'array',
            ],
            'category_ids.*' => ['required', 'integer'],
            'department_ids' => [
                'required_if:target_type,' . RewardTargetTypes::DEPARTMENTS->value,
                'nullable',
                'array',
            ],
            'department_ids.*' => ['required', 'integer'],
            'product_ids' => [
                'required_if:target_type,' . RewardTargetTypes::PRODUCTS->value,
                'nullable',
                'array',
            ],
            'product_ids.*' => ['required', 'integer'],
        ];
    }
}
