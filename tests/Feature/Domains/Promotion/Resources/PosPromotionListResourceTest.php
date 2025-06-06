<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Promotion\Enums\CartWidePromotionTypes;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Domains\Promotion\Enums\PromotionTimeframeTypes;
use App\Domains\Promotion\Enums\PromotionTypes;
use App\Domains\Promotion\Resources\PosPromotionListResource;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionMonthDate;
use App\Models\PromotionTier;
use App\Models\PromotionWeekDay;

test(
    'getTypeAndKeyNamesAsPerSelectedPromotion method returns promotion type and key names for limited by categories promotion as expected',
    function (): void {
        $locations = Location::factory()->create();

        $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->create([
            'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value,
            'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
            'discount_type_id' => DiscountTypes::PERCENTAGE->value,
        ]);

        $response = PosPromotionListResource::getTypeAndKeyNamesAsPerSelectedPromotion(
            $promotion->promotion_applicable_type_id,
            $promotion->cart_wide_promotion_type_id,
            $promotion->item_wise_promotion_type_id,
            $promotion->discount_type_id
        );

        $expectedResponse = [
            PromotionTypes::ITEM_WISE_LIMITED_TO_CATEGORIES_PERCENTAGE->name,
            'buy_value',
            'get_value',
            null,
            null,
        ];

        $this->assertEquals($expectedResponse, $response);
    }
);

test(
    'getTypeAndKeyNamesAsPerSelectedPromotion method returns promotion type and key names for buy any 3 or more and get % off promotion as expected',
    function (): void {
        $locations = Location::factory()->create();

        $categories = Category::factory()->create();

        $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->hasAttached($categories)
        ->create([
            'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value,
            'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
        ]);

        $response = PosPromotionListResource::getTypeAndKeyNamesAsPerSelectedPromotion(
            $promotion->promotion_applicable_type_id,
            $promotion->cart_wide_promotion_type_id,
            $promotion->item_wise_promotion_type_id,
            $promotion->discount_type_id
        );

        $expectedResponse = [
            PromotionTypes::ITEM_WISE_BUY_ANY_3_OR_MORE_AND_GET_30_OFF->name,
            'buy_quantity',
            'percentage',
            null,
            null,
        ];

        $this->assertEquals($expectedResponse, $response);
    }
);

test(
    'getTypeAndKeyNamesAsPerSelectedPromotion method returns promotion type and key names for Percentage Discount For Next Item as expected',
    function (): void {
        $locations = Location::factory()->create();

        $categories = Category::factory()->create();

        $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->hasAttached($categories)
        ->create([
            'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value,
            'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
        ]);

        $response = PosPromotionListResource::getTypeAndKeyNamesAsPerSelectedPromotion(
            $promotion->promotion_applicable_type_id,
            $promotion->cart_wide_promotion_type_id,
            $promotion->item_wise_promotion_type_id,
            $promotion->discount_type_id
        );

        $expectedResponse = [
            PromotionTypes::ITEM_WISE_PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->name,
            'buy_quantity',
            'percentage',
            null,
            null,
        ];

        $this->assertEquals($expectedResponse, $response);
    }
);

test(
    'getTypeAndKeyNamesAsPerSelectedPromotion method returns promotion type and key names for buy any 2 and get 1 on 1RM as expected',
    function (): void {
        $locations = Location::factory()->create();

        $categories = Category::factory()->create();

        $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->hasAttached($categories)
        ->create([
            'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value,
            'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
        ]);

        $response = PosPromotionListResource::getTypeAndKeyNamesAsPerSelectedPromotion(
            $promotion->promotion_applicable_type_id,
            $promotion->cart_wide_promotion_type_id,
            $promotion->item_wise_promotion_type_id,
            $promotion->discount_type_id
        );

        $expectedResponse = [
            PromotionTypes::ITEM_WISE_BUY_2_AND_GET_1_QUANTITY_AT_RM1->name,
            'buy_quantity',
            'amount',
            'get_quantity',
            null,
        ];

        $this->assertEquals($expectedResponse, $response);
    }
);

test(
    'getTypeAndKeyNamesAsPerSelectedPromotion method returns promotion type and key names for cart wide automatic percentage promotion as expected',
    function (): void {
        $locations = Location::factory()->create();

        $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->create([
            'promotion_applicable_type_id' => PromotionApplicableTypes::CART_WIDE->value,
            'cart_wide_promotion_type_id' => CartWidePromotionTypes::AS_PER_AMOUNT->value,
            'item_wise_promotion_type_id' => null,
            'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
            'discount_type_id' => DiscountTypes::PERCENTAGE->value,
        ]);

        $response = PosPromotionListResource::getTypeAndKeyNamesAsPerSelectedPromotion(
            $promotion->promotion_applicable_type_id,
            $promotion->cart_wide_promotion_type_id,
            $promotion->item_wise_promotion_type_id,
            $promotion->discount_type_id
        );

        $expectedResponse = [
            PromotionTypes::CART_WIDE_AS_PER_AMOUNT_PERCENTAGE->name,
            'minimum_spend_amount',
            'percentage',
            null,
            null,
        ];

        $this->assertEquals($expectedResponse, $response);
    }
);

test('getPromotionProductsIds method returns promotion product ids as expected', function (): void {
    $products = Product::factory(2)->create();

    $response = PosPromotionListResource::getPromotionProductsIds($products);

    $expectedResponse = [$products->first()->id, $products->last()->id];

    $this->assertEquals($expectedResponse, $response);
});

test('getPromotionCategoriesIds method returns promotion category ids as expected', function (): void {
    $categories = Category::factory(2)->create();

    $response = PosPromotionListResource::getPromotionCategoriesIds($categories);

    $expectedResponse = [$categories->first()->id, $categories->last()->id];

    $this->assertEquals($expectedResponse, $response);
});

test('getPromotionMonthDates method returns promotion moth dates as expected', function (): void {
    $locations = Location::factory()->create();

    $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->create([
            'name' => 'Buy Two Get Fifty Off On Others',
            'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value,
            'timeframe_type_id' => PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
        ]);

    for ($i = 1; $i < 4; ++$i) {
        PromotionMonthDate::factory()->create([
            'promotion_id' => $promotion->id,
            'month_date' => $i,
        ]);
    }

    $response = PosPromotionListResource::getPromotionMonthDates($promotion->monthly);

    $expectedResponse = [1, 2, 3];

    $this->assertEquals($expectedResponse, $response);
});

test('getPromotionWeekDays method returns promotion week days as expected', function (): void {
    $locations = Location::factory()->create();

    $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->create([
            'name' => 'Buy Two Get Fifty Off On Others',
            'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_3_GET_1->value,
            'timeframe_type_id' => PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
        ]);

    for ($i = 1; $i < 4; ++$i) {
        PromotionWeekDay::factory()->create([
            'promotion_id' => $promotion->id,
            'week_day' => $i,
        ]);
    }

    $response = PosPromotionListResource::getPromotionWeekDays($promotion->weekly);

    $expectedResponse = [1, 2, 3];

    $this->assertEquals($expectedResponse, $response);
});

test(
    'getTypeAndKeyNamesAsPerSelectedPromotion method returns promotion type and key names for buy any 3 or more and get flat off promotion as expected',
    function (): void {
        $locations = Location::factory()->create();

        $categories = Category::factory()->create();

        $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->hasAttached($categories)
        ->create([
            'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value,
            'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
        ]);

        $response = PosPromotionListResource::getTypeAndKeyNamesAsPerSelectedPromotion(
            $promotion->promotion_applicable_type_id,
            null,
            $promotion->item_wise_promotion_type_id,
            $promotion->discount_type_id
        );

        $expectedResponse = [
            PromotionTypes::ITEM_WISE_BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->name,
            'buy_quantity',
            'flat_amount',
            null,
            null,
        ];

        $this->assertEquals($expectedResponse, $response);
    }
);
test('getPromotionTiers method returns promotion tiers as expected', function (): void {
    $locations = Location::factory()->create();

    $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->create([
            'name' => 'Buy Two And Get One On One Currency',
            'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value,
            'timeframe_type_id' => PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
        ]);

    PromotionTier::factory()->create([
        'promotion_id' => $promotion->id,
        'buy_value' => 10.10,
        'get_value' => 20.10,
        'get_quantity' => 1,
    ]);

    $response = PosPromotionListResource::getPromotionTiers(
        $promotion->promotionTiers,
        'buy_quantity',
        'amount',
        'get_quantity'
    );

    $expectedResponse = [
        'buy_quantity' => 10.10,
        'amount' => 20.10,
        'get_quantity' => 1,
    ];

    $this->assertEquals($expectedResponse, $response[0]);
});

test('getPromotionTiers method returns promotion tiers as expected when get_quantity is null', function (): void {
    $locations = Location::factory()->create();

    $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->create([
            'name' => 'Buy Two And Get One On One Currency',
            'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value,
            'timeframe_type_id' => PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
        ]);

    PromotionTier::factory()->create([
        'promotion_id' => $promotion->id,
        'buy_value' => 10.10,
        'get_value' => 20.10,
        'get_quantity' => 1,
    ]);

    $response = PosPromotionListResource::getPromotionTiers($promotion->promotionTiers, 'buy_quantity', 'amount', null);

    $expectedResponse = [
        'buy_quantity' => 10.10,
        'amount' => 20.10,
    ];

    $this->assertEquals($expectedResponse, $response[0]);
});

test(
    'getTypeAndKeyNamesAsPerSelectedPromotion method returns promotion type and key names for as per amount limited to brands percentage promotion as expected',
    function (): void {
        $locations = Location::factory()->create();

        $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->create([
            'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value,
            'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
            'discount_type_id' => DiscountTypes::PERCENTAGE->value,
        ]);

        $response = PosPromotionListResource::getTypeAndKeyNamesAsPerSelectedPromotion(
            $promotion->promotion_applicable_type_id,
            $promotion->cart_wide_promotion_type_id,
            $promotion->item_wise_promotion_type_id,
            $promotion->discount_type_id
        );

        $expectedResponse = [
            PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_BRANDS_PERCENTAGE->name,
            'minimum_spend_amount',
            'percentage',
            null,
            null,
        ];

        $this->assertEquals($expectedResponse, $response);
    }
);

test(
    'getTypeAndKeyNamesAsPerSelectedPromotion method returns promotion type and key names for as per amount limited to brands flat promotion as expected',
    function (): void {
        $locations = Location::factory()->create();

        $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->create([
            'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value,
            'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
            'discount_type_id' => DiscountTypes::FLAT->value,
        ]);

        $response = PosPromotionListResource::getTypeAndKeyNamesAsPerSelectedPromotion(
            $promotion->promotion_applicable_type_id,
            $promotion->cart_wide_promotion_type_id,
            $promotion->item_wise_promotion_type_id,
            $promotion->discount_type_id
        );

        $expectedResponse = [
            PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_BRANDS_FLAT->name,
            'minimum_spend_amount',
            'flat_amount',
            null,
            null,
        ];

        $this->assertEquals($expectedResponse, $response);
    }
);

test(
    'getTypeAndKeyNamesAsPerSelectedPromotion method returns promotion type and key names for as per amount get off on others percentage promotion as expected',
    function (): void {
        $locations = Location::factory()->create();

        $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->create([
            'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value,
            'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
            'discount_type_id' => DiscountTypes::PERCENTAGE->value,
        ]);

        $response = PosPromotionListResource::getTypeAndKeyNamesAsPerSelectedPromotion(
            $promotion->promotion_applicable_type_id,
            $promotion->cart_wide_promotion_type_id,
            $promotion->item_wise_promotion_type_id,
            $promotion->discount_type_id
        );

        $expectedResponse = [
            PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_GET_OFF_ON_OTHERS_PERCENTAGE->name,
            'minimum_spend_amount',
            'percentage',
            null,
            'maximum_spend_amount',
        ];

        $this->assertEquals($expectedResponse, $response);
    }
);

test(
    'getTypeAndKeyNamesAsPerSelectedPromotion method returns promotion type and key names for as per amount get off on others flat promotion as expected',
    function (): void {
        $locations = Location::factory()->create();

        $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->create([
            'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value,
            'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
            'discount_type_id' => DiscountTypes::FLAT->value,
        ]);

        $response = PosPromotionListResource::getTypeAndKeyNamesAsPerSelectedPromotion(
            $promotion->promotion_applicable_type_id,
            $promotion->cart_wide_promotion_type_id,
            $promotion->item_wise_promotion_type_id,
            $promotion->discount_type_id
        );

        $expectedResponse = [
            PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_GET_OFF_ON_OTHERS_FLAT->name,
            'minimum_spend_amount',
            'flat_amount',
            null,
            'maximum_spend_amount',
        ];

        $this->assertEquals($expectedResponse, $response);
    }
);

test(
    'getTypeAndKeyNamesAsPerSelectedPromotion method returns promotion type and key names for as per amount limited to price percentage promotion as expected',
    function (): void {
        $locations = Location::factory()->create();

        $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->create([
            'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value,
            'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
            'discount_type_id' => DiscountTypes::PERCENTAGE->value,
        ]);

        $response = PosPromotionListResource::getTypeAndKeyNamesAsPerSelectedPromotion(
            $promotion->promotion_applicable_type_id,
            $promotion->cart_wide_promotion_type_id,
            $promotion->item_wise_promotion_type_id,
            $promotion->discount_type_id
        );

        $expectedResponse = [
            PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_PRICE_PERCENTAGE->name,
            'minimum_product_price',
            'percentage',
            null,
            'maximum_product_price',
        ];

        $this->assertEquals($expectedResponse, $response);
    }
);

test(
    'getTypeAndKeyNamesAsPerSelectedPromotion method returns promotion type and key names for as per amount limited to price flat promotion as expected',
    function (): void {
        $locations = Location::factory()->create();

        $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->create([
            'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value,
            'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
            'discount_type_id' => DiscountTypes::FLAT->value,
        ]);

        $response = PosPromotionListResource::getTypeAndKeyNamesAsPerSelectedPromotion(
            $promotion->promotion_applicable_type_id,
            $promotion->cart_wide_promotion_type_id,
            $promotion->item_wise_promotion_type_id,
            $promotion->discount_type_id
        );

        $expectedResponse = [
            PromotionTypes::ITEM_WISE_AS_PER_AMOUNT_LIMITED_TO_PRICE_FLAT->name,
            'minimum_product_price',
            'flat_amount',
            null,
            'maximum_product_price',
        ];

        $this->assertEquals($expectedResponse, $response);
    }
);

test(
    'getTypeAndKeyNamesAsPerSelectedPromotion method returns promotion type and key names for Flat Discount For Next Item as expected',
    function (): void {
        $locations = Location::factory()->create();

        $categories = Category::factory()->create();

        $promotion = Promotion::factory()
        ->hasAttached($locations)
        ->hasAttached($categories)
        ->create([
            'promotion_applicable_type_id' => PromotionApplicableTypes::ITEM_WISE->value,
            'cart_wide_promotion_type_id' => null,
            'item_wise_promotion_type_id' => ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value,
            'timeframe_type_id' => PromotionTimeframeTypes::NO_LIMIT->value,
        ]);

        $response = PosPromotionListResource::getTypeAndKeyNamesAsPerSelectedPromotion(
            $promotion->promotion_applicable_type_id,
            $promotion->cart_wide_promotion_type_id,
            $promotion->item_wise_promotion_type_id,
            $promotion->discount_type_id
        );

        $expectedResponse = [
            PromotionTypes::ITEM_WISE_FLAT_DISCOUNT_FOR_NEXT_ITEM->name,
            'buy_quantity',
            'flat_amount',
            null,
            null,
        ];

        $this->assertEquals($expectedResponse, $response);
    }
);
