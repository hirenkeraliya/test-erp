<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyCampaign\DataObjects;

use App\Domains\LoyaltyCampaign\LoyaltyCampaignQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class LoyaltyCampaignData extends Data
{
    public function __construct(
        public string $name,
        public float $minimum_spend_amount,
        public int $loyalty_points,
        public string $start_date,
        public string $end_date,
        public int $loyalty_point_expiration_days,
        public ?array $excluded_brand_ids = [],
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $loyaltyCampaignId = null;
        $loyaltyCampaignQueries = new LoyaltyCampaignQueries();

        if ('admin.loyalty_campaigns.update' === $request->route()?->getName()) {
            $loyaltyCampaignId = $request->route()->parameter('loyaltyCampaignId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('loyalty_campaigns', 'name')->ignore($loyaltyCampaignId)
                    ->where($loyaltyCampaignQueries->filterByCompany(session('admin_company_id'))),
            ],
            'minimum_spend_amount' => ['required', 'numeric', 'min:0.01'],
            'loyalty_points' => ['required', 'integer', 'min:1'],
            'loyalty_point_expiration_days' => ['required', 'integer', 'min:0'],
            'excluded_brand_ids' => ['sometimes', 'nullable', 'array'],
            'excluded_brand_ids.*' => ['required', 'integer'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'after:start_date', 'date_format:Y-m-d'],
        ];
    }
}
