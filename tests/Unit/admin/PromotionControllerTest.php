<?php

declare(strict_types=1);

use App\Domains\Category\CategoryQueries;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\Promotion\DataObjects\PromotionData;
use App\Domains\Promotion\Enums\CartWidePromotionTypes;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Promotion\Enums\ProductUploadTypes;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Domains\Promotion\Enums\PromotionUsageTypes;
use App\Domains\Promotion\PromotionQueries;
use App\Domains\Promotion\Services\PromotionCheckRequestService;
use App\Domains\Promotion\Services\PromotionService;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\Tag\TagQueries;
use App\Http\Controllers\Admin\PromotionController;
use App\Models\Admin;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the promotion queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'abc',
        'sort_by' => null,
        'sort_direction' => 'desc',
        'per_page' => 1,
        'location_ids' => 'null',
        'promotion_type' => 'null',
        'status_value' => 'null',
        'promotion_user_restriction_type' => 'null',
        'id' => null,
        'availability_type' => null,
        'type' => null,
    ];

    $promotionQueries = $this->mock(PromotionQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 20, 15));
    });

    $promotionController = new PromotionController($promotionQueries);

    $response = $promotionController->fetchPromotions(new Request($requestParameter));

    $this->assertEquals(20, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']->resource);
});

test('It calls addNew method of the promotion queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $promotionData = seedPromotionRecord();

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $promotionRecords = new PromotionData(...$promotionData);

    $this->mock(LocationQueries::class, function ($mock) use ($promotionRecords, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $promotionRecords->location_ids)
            ->andReturn(true);
    });

    $this->mock(ProductQueries::class, function ($mock) use ($promotionRecords, $companyId): void {
        $mock->shouldReceive('doAllProductsExist')
            ->once()
            ->with($companyId, $promotionRecords->regular_product_ids)
            ->andReturn(true);
    });

    $promotionQueries = $this->mock(PromotionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $promotionController = new PromotionController($promotionQueries);
    $redirectResponse = $promotionController->store($promotionRecords, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Promotion added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/promotions', $redirectResponse->getTargetUrl());
});

test(
    'the getPromotionDetails method returns proper response for cart wide percentage promotion',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $promotionData = seedPromotionRecordForCartWidePercentage();

        $promotionRecords = new PromotionData(...$promotionData);

        $promotionQueries = $this->mock(PromotionQueries::class);

        $promotionController = new PromotionController($promotionQueries);
        $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

        $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::CART_WIDE->value);
        $this->assertEquals($response['cart_wide_promotion_type_id'], CartWidePromotionTypes::AS_PER_AMOUNT->value);
        $this->assertEquals($response['discount_type_id'], null);
        $this->assertEquals($response['tiers'], $promotionRecords->tiers);
    }
);

test('the getPromotionDetails method returns proper response for cart wide flat promotion', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $promotionData = seedPromotionRecordForCartWideFlat();

    $promotionRecords = new PromotionData(...$promotionData);

    $promotionQueries = $this->mock(PromotionQueries::class);

    $promotionController = new PromotionController($promotionQueries);
    $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

    $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::CART_WIDE->value);
    $this->assertEquals($response['cart_wide_promotion_type_id'], CartWidePromotionTypes::AS_PER_AMOUNT->value);
    $this->assertEquals($response['discount_type_id'], null);
    $this->assertEquals($response['tiers'], $promotionRecords->tiers);
});

test('the getPromotionDetails method returns proper response for limited by products', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $promotionData = seedPromotionRecordForLimitedByProducts();

    $promotionRecords = new PromotionData(...$promotionData);

    $promotionQueries = $this->mock(PromotionQueries::class);

    $promotionController = new PromotionController($promotionQueries);
    $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

    $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
    $this->assertEquals(
        $response['item_wise_promotion_type_id'],
        ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value
    );
    $this->assertEquals($response['discount_type_id'], DiscountTypes::PERCENTAGE->value);
    $this->assertEquals($response['regular_product_ids'], $promotionRecords->regular_product_ids);
});

test('the getPromotionDetails method returns proper response for limited by category', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $promotionData = seedPromotionRecordForLimitedByCategory();

    $promotionRecords = new PromotionData(...$promotionData);

    $promotionQueries = $this->mock(PromotionQueries::class);

    $promotionController = new PromotionController($promotionQueries);
    $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

    $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
    $this->assertEquals(
        $response['item_wise_promotion_type_id'],
        ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value
    );
    $this->assertEquals($response['discount_type_id'], DiscountTypes::PERCENTAGE->value);
    $this->assertEquals($response['category_ids'], $promotionRecords->category_ids);
});

test('the getPromotionDetails method returns proper response for buy two get one or more', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $promotionData = seedPromotionRecordForBuyTwoGetOneOrMore();

    $promotionRecords = new PromotionData(...$promotionData);

    $promotionQueries = $this->mock(PromotionQueries::class);

    $promotionController = new PromotionController($promotionQueries);
    $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

    $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
    $this->assertEquals($response['item_wise_promotion_type_id'], ItemWisePromotionTypes::BUY_3_GET_1->value);
    $this->assertEquals($response['discount_type_id'], null);
    $this->assertEquals($response['buy_product_ids'], $promotionRecords->buy_product_ids);
    $this->assertEquals($response['get_product_ids'], $promotionRecords->get_product_ids);
    $this->assertEquals($response['tiers'], $promotionRecords->tiers);
});

test(
    'the getPromotionDetails method returns proper response for buy two get fifty on other',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $promotionData = seedPromotionRecordForBuyTwoGetFiftyOnOther();

        $promotionRecords = new PromotionData(...$promotionData);

        $promotionQueries = $this->mock(PromotionQueries::class);

        $promotionController = new PromotionController($promotionQueries);
        $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

        $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
        $this->assertEquals(
            $response['item_wise_promotion_type_id'],
            ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value
        );
        $this->assertEquals($response['discount_type_id'], null);
        $this->assertEquals($response['buy_product_ids'], $promotionRecords->buy_product_ids);
        $this->assertEquals($response['get_product_ids'], $promotionRecords->get_product_ids);
        $this->assertEquals($response['tiers'], $promotionRecords->tiers);
    }
);

test(
    'the getPromotionDetails method returns proper response for buy any 3 or more and get 30 off',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $promotionData = seedPromotionRecordForBuyAnyThreeOrMoreGet3ThirtyOff();

        $promotionRecords = new PromotionData(...$promotionData);

        $promotionQueries = $this->mock(PromotionQueries::class);

        $promotionController = new PromotionController($promotionQueries);
        $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

        $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
        $this->assertEquals(
            $response['item_wise_promotion_type_id'],
            ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value
        );
        $this->assertEquals($response['discount_type_id'], null);
        $this->assertEquals($response['regular_product_ids'], $promotionRecords->regular_product_ids);
        $this->assertEquals($response['tiers'], $promotionRecords->tiers);
    }
);

test(
    'the getPromotionDetails method returns proper response for Percentage Discount For Next Item',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $promotionData = seedPromotionRecordForPercentageDiscountForNextItem();

        $promotionRecords = new PromotionData(...$promotionData);

        $promotionQueries = $this->mock(PromotionQueries::class);

        $promotionController = new PromotionController($promotionQueries);
        $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

        $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
        $this->assertEquals(
            $response['item_wise_promotion_type_id'],
            ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value
        );
        $this->assertEquals($response['discount_type_id'], null);
        $this->assertEquals($response['regular_product_ids'], $promotionRecords->regular_product_ids);
        $this->assertEquals($response['tiers'], $promotionRecords->tiers);
    }
);

test('the getPromotionDetails method returns proper response for cheapest free', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $promotionData = seedPromotionRecordForCheapestFree();

    $promotionRecords = new PromotionData(...$promotionData);

    $promotionQueries = $this->mock(PromotionQueries::class);

    $promotionController = new PromotionController($promotionQueries);
    $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

    $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
    $this->assertEquals($response['item_wise_promotion_type_id'], ItemWisePromotionTypes::CHEAPEST_FREE->value);
    $this->assertEquals($response['discount_type_id'], null);
    $this->assertEquals($response['regular_product_ids'], $promotionRecords->regular_product_ids);
    $this->assertEquals($response['tiers'], $promotionRecords->tiers);
});

test('the getPromotionDetails method returns proper response for bundle buy', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $promotionData = seedPromotionRecordForBundleBuy();

    $promotionRecords = new PromotionData(...$promotionData);

    $promotionQueries = $this->mock(PromotionQueries::class);

    $promotionController = new PromotionController($promotionQueries);
    $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

    $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
    $this->assertEquals($response['item_wise_promotion_type_id'], ItemWisePromotionTypes::BUNDLE_BUY->value);
    $this->assertEquals($response['discount_type_id'], null);
    $this->assertEquals($response['regular_product_ids'], $promotionRecords->regular_product_ids);
    $this->assertEquals($response['tiers'], $promotionRecords->tiers);
});

test('it calls the setStatus method of PromotionQueries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $promotion = Promotion::factory()->make([
        'company_id' => $companyId,
        'id' => 1,
    ]);

    $promotionQueries = $this->mock(PromotionQueries::class, function ($mock) use ($promotion, $companyId): void {
        $mock->shouldReceive('setStatus')
            ->once()
            ->with($promotion->id, $companyId, false);
    });

    $promotionController = new PromotionController($promotionQueries);
    $response = $promotionController->setStatus(1, false);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Status changed successfully.', $response->getSession()->all()['success']);
    $this->assertStringContainsString('admin/promotions', $response->getTargetUrl());
});

test(
    'It calls the getByIdWithRelations method of the promotion queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $this->mock(PromotionService::class, function ($mock): void {
            $mock->shouldReceive('getPromotionTypeLabel')
            ->once();
        });

        $this->mock(CategoryQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getMainCategoriesWithBasicColumns')
            ->once()
            ->with($companyId)
            ->andReturn(new Collection([]));
        });

        $promotionQueries = $this->mock(PromotionQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getByIdWithRelations')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new Promotion([]));
        });

        $this->mock(MemberGroupQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(collect([]));
        });

        $this->mock(MembershipQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
            ->once()
            ->andReturn(collect([]));
        });

        $this->mock(TagQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
            ->once()
            ->andReturn(new Collection([]));
        });

        $this->mock(ProductCollectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getProductCollections')
            ->once()
            ->andReturn(new Collection([]));
        });

        $this->mock(EmployeeGroupQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(collect([]));
        });

        $this->mock(SaleChannelQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllByCompanyId')
            ->once()
            ->andReturn(collect([]));
        });

        $this->mock(PaymentTypeQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllByCompanyId')
            ->once()
            ->andReturn(collect([]));
        });

        $promotionController = new PromotionController($promotionQueries);

        $response = $promotionController->edit(1);

        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
            ->hasAll('promotion', 'categories', 'timeFrames', 'promotionName', 'staticDetails')
        );
    }
);

test('It calls the update method of the promotion queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $promotionData = seedPromotionRecord();

    $promotionRecords = new PromotionData(...$promotionData);

    $this->mock(ProductQueries::class, function ($mock) use ($promotionRecords, $companyId): void {
        $mock->shouldReceive('doAllProductsExist')
            ->once()
            ->with($companyId, $promotionRecords->regular_product_ids)
            ->andReturn(true);
    });

    $promotionQueries = $this->mock(PromotionQueries::class, function ($mock): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn(new Promotion([]));
        $mock->shouldReceive('update')
            ->once();
    });

    $promotionController = new PromotionController($promotionQueries);
    $redirectResponse = $promotionController->update($promotionRecords, 1);

    $this->assertEquals('Promotion has been successfully updated.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/promotions', $redirectResponse->getTargetUrl());
});

test(
    'the getPromotionDetails method returns proper response for buy any 3 or more and get Currency 30 off',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $promotionData = seedPromotionRecordForBuyAnyThreeOrMoreGetRMThirtyOff();

        $promotionRecords = new PromotionData(...$promotionData);

        $promotionQueries = $this->mock(PromotionQueries::class);

        $promotionController = new PromotionController($promotionQueries);
        $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

        $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
        $this->assertEquals(
            $response['item_wise_promotion_type_id'],
            ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value
        );
        $this->assertEquals($response['discount_type_id'], null);
        $this->assertEquals($response['regular_product_ids'], $promotionRecords->regular_product_ids);
        $this->assertEquals($response['tiers'], $promotionRecords->tiers);
    }
);

test(
    'the getPromotionDetails method returns proper response for buy two get Currency fifty on other',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $promotionData = seedPromotionRecordForBuyTwoGetRMFiftyOnOther();

        $promotionRecords = new PromotionData(...$promotionData);

        $promotionQueries = $this->mock(PromotionQueries::class);

        $promotionController = new PromotionController($promotionQueries);
        $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

        $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
        $this->assertEquals(
            $response['item_wise_promotion_type_id'],
            ItemWisePromotionTypes::BUY_2_GET_RM_50_OFF_ON_OTHERS->value
        );
        $this->assertEquals($response['discount_type_id'], null);
        $this->assertEquals($response['buy_product_ids'], $promotionRecords->buy_product_ids);
        $this->assertEquals($response['get_product_ids'], $promotionRecords->get_product_ids);
        $this->assertEquals($response['tiers'], $promotionRecords->tiers);
    }
);
test('the getPromotionDetails method returns proper response for buy 2 and get 1 quantity at rm1 ', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $promotionData = seedPromotionRecordForBuyTwoAndGetOneQuantityAtRmOne();

    $promotionRecords = new PromotionData(...$promotionData);

    $promotionQueries = $this->mock(PromotionQueries::class);

    $promotionController = new PromotionController($promotionQueries);
    $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

    $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
    $this->assertEquals(
        $response['item_wise_promotion_type_id'],
        ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value
    );
    $this->assertEquals($response['discount_type_id'], null);
    $this->assertEquals($response['regular_product_ids'], $promotionRecords->regular_product_ids);
    $this->assertEquals($response['tiers'], $promotionRecords->tiers);
});

test(
    'the getPromotionDetails method returns proper response for as per amount limited to brands percentage promotion',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $promotionData = seedPromotionRecordForAsPerAmountLimitedToBrandsPercentage();

        $promotionRecords = new PromotionData(...$promotionData);

        $promotionQueries = $this->mock(PromotionQueries::class);

        $promotionController = new PromotionController($promotionQueries);
        $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

        $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
        $this->assertEquals(
            $response['item_wise_promotion_type_id'],
            ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value
        );
        $this->assertEquals($response['discount_type_id'], DiscountTypes::PERCENTAGE->value);
        $this->assertEquals($response['tiers'], $promotionRecords->tiers);
    }
);

test(
    'the getPromotionDetails method returns proper response for as per amount limited to brands flat promotion',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $promotionData = seedPromotionRecordForAsPerAmountGetOffOnOthersFlat();

        $promotionRecords = new PromotionData(...$promotionData);

        $promotionQueries = $this->mock(PromotionQueries::class);

        $promotionController = new PromotionController($promotionQueries);
        $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

        $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
        $this->assertEquals(
            $response['item_wise_promotion_type_id'],
            ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value
        );
        $this->assertEquals($response['discount_type_id'], DiscountTypes::FLAT->value);
        $this->assertEquals($response['tiers'], $promotionRecords->tiers);
    }
);

test(
    'the getPromotionDetails method returns proper response for as per amount get off on others percentage promotion',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $promotionData = seedPromotionRecordForAsPerAmountGetOffOnOthersPercentage();

        $promotionRecords = new PromotionData(...$promotionData);

        $promotionQueries = $this->mock(PromotionQueries::class);

        $promotionController = new PromotionController($promotionQueries);
        $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

        $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
        $this->assertEquals(
            $response['item_wise_promotion_type_id'],
            ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value
        );
        $this->assertEquals($response['discount_type_id'], DiscountTypes::PERCENTAGE->value);
        $this->assertEquals($response['tiers'], $promotionRecords->tiers);
    }
);

test(
    'the getPromotionDetails method returns proper response for as per amount get off on others flat promotion',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $promotionData = seedPromotionRecordForAsPerAmountLimitedToBrandsFlat();

        $promotionRecords = new PromotionData(...$promotionData);

        $promotionQueries = $this->mock(PromotionQueries::class);

        $promotionController = new PromotionController($promotionQueries);
        $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

        $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
        $this->assertEquals(
            $response['item_wise_promotion_type_id'],
            ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value
        );
        $this->assertEquals($response['discount_type_id'], DiscountTypes::FLAT->value);
        $this->assertEquals($response['tiers'], $promotionRecords->tiers);
    }
);

test('It calls the exportPromotions method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'abc',
        'sort_by' => null,
        'sort_direction' => 'desc',
        'per_page' => 1,
        'location_ids' => 'null',
        'promotion_type' => 'null',
        'status_value' => 'null',
        'promotion_user_restriction_type' => 'null',
        'id' => null,
        'availability_type' => null,
        'type' => null,
    ];

    $promotionQueries = $this->mock(PromotionQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getPromotionsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Promotion()));
    });

    $promotionController = new PromotionController($promotionQueries);

    $response = $promotionController->exportPromotions('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'the getPromotionDetails method returns proper response for as per amount limited to price percentage promotion',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $promotionData = seedPromotionRecordForAsPerAmountLimitedToPricePercentage();

        $promotionRecords = new PromotionData(...$promotionData);

        $promotionQueries = $this->mock(PromotionQueries::class);

        $promotionController = new PromotionController($promotionQueries);
        $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

        $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
        $this->assertEquals(
            $response['item_wise_promotion_type_id'],
            ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value
        );
        $this->assertEquals($response['discount_type_id'], DiscountTypes::PERCENTAGE->value);
        $this->assertEquals($response['tiers'], $promotionRecords->tiers);
    }
);

test(
    'the getPromotionDetails method returns proper response for as per amount limited to price flat promotion',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $promotionData = seedPromotionRecordForAsPerAmountLimitedToPriceFlat();

        $promotionRecords = new PromotionData(...$promotionData);

        $promotionQueries = $this->mock(PromotionQueries::class);

        $promotionController = new PromotionController($promotionQueries);
        $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

        $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
        $this->assertEquals(
            $response['item_wise_promotion_type_id'],
            ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value
        );
        $this->assertEquals($response['discount_type_id'], DiscountTypes::FLAT->value);
        $this->assertEquals($response['tiers'], $promotionRecords->tiers);
    }
);

test('It calls the exportPromotionsProductsDetails method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'type' => ProductUploadTypes::REGULAR->value,
    ];

    $promotionQueries = $this->mock(PromotionQueries::class, function ($mock): void {
        $mock->shouldReceive('fetchPromotionProducts')
            ->once()
            ->with(1)
            ->andReturn(new Promotion());
    });

    $promotionController = new PromotionController($promotionQueries);

    $response = $promotionController->exportPromotionsProductsDetails(
        1,
        'filename.csv',
        new Request($requestParameter)
    );

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the fetchPromotionDetailsById of the promotion queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $promotionQueries = $this->mock(PromotionQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getByIdWithRelations')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new Promotion());
        });

        $promotionController = new PromotionController($promotionQueries);

        $response = $promotionController->fetchPromotionDetailsById(1);
        expect($response)->toHaveKey('promotion_details');
    }
);

test('It calls validateRegularProductPrice method of the promotionCheckRequestService class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $promotionData = seedPromotionRecord();
    $promotionData['item_wise_promotion_type_id'] = ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value;
    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $promotionRecords = new PromotionData(...$promotionData);

    $promotionQueries = $this->mock(PromotionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(PromotionCheckRequestService::class, function ($mock): void {
        $mock->shouldReceive('validateRegularProductPrice')
            ->once();
        $mock->shouldReceive('validateRegularProductIds')
            ->once();
        $mock->shouldReceive('validateLocationIds')
            ->once();
    });

    $promotionController = new PromotionController($promotionQueries);
    $redirectResponse = $promotionController->store($promotionRecords, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Promotion added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/promotions', $redirectResponse->getTargetUrl());
});

test(
    'the getPromotionDetails method returns proper response for Flat Discount For Next Item',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $promotionData = seedPromotionRecordForFlatDiscountForNextItem();

        $promotionRecords = new PromotionData(...$promotionData);

        $promotionQueries = $this->mock(PromotionQueries::class);

        $promotionController = new PromotionController($promotionQueries);
        $response = $promotionController->getPromotionDetails($promotionRecords->toArray());

        $this->assertEquals($response['promotion_applicable_type_id'], PromotionApplicableTypes::ITEM_WISE->value);
        $this->assertEquals(
            $response['item_wise_promotion_type_id'],
            ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value
        );
        $this->assertEquals($response['discount_type_id'], null);
        $this->assertEquals($response['regular_product_ids'], $promotionRecords->regular_product_ids);
        $this->assertEquals($response['tiers'], $promotionRecords->tiers);
    }
);

function seedPromotionRecord(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [1],
        'allow_registered_member' => false,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => [1],
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => null,
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => null,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 1,
        'timeframe_type_id' => 1,
        'percentage' => 50,
        'flat_amount' => null,
        'tiers' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForCartWidePercentage(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [1],
        'allow_registered_member' => false,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => null,
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => [6, 7],
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 1,
        'discount_type_id' => null,
        'cart_wide_promotion_type_id' => 1,
        'item_wise_promotion_type_id' => null,
        'timeframe_type_id' => 3,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '10',
                'get_value' => '500',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForCartWideFlat(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [1],
        'allow_registered_member' => true,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => null,
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => [6, 7],
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 1,
        'discount_type_id' => null,
        'cart_wide_promotion_type_id' => 1,
        'item_wise_promotion_type_id' => null,
        'timeframe_type_id' => 3,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '100',
                'get_value' => '500',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForLimitedByProducts(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [1],
        'allow_registered_member' => true,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => [1],
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => '2022-01-01',
        'end_date' => '2022-01-02',
        'week_days' => null,
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => 1,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 1,
        'timeframe_type_id' => 2,
        'percentage' => 50,
        'flat_amount' => null,
        'tiers' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'is_automatic' => true,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForLimitedByCategory(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [1, 2],
        'allow_registered_member' => true,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => [1],
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => [6, 7],
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => 1,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 2,
        'timeframe_type_id' => 3,
        'percentage' => 50,
        'flat_amount' => null,
        'tiers' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForBuyTwoGetOneOrMore(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [2, 3],
        'allow_registered_member' => true,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => null,
        'buy_product_ids' => [11, 12],
        'get_product_ids' => [5, 6],
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => null,
        'month_dates' => [30, 31],
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => null,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 3,
        'timeframe_type_id' => 4,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '40',
                'get_value' => '30',
            ],
            [
                'buy_value' => '20',
                'get_value' => '10',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForBuyTwoGetFiftyOnOther(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [5],
        'allow_registered_member' => true,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => null,
        'buy_product_ids' => [9, 10],
        'get_product_ids' => [101, 102],
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => '2022-06-01',
        'end_date' => null,
        'week_days' => null,
        'month_dates' => null,
        'start_time' => '11:00',
        'end_time' => '12:00',
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => null,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 4,
        'timeframe_type_id' => 5,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '5',
                'get_value' => '10',
            ],
            [
                'buy_value' => '10',
                'get_value' => '20',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForBuyAnyThreeOrMoreGet3ThirtyOff(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [5],
        'allow_registered_member' => false,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => [203, 204],
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => null,
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => null,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 5,
        'timeframe_type_id' => 1,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '5',
                'get_value' => '25',
            ],
            [
                'buy_value' => '10',
                'get_value' => '40',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForPercentageDiscountForNextItem(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [5],
        'allow_registered_member' => false,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => [203, 204],
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => null,
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => null,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 17,
        'timeframe_type_id' => 1,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '5',
                'get_value' => '25',
            ],
            [
                'buy_value' => '10',
                'get_value' => '40',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForFlatDiscountForNextItem(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [5],
        'allow_registered_member' => false,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => [203, 204],
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => null,
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => null,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 18,
        'timeframe_type_id' => 1,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '5',
                'get_value' => '25',
            ],
            [
                'buy_value' => '10',
                'get_value' => '40',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForCheapestFree(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [5],
        'allow_registered_member' => false,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => [98, 99],
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => [3, 5],
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => null,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 6,
        'timeframe_type_id' => 3,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '35',
                'get_value' => '10',
            ],
            [
                'buy_value' => '45',
                'get_value' => '15',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForBundleBuy(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [5],
        'allow_registered_member' => false,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => [98, 99],
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => null,
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => null,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 7,
        'timeframe_type_id' => 1,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '2',
                'get_value' => '500',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForBuyTwoAndGetOneQuantityAtRmOne(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [5],
        'allow_registered_member' => false,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => [98, 99],
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => null,
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => null,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 11,
        'timeframe_type_id' => 1,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '2',
                'get_value' => '1',
                'get_quantity' => '1',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForBuyTwoGetRMFiftyOnOther(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [5],
        'allow_registered_member' => false,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => null,
        'buy_product_ids' => [9, 10],
        'get_product_ids' => [101, 102],
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => '2022-06-01',
        'end_date' => null,
        'week_days' => null,
        'month_dates' => null,
        'start_time' => '11:00',
        'end_time' => '12:00',
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => null,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 9,
        'timeframe_type_id' => 5,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '5',
                'get_value' => '10',
            ],
            [
                'buy_value' => '10',
                'get_value' => '20',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForBuyAnyThreeOrMoreGetRMThirtyOff(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [5],
        'allow_registered_member' => false,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => [203, 204],
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => null,
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => null,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 10,
        'timeframe_type_id' => 1,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '5',
                'get_value' => '25',
            ],
            [
                'buy_value' => '10',
                'get_value' => '40',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForAsPerAmountLimitedToBrandsPercentage(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [1],
        'allow_registered_member' => false,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => null,
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => [6, 7],
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => DiscountTypes::PERCENTAGE->value,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 13,
        'timeframe_type_id' => 3,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '10',
                'get_value' => '500',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForAsPerAmountLimitedToBrandsFlat(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [1],
        'allow_registered_member' => true,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => null,
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => [6, 7],
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => DiscountTypes::FLAT->value,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 13,
        'timeframe_type_id' => 3,
        'percentage' => null,
        'flat_amount' => null,
        'employee_group_ids' => null,
        'member_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '100',
                'get_value' => '500',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForAsPerAmountGetOffOnOthersPercentage(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [1],
        'allow_registered_member' => false,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => null,
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => [6, 7],
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => DiscountTypes::PERCENTAGE->value,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 14,
        'timeframe_type_id' => 3,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '10',
                'get_value' => '500',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForAsPerAmountGetOffOnOthersFlat(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [1],
        'allow_registered_member' => true,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => null,
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => [6, 7],
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => DiscountTypes::FLAT->value,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => 14,
        'timeframe_type_id' => 3,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '100',
                'get_value' => '500',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForAsPerAmountLimitedToPricePercentage(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [1],
        'allow_registered_member' => true,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => null,
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => [6, 7],
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => DiscountTypes::PERCENTAGE->value,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value,
        'timeframe_type_id' => 3,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '10',
                'get_value' => '500',
            ],
        ],
        'payment_type_ids' => [],
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}

function seedPromotionRecordForAsPerAmountLimitedToPriceFlat(): array
{
    return [
        'name' => 'Promotion name',
        'location_ids' => [1],
        'allow_registered_member' => true,
        'allow_walk_in_member' => false,
        'dream_price_applicable' => false,
        'regular_product_ids' => null,
        'buy_product_ids' => null,
        'get_product_ids' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'tag_ids' => null,
        'product_collection_ids' => null,
        'start_date' => null,
        'end_date' => null,
        'week_days' => [6, 7],
        'month_dates' => null,
        'start_time' => null,
        'end_time' => null,
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => DiscountTypes::FLAT->value,
        'cart_wide_promotion_type_id' => null,
        'item_wise_promotion_type_id' => ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value,
        'timeframe_type_id' => 3,
        'percentage' => null,
        'flat_amount' => null,
        'member_group_ids' => null,
        'employee_group_ids' => null,
        'is_automatic' => true,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'usage_type' => PromotionUsageTypes::SINGLE_USE->value,
        'promo_codes' => [],
        'tiers' => [
            [
                'buy_value' => '100',
                'get_value' => '500',
            ],
        ],
        'payment_type_ids' => [],
        'allow_employee' => false,
        'is_membership_required' => false,
        'membership_ids' => [],
    ];
}
