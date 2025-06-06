<?php

declare(strict_types=1);

namespace App\Domains\Membership\DataObjects;

use App\Domains\Membership\MembershipQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class MembershipData extends Data
{
    public function __construct(
        public string $name,
        public float $lifetime_value,
        public int $loyalty_points_per_currency_unit,
        public int $min_loyalty_points_for_redemption,
        public int $max_loyalty_points_for_redemption,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $membershipId = null;
        $membershipQueries = new MembershipQueries();

        if ('admin.memberships.update' === $request->route()?->getName()) {
            $membershipId = $request->route()->parameter('membershipId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('memberships', 'name')->ignore($membershipId)
                    ->where($membershipQueries->filterByCompany(session('admin_company_id'))),
            ],
            'lifetime_value' => ['required', 'numeric', 'min:0'],
            'loyalty_points_per_currency_unit' => ['required', 'integer', 'min:1'],
            'min_loyalty_points_for_redemption' => ['required', 'integer', 'min:0'],
            'max_loyalty_points_for_redemption' => ['required', 'integer', 'gt:min_loyalty_points_for_redemption'],
        ];
    }
}
