<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Promotion\DataObjects\PromotionData;
use App\Domains\Promotion\Enums\PromotionUsageTypes;
use App\Models\Company;
use App\Models\Location;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->companyAId = Company::factory()->create()->id;
    $this->companyBId = Company::factory()->create()->id;

    $this->promotionA = Promotion::factory()->create([
        'company_id' => $this->companyAId,
        'name' => 'ABC',
    ]);

    setCompanyIdInSession($this->companyAId);
});

test('admin cannot add same name with same company.', function (): void {
    $locationId = Location::factory()->create([
        'company_id' => $this->companyAId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $productId = Product::factory()->create([
        'company_id' => $this->companyAId,
    ])->id;

    $request = new Request([
        'name' => $this->promotionA->name,
        'location_ids' => [$locationId],
        'allow_registered_member' => true,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'regular_product_ids' => [$productId],
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_day' => null,
        'month_date' => null,
        'start_time' => null,
        'end_time' => null,
        'date' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => null,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 1,
        'timeframe_type_id' => 1,
        'percentage' => 50,
        'flat_amount' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
    ]);

    PromotionData::validate($request);
})->throws(ValidationException::class);

test('admin can add same name with different company.', function (): void {
    setCompanyIdInSession($this->companyBId);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyBId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $productId = Product::factory()->create([
        'company_id' => $this->companyBId,
    ])->id;

    $request = new Request([
        'name' => $this->promotionA->name,
        'location_ids' => [$locationId],
        'allow_registered_member' => true,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => [$productId],
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_day' => null,
        'month_date' => null,
        'start_time' => null,
        'end_time' => null,
        'date' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => null,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 1,
        'timeframe_type_id' => 1,
        'percentage' => 50,
        'flat_amount' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'is_membership_required' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
    ]);

    PromotionData::validate($request);
    $this->assertTrue(true);
});

test(
    'validation exception throw if regular product ids  & tiers is missing',
    function ($tiers, $productIds): void {
        $locationId = Location::factory()->create([
            'company_id' => $this->companyAId,
            'type_id' => LocationTypes::STORE->value,
        ])->id;

        $request = new Request([
            'name' => $this->promotionA->name,
            'location_ids' => [$locationId],
            'allow_registered_member' => true,
            'allow_employee' => false,
            'allow_walk_in_member' => false,
            'regular_product_ids' => $productIds,
            'buy_product_ids' => null,
            'get_product_ids' => null,
            'category_ids' => null,
            'start_date' => null,
            'end_date' => null,
            'week_day' => null,
            'month_date' => null,
            'start_time' => null,
            'end_time' => null,
            'date' => null,
            'promotion_applicable_type_id' => 2,
            'discount_type_id' => null,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => 6,
            'timeframe_type_id' => 1,
            'percentage' => null,
            'flat_amount' => null,
            'tiers' => $tiers,
        ]);

        PromotionData::validate($request);
    }
)->with([
    [
        [
            [
                'buy_value' => 1,
                'get_value' => 2,
            ],
        ],
        [],
    ],
    [[], [1]],
])->throws(ValidationException::class);

test('validation exception throw if weekly days is missing', function (): void {
    $locationId = Location::factory()->create([
        'company_id' => $this->companyAId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $request = new Request([
        'name' => $this->promotionA->name,
        'location_ids' => [$locationId],
        'allow_registered_member' => true,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'regular_product_ids' => null,
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_day' => [],
        'month_date' => null,
        'start_time' => null,
        'end_time' => null,
        'date' => null,
        'promotion_applicable_type_id' => 1,
        'discount_type_id' => 1,
        'cart_wide_promotion_type_id' => 1,
        'item_wise_promotion_type_id' => null,
        'timeframe_type_id' => 3,
        'percentage' => null,
        'flat_amount' => null,
        'tiers' => [
            [
                'buy_value' => 1,
                'get_value' => 2,
            ],
        ],
    ]);

    PromotionData::validate($request);
})->throws(ValidationException::class);
