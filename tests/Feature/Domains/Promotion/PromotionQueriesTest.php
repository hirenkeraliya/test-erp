<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Promotion\Enums\ProductUploadTypes;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Domains\Promotion\Enums\PromotionTimeframeTypes;
use App\Domains\Promotion\Enums\PromotionUsageTypes;
use App\Domains\Promotion\PromotionQueries;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Company;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionMonthDate;
use App\Models\PromotionPromoCode;
use App\Models\PromotionTier;
use App\Models\PromotionWeekDay;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->promotionA = Promotion::factory()->create([
        'name' => 'ABCD',
        'company_id' => $this->companyId,
        'status' => true,
    ]);

    $this->promotionB = Promotion::factory()->create([
        'name' => 'EFGH',
        'company_id' => $this->companyId,
        'status' => false,
    ]);

    $this->promotionQueries = new PromotionQueries();
});

test('Promotions can be searched', function (): void {
    $response = $this->promotionQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'location_ids' => null,
        'promotion_type' => null,
        'status_value' => null,
        'promotion_user_restriction_type' => null,
        'id' => null,
        'availability_type' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $this->promotionA->id)
        ->toHaveKey('name', $this->promotionA->name);
});

test('new promotion can be added', function (): void {
    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $productId = Product::factory()->create([
        'company_id' => $this->companyId,
    ])->id;

    $admin = Admin::factory()->create();

    $promotionData = getPromotionBasicDetails(
        $locationId,
        $productId,
        50,
        PromotionApplicableTypes::ITEM_WISE->value,
        DiscountTypes::PERCENTAGE->value,
        null,
        ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value,
        PromotionTimeframeTypes::NO_LIMIT->value
    );

    $this->promotionQueries->addNew($promotionData, $this->companyId, $admin);

    $this->assertDatabaseHas('promotions', [
        'name' => $promotionData['name'],
        'company_id' => $this->companyId,
        'promotion_applicable_type_id' => $promotionData['promotion_applicable_type_id'],
        'discount_type_id' => $promotionData['discount_type_id'],
        'cart_wide_promotion_type_id' => $promotionData['cart_wide_promotion_type_id'],
        'timeframe_type_id' => $promotionData['timeframe_type_id'],
        'percentage' => $promotionData['percentage'],
    ]);

    $this->assertDatabaseHas('location_promotion', [
        'location_id' => $locationId,
    ]);

    $this->assertDatabaseHas('product_promotion', [
        'product_id' => $productId,
        'type' => ProductUploadTypes::REGULAR->value,
    ]);
});

test('it changes the status of the promotions', function (): void {
    $this->promotionQueries->setStatus($this->promotionA->id, $this->companyId, false);
    $this->promotionQueries->setStatus($this->promotionB->id, $this->companyId, true);

    $this->assertDatabaseHas('promotions', [
        'id' => $this->promotionA->id,
        'status' => false,
    ]);

    $this->assertDatabaseHas('promotions', [
        'id' => $this->promotionB->id,
        'status' => true,
    ]);
});

test('A promotion can be fetched with all related models when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $response = $this->promotionQueries->getByIdWithRelations($this->promotionA->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('name', $this->promotionA->name)
        ->toHaveKey('promotion_applicable_type_id', $this->promotionA->promotion_applicable_type_id)
        ->toHaveKey('discount_type_id', $this->promotionA->discount_type_id)
        ->toHaveKey('cart_wide_promotion_type_id', $this->promotionA->cart_wide_promotion_type_id)
        ->toHaveKey('item_wise_promotion_type_id', $this->promotionA->item_wise_promotion_type_id)
        ->toHaveKey('timeframe_type_id', $this->promotionA->timeframe_type_id)
        ->toHaveKey('percentage', $this->promotionA->percentage)
        ->toHaveKey('flat_amount', $this->promotionA->flat_amount)
        ->toHaveKey('start_date', $this->promotionA->start_date)
        ->toHaveKey('end_date', $this->promotionA->end_date)
        ->toHaveKey('start_time', $this->promotionA->start_time)
        ->toHaveKey('end_time', $this->promotionA->end_time)
        ->toHaveKey('allow_registered_member', $this->promotionA->allow_registered_member)
        ->toHaveKeys(
            ['regular_products', 'buy_products', 'get_products', 'categories', 'monthly', 'promotion_tiers', 'weekly']
        );
});

test('A promotion can be fetched with all related models when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyId,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $promotion = Promotion::factory()->create([
        'name' => 'ABCDEFG',
        'company_id' => $this->companyId,
        'status' => true,
    ]);

    $productId = Product::factory()->create([
        'company_id' => $this->companyId,
        'master_product_id' => $masterProduct->id,
    ])->id;

    $promotion->regularProducts()->attach([$productId]);

    $response = $this->promotionQueries->getByIdWithRelations($this->promotionA->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('name', $this->promotionA->name)
        ->toHaveKey('promotion_applicable_type_id', $this->promotionA->promotion_applicable_type_id)
        ->toHaveKey('discount_type_id', $this->promotionA->discount_type_id)
        ->toHaveKey('cart_wide_promotion_type_id', $this->promotionA->cart_wide_promotion_type_id)
        ->toHaveKey('item_wise_promotion_type_id', $this->promotionA->item_wise_promotion_type_id)
        ->toHaveKey('timeframe_type_id', $this->promotionA->timeframe_type_id)
        ->toHaveKey('percentage', $this->promotionA->percentage)
        ->toHaveKey('flat_amount', $this->promotionA->flat_amount)
        ->toHaveKey('start_date', $this->promotionA->start_date)
        ->toHaveKey('end_date', $this->promotionA->end_date)
        ->toHaveKey('start_time', $this->promotionA->start_time)
        ->toHaveKey('end_time', $this->promotionA->end_time)
        ->toHaveKey('allow_registered_member', $this->promotionA->allow_registered_member)
        ->toHaveKeys(
            ['regular_products', 'buy_products', 'get_products', 'categories', 'monthly', 'promotion_tiers', 'weekly']
        );
});

test('A promotion can be fetched with all related models for clone promotion', function (): void {
    $response = $this->promotionQueries->getByIdForClone($this->promotionA->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('name', $this->promotionA->name)
        ->toHaveKey('promotion_applicable_type_id', $this->promotionA->promotion_applicable_type_id)
        ->toHaveKey('discount_type_id', $this->promotionA->discount_type_id)
        ->toHaveKey('cart_wide_promotion_type_id', $this->promotionA->cart_wide_promotion_type_id)
        ->toHaveKey('item_wise_promotion_type_id', $this->promotionA->item_wise_promotion_type_id)
        ->toHaveKey('timeframe_type_id', $this->promotionA->timeframe_type_id)
        ->toHaveKey('percentage', $this->promotionA->percentage)
        ->toHaveKey('flat_amount', $this->promotionA->flat_amount)
        ->toHaveKey('start_date', $this->promotionA->start_date)
        ->toHaveKey('end_date', $this->promotionA->end_date)
        ->toHaveKey('start_time', $this->promotionA->start_time)
        ->toHaveKey('end_time', $this->promotionA->end_time)
        ->toHaveKey('allow_registered_member', $this->promotionA->allow_registered_member)
        ->toHaveKeys(
            [
                'regular_products',
                'buy_products',
                'get_products',
                'categories',
                'monthly',
                'promotion_tiers',
                'weekly',
                'locations',
                'product_collections',
            ]
        );
});

test('A promotion can be fetched', function (): void {
    $response = $this->promotionQueries->getById($this->promotionA->id, $this->companyId);

    expect($response->toArray())
        ->toHaveKey('name', $this->promotionA->name)
        ->toHaveKey('promotion_applicable_type_id', $this->promotionA->promotion_applicable_type_id)
        ->toHaveKey('discount_type_id', $this->promotionA->discount_type_id)
        ->toHaveKey('cart_wide_promotion_type_id', $this->promotionA->cart_wide_promotion_type_id)
        ->toHaveKey('item_wise_promotion_type_id', $this->promotionA->item_wise_promotion_type_id);
});

test('it can updates the specified promotion', function (): void {
    $promotion = Promotion::factory()->create([
        'company_id' => $this->companyId,
        'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
        'discount_type_id' => DiscountTypes::PERCENTAGE->value,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value,
        'timeframe_type_id' => PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
        'percentage' => null,
        'flat_amount' => null,
        'start_date' => null,
        'end_date' => null,
        'start_time' => null,
        'end_time' => null,
        'allow_registered_member' => false,
        'status' => true,
    ]);

    PromotionWeekDay::factory()->create([
        'promotion_id' => $promotion->id,
        'week_day' => 2,
    ]);

    $productId = Product::factory()->create([
        'company_id' => $this->companyId,
    ])->id;

    $promotionData = getPromotionBasicDetails(
        null,
        $productId,
        50,
        PromotionApplicableTypes::ITEM_WISE->value,
        DiscountTypes::PERCENTAGE->value,
        null,
        ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value,
        PromotionTimeframeTypes::NO_LIMIT->value
    );

    $this->promotionQueries->update($promotionData, $promotion);

    $this->assertDatabaseHas('promotions', [
        'name' => $promotionData['name'],
        'company_id' => $this->companyId,
        'promotion_applicable_type_id' => $promotionData['promotion_applicable_type_id'],
        'discount_type_id' => $promotionData['discount_type_id'],
        'cart_wide_promotion_type_id' => $promotionData['cart_wide_promotion_type_id'],
        'timeframe_type_id' => $promotionData['timeframe_type_id'],
        'percentage' => $promotionData['percentage'],
    ]);

    $this->assertDatabaseHas('product_promotion', [
        'product_id' => $productId,
        'promotion_id' => $promotion->id,
        'type' => ProductUploadTypes::REGULAR->value,
    ]);

    $this->assertDatabaseMissing('promotion_week_days', [
        'promotion_id' => $promotion->id,
        'week_day' => 2,
    ]);
});

test('the relation records cleared before update the promotion', function (): void {
    $productOne = Product::factory()->create([
        'company_id' => $this->companyId,
        'upc' => (string) Str::uuid(),
    ]);

    $productTwo = Product::factory()->create([
        'company_id' => $this->companyId,
        'upc' => (string) Str::uuid(),
    ]);

    $category = Category::factory()->create([
        'company_id' => $this->companyId,
    ]);

    PromotionTier::factory()->create([
        'promotion_id' => $this->promotionB->id,
        'buy_value' => 1,
        'get_value' => 1,
    ]);

    PromotionMonthDate::factory()->create([
        'promotion_id' => $this->promotionB->id,
        'month_date' => 1,
    ]);

    PromotionWeekDay::factory()->create([
        'promotion_id' => $this->promotionB->id,
        'week_day' => 1,
    ]);

    $this->promotionB->categories()->sync([$category->id]);

    $this->promotionB->uploadedProducts()->syncWithPivotValues(
        [$productTwo->id],
        [
            'type' => ProductUploadTypes::REGULAR->value,
        ]
    );

    $this->promotionB->uploadedProducts()->syncWithPivotValues(
        [$productOne->id],
        [
            'type' => ProductUploadTypes::BUY_PRODUCT->value,
        ]
    );

    $this->promotionB->uploadedProducts()->syncWithPivotValues(
        [$productOne->id],
        [
            'type' => ProductUploadTypes::GET_PRODUCT->value,
        ]
    );

    $promotionData = getPromotionBasicDetails(
        null,
        $productOne->id,
        50,
        PromotionApplicableTypes::ITEM_WISE->value,
        DiscountTypes::PERCENTAGE->value,
        null,
        ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value,
        PromotionTimeframeTypes::NO_LIMIT->value
    );

    $this->promotionQueries->update($promotionData, $this->promotionB);

    $this->assertDatabaseMissing('promotion_tiers', [
        'promotion_id' => $this->promotionB->id,
        'buy_value' => 1,
        'get_value' => 1,
    ]);

    $this->assertDatabaseMissing('promotion_month_dates', [
        'promotion_id' => $this->promotionB->id,
        'month_date' => 1,
    ]);

    $this->assertDatabaseMissing('promotion_week_days', [
        'promotion_id' => $this->promotionB->id,
        'week_day' => 1,
    ]);

    $this->assertDatabaseMissing('category_promotion', [
        'category_id' => $category->id,
        'promotion_id' => $this->promotionB->id,
    ]);
});

test(
    'getListForPosAsPerTimeFrameWithRelatedData method returns the promotions list with related data as expected',
    function (): void {
        $promotion = Promotion::factory()->create([
            'company_id' => $this->companyId,
            'timeframe_type_id' => PromotionTimeframeTypes::LIMITED_BY_DATES->value,
            'status' => true,
        ]);

        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $promotion->locations()->sync($location->id);

        $promotion->uploadedProducts()->syncWithPivotValues(
            [$product->id],
            [
                'type' => ProductUploadTypes::REGULAR->value,
            ]
        );

        $response = $this->promotionQueries->getListForPosAsPerTimeFrameWithRelatedData($location);

        expect($response)->toBeInstanceOf(Collection::class);

        $promotionsDetail = $response->toArray();

        expect($promotionsDetail[0])
        ->toHaveKey('name', $promotion->name)
        ->toHaveKey('promotion_applicable_type_id', $promotion->promotion_applicable_type_id)
        ->toHaveKey('discount_type_id', $promotion->discount_type_id)
        ->toHaveKey('cart_wide_promotion_type_id', $promotion->cart_wide_promotion_type_id)
        ->toHaveKey('item_wise_promotion_type_id', $promotion->item_wise_promotion_type_id)
        ->toHaveKey('timeframe_type_id', $promotion->timeframe_type_id)
        ->toHaveKey('percentage', $promotion->percentage)
        ->toHaveKey('flat_amount', $promotion->flat_amount)
        ->toHaveKey('start_date', $promotion->start_date)
        ->toHaveKey('end_date', $promotion->end_date)
        ->toHaveKey('start_time', $promotion->start_time)
        ->toHaveKey('end_time', $promotion->end_time)
        ->toHaveKey('allow_registered_member', $promotion->allow_registered_member)
        ->toHaveKeys(
            ['regular_products', 'buy_products', 'get_products', 'categories', 'monthly', 'promotion_tiers', 'weekly']
        );
    }
);

test(
    'getListForPosAsPerTimeFrameWithRelatedDataAndManualPromotionOnly method returns the manual promotions list with related data as expected',
    function (): void {
        $promotion = Promotion::factory()->create([
            'company_id' => $this->companyId,
            'timeframe_type_id' => PromotionTimeframeTypes::LIMITED_BY_DATES->value,
            'status' => true,
            'is_automatic' => false,
        ]);

        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $promotionPromoCode = PromotionPromoCode::factory()->create([
            'promotion_id' => $promotion->getKey(),
        ]);

        $promotion->locations()->sync($location->id);

        $promotion->uploadedProducts()->syncWithPivotValues(
            [$product->id],
            [
                'type' => ProductUploadTypes::REGULAR->value,
            ]
        );

        $promotion->promotionPromoCodes = $promotionPromoCode;

        $filterData = [
            'per_page' => 10,
            'search_text' => null,
            'after_updated_at' => null,
        ];

        $response = $this->promotionQueries->getListForPosAsPerTimeFrameWithRelatedDataAndManualPromotionOnly(
            $location,
            $filterData
        );

        expect($response)->toBeInstanceOf(LengthAwarePaginator::class);

        $promotionsDetail = $response->getCollection()->toArray();

        expect($promotionsDetail[0])
            ->toHaveKey('name', $promotion->name)
            ->toHaveKey('promotion_applicable_type_id', $promotion->promotion_applicable_type_id)
            ->toHaveKey('discount_type_id', $promotion->discount_type_id)
            ->toHaveKey('cart_wide_promotion_type_id', $promotion->cart_wide_promotion_type_id)
            ->toHaveKey('item_wise_promotion_type_id', $promotion->item_wise_promotion_type_id)
            ->toHaveKey('timeframe_type_id', $promotion->timeframe_type_id)
            ->toHaveKey('percentage', $promotion->percentage)
            ->toHaveKey('flat_amount', $promotion->flat_amount)
            ->toHaveKey('start_date', $promotion->start_date)
            ->toHaveKey('end_date', $promotion->end_date)
            ->toHaveKey('start_time', $promotion->start_time)
            ->toHaveKey('end_time', $promotion->end_time)
            ->toHaveKey('allow_registered_member', $promotion->allow_registered_member)
            ->toHaveKeys(
                [
                    'regular_products',
                    'buy_products',
                    'get_products',
                    'categories',
                    'monthly',
                    'promotion_tiers',
                    'weekly',
                    'promotion_promo_codes',
                ]
            );
    }
);

test('getByIdsWithRelations method returns promotion with all related models', function (): void {
    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $this->promotionA->locations()->attach($locationId);

    $response = $this->promotionQueries->getByIdsWithRelations([$this->promotionA->id], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->promotionA->name)
        ->toHaveKeys(
            [
                'regular_products',
                'buy_products',
                'get_products',
                'categories',
                'monthly',
                'promotion_tiers',
                'weekly',
                'locations',
            ]
        );
});

test('removeSelectedProducts method detach the selected products', function (): void {
    $promotion = Promotion::factory()->create([
        'name' => 'ABCDEFG',
        'company_id' => $this->companyId,
        'status' => true,
    ]);

    $productId = Product::factory()->create([
        'company_id' => $this->companyId,
    ])->id;

    $promotion->regularProducts()->attach([$productId]);

    $this->assertDatabaseHas('product_promotion', [
        'product_id' => $productId,
        'promotion_id' => $promotion->id,
    ]);

    $this->promotionQueries->removeSelectedProducts([
        'id' => $promotion->id,
        'type' => 'regular_product',
    ]);

    $this->assertDatabaseMissing('product_promotion', [
        'product_id' => $productId,
        'promotion_id' => $this->promotionA->id,
    ]);
});

test('getPromotionsExport method returns promotion as expected', function (): void {
    $response = $this->promotionQueries->getPromotionsExport([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'location_ids' => null,
        'promotion_type' => null,
        'status_value' => null,
        'promotion_user_restriction_type' => null,
        'id' => null,
        'availability_type' => null,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->promotionA->id)
        ->toHaveKey('name', $this->promotionA->name);
});

function getPromotionBasicDetails(
    ?int $locationId,
    int $productId,
    int $percentage,
    int $promotionApplicableTypeId,
    int $discountTypeId,
    ?int $cartWidePromotionTypeId,
    int $itemWisePromotionTypeId,
    int $timeframeTypeId,
): array {
    return [
        'name' => 'Promotion name',
        'promotion_applicable_type_id' => $promotionApplicableTypeId,
        'discount_type_id' => $discountTypeId,
        'cart_wide_promotion_type_id' => $cartWidePromotionTypeId,
        'item_wise_promotion_type_id' => $itemWisePromotionTypeId,
        'timeframe_type_id' => $timeframeTypeId,
        'percentage' => $percentage,
        'flat_amount' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => null,
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'allow_registered_member' => false,
        'location_ids' => [$locationId],
        'regular_product_ids' => [$productId],
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'tiers' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

test('fetchPromotionProducts method call with related models when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $response = $this->promotionQueries->fetchPromotionProducts($this->promotionA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->promotionA->name)
        ->toHaveKeys(['regular_products', 'buy_products', 'get_products']);
});

test('fetchPromotionProducts method call with related models when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $this->companyId,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $promotion = Promotion::factory()->create([
        'name' => 'ABCDEFG',
        'company_id' => $this->companyId,
        'status' => true,
    ]);

    $productId = Product::factory()->create([
        'company_id' => $this->companyId,
        'master_product_id' => $masterProduct->id,
    ])->id;

    $promotion->regularProducts()->attach([$productId]);

    $response = $this->promotionQueries->fetchPromotionProducts($promotion->id);

    expect($response->toArray())
        ->toHaveKey('name', $promotion->name)
        ->toHaveKeys(['regular_products', 'buy_products', 'get_products']);
});

test('getPromotionsForApplication method returns paginated results as expected', function (): void {
    $promotion = Promotion::factory()->create([
        'company_id' => $this->companyId,
        'start_date' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
        'end_date' => Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
        'status' => true,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $promotion->locations()->attach($locationId);

    $response = $this->promotionQueries->getPromotionsForApplication([
        'sort_by' => null,
        'sort_direction' => null,
        'search_text' => null,
        'per_page' => 1,
        'location_id' => $locationId,
        'after_updated_at' => null,
    ], $this->companyId);
    expect($response->toArray()['data'][0])
        ->toHaveKey('name', $promotion->name)
        ->toHaveKey('promotion_applicable_type_id', $promotion->promotion_applicable_type_id);
});

test(
    'getListForEcommerceAsPerTimeFrameWithRelatedData method returns the promotions list with related data as expected',
    function (): void {
        $promotion = Promotion::factory()->create([
            'company_id' => $this->companyId,
            'timeframe_type_id' => PromotionTimeframeTypes::LIMITED_BY_DATES->value,
            'status' => true,
            'is_available_in_ecommerce' => true,
        ]);

        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $promotion->locations()->sync($location->id);

        $promotion->uploadedProducts()->syncWithPivotValues(
            [$product->id],
            [
                'type' => ProductUploadTypes::REGULAR->value,
            ]
        );

        $response = $this->promotionQueries->getListForEcommerceAsPerTimeFrameWithRelatedData(
            $this->companyId,
            $location->id
        );

        expect($response)->toBeInstanceOf(Collection::class);

        $promotionsDetail = $response->toArray();

        expect($promotionsDetail[0])
        ->toHaveKey('name', $promotion->name)
        ->toHaveKey('promotion_applicable_type_id', $promotion->promotion_applicable_type_id)
        ->toHaveKey('discount_type_id', $promotion->discount_type_id)
        ->toHaveKey('cart_wide_promotion_type_id', $promotion->cart_wide_promotion_type_id)
        ->toHaveKey('item_wise_promotion_type_id', $promotion->item_wise_promotion_type_id)
        ->toHaveKey('timeframe_type_id', $promotion->timeframe_type_id)
        ->toHaveKey('percentage', $promotion->percentage)
        ->toHaveKey('flat_amount', $promotion->flat_amount)
        ->toHaveKey('start_date', $promotion->start_date)
        ->toHaveKey('end_date', $promotion->end_date)
        ->toHaveKey('start_time', $promotion->start_time)
        ->toHaveKey('end_time', $promotion->end_time)
        ->toHaveKey('allow_registered_member', $promotion->allow_registered_member)
        ->toHaveKeys(
            ['regular_products', 'buy_products', 'get_products', 'categories', 'monthly', 'promotion_tiers', 'weekly']
        );
    }
);

test('getPromotionsStoreWiseForApplication method returns promotion results as expected', function (): void {
    $promotion = Promotion::factory()->create([
        'company_id' => $this->companyId,
        'start_date' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
        'end_date' => Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
        'status' => true,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $promotion->locations()->attach($locationId);

    $response = $this->promotionQueries->getPromotionsStoreWiseForApplication($this->companyId, $locationId);

    expect($response->first()->toArray())
        ->toHaveKey('name', $promotion->name)
        ->toHaveKey('promotion_applicable_type_id', $promotion->promotion_applicable_type_id);
});

test('getPromotionById method returns promotion results as expected', function (): void {
    $promotion = Promotion::factory()->create([
        'company_id' => $this->companyId,
        'start_date' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
        'end_date' => Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
        'status' => true,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $promotion->locations()->attach($locationId);

    $response = $this->promotionQueries->getPromotionById($promotion->id);

    expect($response->toArray())
        ->toHaveKeys(
            [
                'id',
                'company_id',
                'name',
                'promotion_applicable_type_id',
                'cart_wide_promotion_type_id',
                'timeframe_type_id',
                'discount_type_id',
                'percentage',
                'flat_amount',
                'start_date',
                'end_date',
                'start_time',
                'end_time',
                'status',
                'monthly',
                'weekly',
                'promotion_tiers',
            ]
        );
});

test(
    'validateLocationAndSaleChannelMatch method validates location and sale channel matches between promotion and sale channel',
    function (): void {
        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $saleChannel = SaleChannel::factory()->create([
            'company_id' => $this->companyId,
            'default_location_id' => $location->id,
        ]);

        $this->promotionA->locations()->sync($location->id);
        $this->promotionA->saleChannels()->sync($saleChannel->id);

        expect(
            $this->promotionQueries->validateLocationAndSaleChannelMatch($this->promotionA, $saleChannel)
        )->toBeTrue();

        $differentLocation = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $this->promotionA->locations()->sync($differentLocation->id);

        expect(
            $this->promotionQueries->validateLocationAndSaleChannelMatch($this->promotionA, $saleChannel)
        )->toBeFalse();
    }
);

test('getManualPromotionsStoreWiseForApplication method returns promotion results as expected', function (): void {
    $promotion = Promotion::factory()->create([
        'company_id' => $this->companyId,
        'start_date' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
        'end_date' => Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
        'status' => true,
        'is_automatic' => false,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $promotion->locations()->attach($locationId);

    $response = $this->promotionQueries->getManualPromotionsStoreWiseForApplication($this->companyId, $locationId);

    expect($response->first()->toArray())
        ->toHaveKey('name', $promotion->name)
        ->toHaveKey('promotion_applicable_type_id', $promotion->promotion_applicable_type_id);
});

test('getManualPromotionsForApplication method returns paginated results as expected', function (): void {
    $promotion = Promotion::factory()->create([
        'company_id' => $this->companyId,
        'start_date' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
        'end_date' => Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
        'status' => true,
        'is_automatic' => false,
    ]);

    $locationId = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;

    $promotion->locations()->attach($locationId);

    $response = $this->promotionQueries->getManualPromotionsForApplication([
        'sort_by' => null,
        'sort_direction' => null,
        'search_text' => null,
        'per_page' => 1,
        'location_id' => $locationId,
        'after_updated_at' => null,
    ], $this->companyId);
    expect($response->toArray()['data'][0])
        ->toHaveKey('name', $promotion->name)
        ->toHaveKey('promotion_applicable_type_id', $promotion->promotion_applicable_type_id);
});

test(
    'getPromotionsOfProvidedPromoCodeForApplication method returns the manual promotion with related data as expected',
    function (): void {
        $promotion = Promotion::factory()->create([
            'company_id' => $this->companyId,
            'timeframe_type_id' => PromotionTimeframeTypes::LIMITED_BY_DATES->value,
            'status' => true,
            'is_automatic' => false,
        ]);

        $location = Location::factory()->create([
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $promotionPromoCode = PromotionPromoCode::factory()->create([
            'promotion_id' => $promotion->getKey(),
            'promo_code' => 'ABC123',
        ]);

        $promotion->locations()->sync($location->id);

        $promotion->promotionPromoCodes = $promotionPromoCode;

        $response = $this->promotionQueries->getPromotionsOfProvidedPromoCodeForApplication(
            $this->companyId,
            $location->id,
            $promotionPromoCode->promo_code,
        );

        expect($response->toArray())
            ->toHaveKey('is_automatic', $promotion->is_automatic);
    }
);
