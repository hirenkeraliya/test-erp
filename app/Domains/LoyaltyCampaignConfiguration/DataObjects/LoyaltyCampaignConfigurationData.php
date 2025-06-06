<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyCampaignConfiguration\DataObjects;

use App\Domains\LoyaltyCampaignConfiguration\Enums\ExpirationTypes;
use App\Domains\LoyaltyCampaignConfiguration\Enums\LoyaltyCampaignTypes;
use App\Domains\LoyaltyCampaignConfiguration\LoyaltyCampaignConfigurationQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class LoyaltyCampaignConfigurationData extends Data
{
    public function __construct(
        public string $description,
        public int $loyalty_campaign_type,
        public int $point_earned,
        public float $minimum_purchase_amount,
        public int $expiration_type,
        public bool $include_tax,
        public bool $status,
        public ?array $brand_ids = [],
        public ?array $location_ids = [],
        public ?array $category_ids = [],
        public ?array $product_ids = [],
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $loyaltyCampaignConfigurationId = null;
        $loyaltyCampaignConfigurationQueries = new LoyaltyCampaignConfigurationQueries();

        if ('admin.loyalty_campaign_configurations.update' === $request->route()?->getName()) {
            $loyaltyCampaignConfigurationId = $request->route()->parameter('loyaltyCampaignConfigurationId');
        }

        return [
            'description' => [
                'required',
                'string',
                'max:255',
                Rule::unique('loyalty_campaign_configurations', 'description')->ignore($loyaltyCampaignConfigurationId)
                    ->where($loyaltyCampaignConfigurationQueries->filterByCompany(session('admin_company_id'))),
            ],
            'loyalty_campaign_type' => ['required', 'integer', 'in:' . LoyaltyCampaignTypes::getValues()],
            'expiration_type' => ['required', 'integer', 'in:' . ExpirationTypes::getValues()],
            'minimum_purchase_amount' => ['required', 'numeric', 'min:0.00'],
            'point_earned' => ['required', 'integer', 'min:1'],
            'include_tax' => ['required', 'boolean'],
            'status' => ['required', 'boolean'],
            'location_ids' => ['sometimes', 'nullable', 'array'],
            'location_ids.*' => ['required', 'integer'],
            'brand_ids' => [
                'required_if:loyalty_campaign_type,' . LoyaltyCampaignTypes::PRODUCT_BRANDS->value,
                'nullable',
                'array',
            ],
            'brand_ids.*' => ['required', 'integer'],
            'category_ids' => [
                'required_if:loyalty_campaign_type,' . LoyaltyCampaignTypes::PRODUCT_CATEGORIES->value,
                'nullable',
                'array',
            ],
            'category_ids.*' => ['required', 'integer'],
            'product_ids' => [
                'required_if:loyalty_campaign_type,' . LoyaltyCampaignTypes::SPECIFIC_PRODUCTS->value,
                'nullable',
                'array',
            ],
            'product_ids.*' => ['required', 'integer'],
        ];
    }
}
