<?php

declare(strict_types=1);

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\Enums\DiscountApplicableTypes;
use App\Domains\ComplimentaryItemReason\ComplimentaryItemReasonQueries;
use App\Domains\ComplimentaryItemReason\Services\ComplimentaryItemService;
use App\Domains\Director\DirectorQueries;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\DreamPrice\Services\DreamPriceService;
use App\Domains\HappyHourDiscount\HappyHourDiscountQueries;
use App\Domains\HappyHourDiscount\Services\HappyHourDiscountSaleService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Promotion\Enums\CartWidePromotionTypes;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Promotion\Enums\PromotionApplicableTypes;
use App\Domains\Promotion\Enums\PromotionTimeframeTypes;
use App\Domains\Promotion\Enums\PromotionUsageTypes;
use App\Domains\Promotion\PromotionQueries;
use App\Domains\Promotion\Services\AsPerAmountGetOffOnOthersPromotionService;
use App\Domains\Promotion\Services\AsPerAmountLimitedToBrandsPromotionService;
use App\Domains\Promotion\Services\AsPerAmountLimitedToPricePromotionService;
use App\Domains\Promotion\Services\BundleBuyPromotionService;
use App\Domains\Promotion\Services\BuyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService;
use App\Domains\Promotion\Services\BuyAnyThreeOrMoreAndGetThirtyPercentageOffPromotionService;
use App\Domains\Promotion\Services\BuyThreeGetOnePromotionService;
use App\Domains\Promotion\Services\BuyTwoAndGetOneQuantityAtRM1PromotionService;
use App\Domains\Promotion\Services\BuyTwoGetFiftyPercentageOffOnOthersPromotionService;
use App\Domains\Promotion\Services\BuyTwoGetRMFiftyOffOnOthersPromotionService;
use App\Domains\Promotion\Services\CartWideAsPerAmountPromotionService;
use App\Domains\Promotion\Services\CartWideAsPerPaymentTypePromotionService;
use App\Domains\Promotion\Services\CheapestFreePromotionService;
use App\Domains\Promotion\Services\FlatDiscountForNextItemPromotionService;
use App\Domains\Promotion\Services\GiftWithPurchasePromotionService;
use App\Domains\Promotion\Services\LimitedToCategoriesPromotionService;
use App\Domains\Promotion\Services\LimitedToProductCollectionPromotionService;
use App\Domains\Promotion\Services\LimitedToProductsPromotionService;
use App\Domains\Promotion\Services\LimitedToTagsPromotionService;
use App\Domains\Promotion\Services\PercentageDiscountForNextItemPromotionService;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\SalePriceOverride\Services\SalePriceOverrideService;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\Voucher\Services\VoucherDiscountService;
use App\Domains\Voucher\VoucherQueries;
use App\Models\Company;
use App\Models\ComplimentaryItemReason;
use App\Models\Director;
use App\Models\DreamPrice;
use App\Models\Employee;
use App\Models\HappyHourDiscount;
use App\Models\Location;
use App\Models\Member;
use App\Models\MemberGroup;
use App\Models\MemberGroupMember;
use App\Models\Membership;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionMonthDate;
use App\Models\PromotionPromoCode;
use App\Models\PromotionTier;
use App\Models\PromotionWeekDay;
use App\Models\SaleDiscount;
use App\Models\SaleItemDiscount;
use App\Models\StoreManager;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->saleDiscountService = new SaleDiscountService();
    $this->checkSaleDetailsService = new CheckSaleDetailsService();
    $this->checkSaleDetailsService->appVersion = 100;

    $this->checkSaleDetailsService->company = Company::factory()->make([
        'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
        'default_country_id' => 1,
    ]);
    $this->companyId = 1;

    $this->saleDetails = [
        'offline_sale_id' => '1',
        'employee_id' => null,
        'return_items' => null,
        'vouchers' => null,
        'cashback_id' => null,
        'cashback_amount' => null,
        'cashback_round_off_amount' => null,
        'items' => [
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '10',
                'promoter_ids' => [1],
            ],
        ],
        'payments' => [
            [
                'type_id' => 1,
                'amount' => '100',
            ],
        ],
        'sale_notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
        'is_layaway' => false,
        'cart_promotion_id' => null,
        'voucher_number' => null,
        'sale_round_off_amount' => 0,
    ];

    $this->promotion = Promotion::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'name' => 'Cart Wide Automatic Promotion',
        'promotion_applicable_type_id' => 1,
        'discount_type_id' => 1,
        'cart_wide_promotion_type_id' => 1,
        'timeframe_type_id' => 1,
        'percentage' => 0,
        'flat_amount' => 0,
        'allow_registered_member' => false,
        'allow_employee' => false,
        'status' => true,
        'is_automatic' => true,
    ]);
});

test('setDetails method works as expected', function (): void {
    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->cartItems = collect([]);
    $this->checkSaleDetailsService->companyId = 1;
    $mock = $this->createPartialMock(
        SaleDiscountService::class,
        [
            'getPromotions',
            'getDreamPrices',
            'getComplimentaryItemReasons',
            'getDirectors',
            'getStoreManagers',
            'getVoucher',
            'getHappyHourDiscounts',
        ]
    );
    $promotion = new Promotion();
    $dreamPrice = new DreamPrice();
    $complimentaryItemReason = new ComplimentaryItemReason();

    $mock->expects($this->once())
        ->method('getPromotions')
        ->will($this->returnValue(collect([$promotion])));

    $mock->expects($this->once())
        ->method('getVoucher')
        ->will($this->returnValue(new Voucher()));

    $mock->expects($this->once())
        ->method('getComplimentaryItemReasons')
        ->will($this->returnValue(collect([$complimentaryItemReason])));

    $mock->expects($this->once())
        ->method('getDreamPrices')
        ->will($this->returnValue(collect([$dreamPrice])));

    $mock->expects($this->once())
        ->method('getHappyHourDiscounts')
        ->will($this->returnValue(collect([new HappyHourDiscount()])));

    $mock->setDetails($this->checkSaleDetailsService);
    $this->assertTrue($mock->promotions->first() === $promotion);
    $this->assertTrue($mock->dreamPrices->first() === $dreamPrice);
});

test(
    'It calls the getByIdsWithRelations method of the PromotionQueries class and returns proper response',
    function (): void {
        $this->saleDetails['cart_promotion_id'] = 1;
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails);
        $this->checkSaleDetailsService->companyId = $this->companyId;

        $this->checkSaleDetailsService->location = Location::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $promotion = new Promotion();

        $this->mock(PromotionQueries::class, function ($mock) use ($promotion): void {
            $mock->shouldReceive('getByIdsWithRelations')
                ->once()
                ->andReturn(collect([$promotion]));
        });

        $response = $this->saleDiscountService->getPromotions();
        $this->assertTrue($response->first() === $promotion);
    }
);

test(
    'It calls the getByIdsAndCompanyId method of the ComplimentaryItemReasonQueries class and returns proper response',
    function (): void {
        $mock = $this->createPartialMock(SaleDiscountService::class, ['getComplimentaryItemReasonIds']);

        $mock->expects($this->any())
            ->method('getComplimentaryItemReasonIds')
            ->will($this->returnValue([1, 2]));

        $this->checkSaleDetailsService->companyId = $this->companyId;

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $complimentaryItemReason = new ComplimentaryItemReason();

        $this->mock(ComplimentaryItemReasonQueries::class, function ($mock) use ($complimentaryItemReason): void {
            $mock->shouldReceive('getByIdsAndCompanyId')
                ->once()
                ->andReturn(collect([$complimentaryItemReason]));
        });

        $response = $mock->getComplimentaryItemReasons();
        $this->assertTrue($response->first() === $complimentaryItemReason);
    }
);

test(
    'getComplimentaryItemReasonIds method returns the complimentary item reason ids',
    function (): void {
        $this->saleDetails['items'][0]['complimentary_item_reason_id'] = 1;
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $response = $this->saleDiscountService->getComplimentaryItemReasonIds();
        $this->assertTrue($response === [1]);
    }
);

test(
    'It calls the getByIdsWithProductsAndLocations method of the DreamPriceQueries class and returns proper response',
    function (): void {
        $this->saleDetails['items'][0]['dream_price_id'] = 1;
        $this->saleDetails['items'][0]['dream_price_amount'] = 5;
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);
        $this->checkSaleDetailsService->companyId = $this->companyId;

        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $dreamPrice = new DreamPrice();

        $this->mock(DreamPriceQueries::class, function ($mock) use ($dreamPrice): void {
            $mock->shouldReceive('getByIdsWithProductsAndLocations')
                ->once()
                ->andReturn(collect([$dreamPrice]));
        });

        $response = $this->saleDiscountService->getDreamPrices();
        $this->assertTrue($response->first() === $dreamPrice);
    }
);

test('getPromotionIds method returns the promotion ids', function (): void {
    $cartItems = $this->saleDetails['items'];
    $cartItems[0]['promotion_id'] = 2;

    $this->checkSaleDetailsService->cartItems = collect($cartItems);
    $saleData = $this->saleDetails;
    $saleData['cart_promotion_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleData);
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $response = $this->saleDiscountService->getPromotionIds();

    $this->assertTrue($response === [2, 1]);
});

test('getDreamPriceIds method returns the promotion ids', function (): void {
    $this->saleDetails['items'][0]['dream_price_id'] = 1;
    $this->saleDetails['items'][0]['dream_price_amount'] = 5;
    $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);

    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $response = $this->saleDiscountService->getDreamPriceIds();
    $this->assertTrue($response === [1]);
});

test(
    'CheckForApplicability method throws an exception when Specified promotion is not available in our records',
    function (): void {
        $this->promotion->cart_wide_promotion_type_id = CartWidePromotionTypes::AS_PER_AMOUNT->value;
        $this->saleDiscountService->promotions = collect([$this->promotion]);

        $this->saleDiscountService->checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $this->saleDetails['cart_promotion_id'] = 2;
                $mock->saleData = new SaleData(...$this->saleDetails);

                $mock->shouldReceive('hasCartPromotion')
                    ->once()
                    ->andReturn(true);
            }
        );

        $this->saleDiscountService->checkCartWidePromotionDetails(1);
    }
)->throws(HttpException::class, 'Specified promotion is not available in our records.');

test('CheckForApplicability method calls same class methods as expected', function (): void {
    $this->promotion->cart_wide_promotion_type_id = CartWidePromotionTypes::AS_PER_AMOUNT->value;

    $mock = $this->createPartialMock(
        SaleDiscountService::class,
        [
            'checkCartWidePromoCode',
            'checkMember',
            'checkWalkInMember',
            'checkEmployee',
            'checkPromotionIsActive',
            'checkPromotionTimeFrame',
            'checkPromotionLocations',
            'checkCartWisePromotionRestrictions',
            'checkPromotionMembership',
        ]
    );

    $mock->expects($this->once())
        ->method('checkCartWidePromoCode');

    $mock->expects($this->once())
        ->method('checkMember');

    $mock->expects($this->once())
        ->method('checkWalkInMember');

    $mock->expects($this->once())
        ->method('checkEmployee');

    $mock->expects($this->once())
        ->method('checkPromotionIsActive');

    $mock->expects($this->once())
        ->method('checkPromotionTimeFrame');

    $mock->expects($this->once())
        ->method('checkPromotionLocations');

    $mock->expects($this->once())
        ->method('checkCartWisePromotionRestrictions');

    $mock->expects($this->once())
        ->method('checkPromotionMembership');

    $mock->promotions = collect([$this->promotion]);

    $mock->checkSaleDetailsService = $this->mock(
        CheckSaleDetailsService::class,
        function ($mock): void {
            $this->saleDetails['cart_promotion_id'] = 1;
            $mock->saleData = new SaleData(...$this->saleDetails);
            $mock->appVersion = 0;

            $mock->shouldReceive('hasCartPromotion')
                ->once()
                ->andReturn(true);

            $mock->shouldReceive('getCartSubtotalByDiscountApplicableType')
                ->once()
                ->andReturn(100);
        }
    );

    $this->mock(CartWideAsPerAmountPromotionService::class, function ($mock): void {
        $mock->shouldReceive('checkForApplicability')
            ->once();
    });

    $response = $mock->checkCartWidePromotionDetails(1);
    $this->assertNull($response);
});

test('it should not apply the promotion if it is manual and no cart promo code is provided', function (): void {
    $this->promotion->cart_wide_promotion_type_id = CartWidePromotionTypes::AS_PER_AMOUNT->value;

    $mock = $this->createPartialMock(
        SaleDiscountService::class,
        [
            'checkCartWidePromoCode',
            'checkMember',
            'checkWalkInMember',
            'checkEmployee',
            'checkPromotionIsActive',
            'checkPromotionTimeFrame',
            'checkPromotionLocations',
            'checkCartWisePromotionRestrictions',
            'checkPromotionMembership',
        ]
    );

    $mock->expects($this->never())
        ->method('checkCartWidePromoCode');

    $mock->expects($this->never())
        ->method('checkMember');

    $mock->expects($this->never())
        ->method('checkWalkInMember');

    $mock->expects($this->never())
        ->method('checkEmployee');

    $mock->expects($this->never())
        ->method('checkPromotionIsActive');

    $mock->expects($this->never())
        ->method('checkPromotionTimeFrame');

    $mock->expects($this->never())
        ->method('checkPromotionLocations');

    $mock->expects($this->never())
        ->method('checkCartWisePromotionRestrictions');

    $mock->expects($this->never())
        ->method('checkPromotionMembership');

    $promotion = $this->promotion;
    $promotion->is_automatic = false;

    $mock->promotions = collect([$promotion]);

    $mock->checkSaleDetailsService = $this->mock(
        CheckSaleDetailsService::class,
        function ($mock): void {
            $this->saleDetails['cart_promotion_id'] = 1;
            $mock->saleData = new SaleData(...$this->saleDetails);
            $mock->appVersion = 0;

            $mock->shouldReceive('hasCartPromotion')
                ->once()
                ->andReturn(true);

            $mock->shouldNotReceive('getCartSubtotalByDiscountApplicableType');
        }
    );

    $this->mock(CartWideAsPerAmountPromotionService::class, function ($mock): void {
        $mock->shouldNotReceive('checkForApplicability');
    });

    $response = $mock->checkCartWidePromotionDetails(1);
    $this->assertNull($response);
})->throws(
    HttpException::class,
    'The Selected Promotion Is Manual And Promo Code Is Not Provided, Specify The Promo Code.'
);

test(
    'It calls checkForApplicability method of the GiftWithPurchasePromotionService class and returns proper response',
    function (): void {
        $this->mock(GiftWithPurchasePromotionService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
                ->once();
        });

        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            [
                'checkItemWisePromoCode',
                'checkMember',
                'checkWalkInMember',
                'checkEmployee',
                'checkPromotionIsActive',
                'checkPromotionTimeFrame',
                'checkPromotionLocations',
                'applyDreamPriceOn',
                'checkItemWisePromotionRestrictions',
                'checkPromotionProductType',
            ]
        );
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value;
        $mock->promotions = collect([$this->promotion]);

        $mock->checkSaleDetailsService = $this->mock(CheckSaleDetailsService::class, function ($mock): void {
            $this->saleDetails['cart_promotion_id'] = 1;
            $mock->saleData = new SaleData(...$this->saleDetails);
            $mock->appVersion = 0;

            $mock->shouldReceive('hasDreamPrice')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasComplimentaryItem')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasPriceOverride')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasHappyHourDiscount')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasItemPromotion')
                ->once()
                ->andReturn(true);

            $mock->shouldReceive('hasProductLoyaltyPoints')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('getItemSubtotalByDiscountApplicableType')
                ->once()
                ->andReturn(100);
        });

        $mock->expects($this->once())
            ->method('checkItemWisePromoCode');

        $mock->expects($this->once())
            ->method('checkMember');

        $mock->expects($this->once())
            ->method('checkWalkInMember');

        $mock->expects($this->once())
            ->method('checkEmployee');

        $mock->expects($this->once())
            ->method('checkPromotionIsActive');

        $mock->expects($this->once())
            ->method('checkPromotionTimeFrame');

        $mock->expects($this->once())
            ->method('checkPromotionLocations');

        $mock->expects($this->once())
            ->method('applyDreamPriceOn')
            ->will($this->returnValue(100.10));

        $mock->expects($this->once())
            ->method('checkItemWisePromotionRestrictions');

        $mock->expects($this->once())
            ->method('checkPromotionProductType');

        $this->saleDetails['items'][0]['promotion_id'] = 1;

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
);

test(
    'it should not apply the promotion if it is manual and no item promo code is provided',
    function (): void {
        $this->mock(GiftWithPurchasePromotionService::class, function ($mock): void {
            $mock->shouldNotReceive('checkForApplicability');
        });

        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            [
                'checkItemWisePromoCode',
                'checkMember',
                'checkWalkInMember',
                'checkEmployee',
                'checkPromotionIsActive',
                'checkPromotionTimeFrame',
                'checkPromotionLocations',
                'applyDreamPriceOn',
                'checkItemWisePromotionRestrictions',
            ]
        );
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value;
        $promotion = $this->promotion;
        $promotion->is_automatic = false;
        $mock->promotions = collect([$promotion]);

        $mock->checkSaleDetailsService = $this->mock(CheckSaleDetailsService::class, function ($mock): void {
            $this->saleDetails['cart_promotion_id'] = 1;
            $mock->saleData = new SaleData(...$this->saleDetails);
            $mock->appVersion = 0;

            $mock->shouldReceive('hasDreamPrice')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasItemPromoCode')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasHappyHourDiscount')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasComplimentaryItem')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasItemPromotion')
                ->once()
                ->andReturn(true);

            $mock->shouldReceive('hasProductLoyaltyPoints')
                ->once()
                ->andReturn(false);

            $mock->shouldNotReceive('getItemSubtotalByDiscountApplicableType');
        });

        $mock->expects($this->never())
            ->method('checkItemWisePromoCode');

        $mock->expects($this->never())
            ->method('checkMember');

        $mock->expects($this->never())
            ->method('checkWalkInMember');

        $mock->expects($this->never())
            ->method('checkEmployee');

        $mock->expects($this->never())
            ->method('checkPromotionIsActive');

        $mock->expects($this->never())
            ->method('checkPromotionTimeFrame');

        $mock->expects($this->never())
            ->method('checkPromotionLocations');

        $mock->expects($this->never())
            ->method('applyDreamPriceOn')
            ->will($this->returnValue(100.10));

        $mock->expects($this->never())
            ->method('checkItemWisePromotionRestrictions');

        $this->saleDetails['items'][0]['promotion_id'] = 1;

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
)->throws(
    HttpException::class,
    'The Selected Promotion Is Manual And Promo Code Is Not Provided, Specify The Promo Code.'
);

test('checkItemWisePromotionDetails method returns null when promotion_id not set', function (): void {
    $this->saleDetails['items'][0]['item_discount_amount'] = 10;
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $response = $this->saleDiscountService->checkItemWisePromotionDetails(
        new Product(),
        $this->saleDetails['items'][0]
    );
    $this->assertNull($response);
});

test('checkItemWisePromotionDetails method returns null when promotion_id is null', function (): void {
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $this->saleDetails['items'][0]['item_discount_amount'] = 10;
    $this->saleDetails['items'][0]['promotion_id'] = null;
    $response = $this->saleDiscountService->checkItemWisePromotionDetails(
        new Product(),
        $this->saleDetails['items'][0]
    );
    $this->assertNull($response);
});

test('checkItemWisePromotionDetails method returns null when item_discount_amount not set', function (): void {
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $this->saleDetails['items'][0]['promotion_id'] = 1;

    $response = $this->saleDiscountService->checkItemWisePromotionDetails(
        new Product(),
        $this->saleDetails['items'][0]
    );
    $this->assertNull($response);
});

test('checkItemWisePromotionDetails method returns null when item_discount_amount is null', function (): void {
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $this->saleDetails['items'][0]['item_discount_amount'] = null;
    $this->saleDetails['items'][0]['promotion_id'] = 1;

    $response = $this->saleDiscountService->checkItemWisePromotionDetails(
        new Product(),
        $this->saleDetails['items'][0]
    );
    $this->assertNull($response);
});

test('it calls checkForApplicability method of the LimitedToProductsPromotionService class', function (): void {
    $this->saleDetails['items'][0]['promotion_id'] = 1;
    $this->saleDetails['items'][0]['item_discount_amount'] = 10;

    $mock = $this->createPartialMock(
        SaleDiscountService::class,
        [
            'checkMember',
            'checkWalkInMember',
            'checkEmployee',
            'checkPromotionIsActive',
            'checkPromotionTimeFrame',
            'checkPromotionLocations',
            'checkPromotionProductType',
        ]
    );

    $this->mock(LimitedToProductsPromotionService::class, function ($mock): void {
        $mock->shouldReceive('checkForApplicability')
            ->once();
    });

    $mock->expects($this->once())
        ->method('checkMember');

    $mock->expects($this->once())
        ->method('checkWalkInMember');

    $mock->expects($this->once())
        ->method('checkEmployee');

    $mock->expects($this->once())
        ->method('checkPromotionIsActive');

    $mock->expects($this->once())
        ->method('checkPromotionTimeFrame');

    $mock->expects($this->once())
        ->method('checkPromotionLocations');

    $mock->expects($this->once())
        ->method('checkPromotionProductType');

    $this->promotion->id = 1;
    $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value;
    $mock->promotions = collect([$this->promotion]);

    $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

    $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
});

test('it calls checkForApplicability method of the LimitedToCategoriesPromotionService class', function (): void {
    $this->saleDetails['items'][0]['promotion_id'] = 1;
    $this->saleDetails['items'][0]['item_discount_amount'] = 10;

    $mock = $this->createPartialMock(
        SaleDiscountService::class,
        [
            'checkMember',
            'checkWalkInMember',
            'checkEmployee',
            'checkPromotionIsActive',
            'checkPromotionTimeFrame',
            'checkPromotionLocations',
            'checkPromotionProductType',
        ]
    );

    $this->mock(LimitedToCategoriesPromotionService::class, function ($mock): void {
        $mock->shouldReceive('checkForApplicability')
            ->once();
    });

    $mock->expects($this->once())
        ->method('checkMember');

    $mock->expects($this->once())
        ->method('checkWalkInMember');

    $mock->expects($this->once())
        ->method('checkEmployee');

    $mock->expects($this->once())
        ->method('checkPromotionIsActive');

    $mock->expects($this->once())
        ->method('checkPromotionTimeFrame');

    $mock->expects($this->once())
        ->method('checkPromotionLocations');

    $mock->expects($this->once())
        ->method('checkPromotionProductType');

    $this->promotion->id = 1;
    $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value;
    $mock->promotions = collect([$this->promotion]);

    $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

    $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
});

test(
    'it calls checkForApplicability method of the LimitedToProductCollectionPromotionService class',
    function (): void {
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 10;

        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            [
                'checkMember',
                'checkWalkInMember',
                'checkEmployee',
                'checkPromotionIsActive',
                'checkPromotionTimeFrame',
                'checkPromotionLocations',
                'checkPromotionProductType',
            ]
        );

        $this->mock(LimitedToProductCollectionPromotionService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
                ->once();
        });

        $mock->expects($this->once())
            ->method('checkMember');

        $mock->expects($this->once())
            ->method('checkWalkInMember');

        $mock->expects($this->once())
            ->method('checkEmployee');

        $mock->expects($this->once())
            ->method('checkPromotionIsActive');

        $mock->expects($this->once())
            ->method('checkPromotionTimeFrame');

        $mock->expects($this->once())
            ->method('checkPromotionLocations');

        $mock->expects($this->once())
            ->method('checkPromotionProductType');

        $this->promotion->id = 1;
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value;
        $mock->promotions = collect([$this->promotion]);

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
);

test('it calls checkForApplicability method of the LimitedToTagsPromotionService class', function (): void {
    $this->saleDetails['items'][0]['promotion_id'] = 1;
    $this->saleDetails['items'][0]['item_discount_amount'] = 10;

    $mock = $this->createPartialMock(
        SaleDiscountService::class,
        [
            'checkMember',
            'checkWalkInMember',
            'checkEmployee',
            'checkPromotionIsActive',
            'checkPromotionTimeFrame',
            'checkPromotionLocations',
            'checkPromotionProductType',
        ]
    );

    $this->mock(LimitedToTagsPromotionService::class, function ($mock): void {
        $mock->shouldReceive('checkForApplicability')
            ->once();
    });

    $mock->expects($this->once())
        ->method('checkMember');

    $mock->expects($this->once())
        ->method('checkWalkInMember');

    $mock->expects($this->once())
        ->method('checkEmployee');

    $mock->expects($this->once())
        ->method('checkPromotionIsActive');

    $mock->expects($this->once())
        ->method('checkPromotionTimeFrame');

    $mock->expects($this->once())
        ->method('checkPromotionLocations');

    $mock->expects($this->once())
        ->method('checkPromotionProductType');

    $this->promotion->id = 1;
    $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::LIMITED_TO_TAGS->value;
    $mock->promotions = collect([$this->promotion]);

    $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

    $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
});

test(
    'it calls checkForApplicability method of the BuyThreeGetOnePromotionService class',
    function (): void {
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            [
                'checkMember',
                'checkWalkInMember',
                'checkEmployee',
                'checkPromotionIsActive',
                'checkPromotionTimeFrame',
                'checkPromotionLocations',
                'checkPromotionProductType',
            ]
        );

        $this->mock(BuyThreeGetOnePromotionService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
                ->once();
        });

        $mock->expects($this->once())->method('checkMember');
        $mock->expects($this->once())->method('checkWalkInMember');
        $mock->expects($this->once())->method('checkEmployee');
        $mock->expects($this->once())->method('checkPromotionIsActive');
        $mock->expects($this->once())->method('checkPromotionTimeFrame');
        $mock->expects($this->once())->method('checkPromotionLocations');
        $mock->expects($this->once())->method('checkPromotionProductType');

        $this->promotion->id = 1;
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::BUY_3_GET_1->value;
        $mock->promotions = collect([$this->promotion]);

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
);

test(
    'it calls checkForApplicability method of the DreamPriceService class',
    function (): void {
        $mock = $this->createPartialMock(SaleDiscountService::class, []);

        $dreamPrice = DreamPrice::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $mock->dreamPrices = collect([$dreamPrice]);

        $this->saleDetails['items'][0]['dream_price_id'] = $dreamPrice->id;
        $this->saleDetails['items'][0]['dream_price_amount'] = 5;

        $mock->checkSaleDetailsService = $this->mock(CheckSaleDetailsService::class, function ($mock): void {
            $mock->saleData = new SaleData(...$this->saleDetails);
            $mock->appVersion = 0;

            $mock->shouldReceive('hasComplimentaryItem')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasDreamPrice')
                ->once()
                ->andReturn(true);

            $mock->shouldReceive('hasItemPromotion')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasHappyHourDiscount')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasPriceOverride')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasProductLoyaltyPoints')
                ->once()
                ->andReturn(false);

            $this->mock(DreamPriceService::class, function ($mock): void {
                $mock->shouldReceive('checkForApplicability')
                    ->once();
            });
        });

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
);

test(
    'it calls checkForApplicability method of the ComplimentaryItemService class',
    function (): void {
        $mock = $this->createPartialMock(SaleDiscountService::class, []);

        $complimentaryItemReason = ComplimentaryItemReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $director = Director::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $mock->complimentaryItemReasons = collect([$complimentaryItemReason]);
        $mock->directors = collect([$director]);
        $mock->storeManagers = collect([]);

        $this->saleDetails['items'][0]['complimentary_item_reason_id'] = $complimentaryItemReason->id;
        $mock->checkSaleDetailsService = $this->mock(CheckSaleDetailsService::class, function ($mock): void {
            $mock->saleData = new SaleData(...$this->saleDetails);

            $mock->shouldReceive('hasDreamPrice')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasHappyHourDiscount')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasComplimentaryItem')
                ->once()
                ->andReturn(true);

            $this->mock(ComplimentaryItemService::class, function ($mock): void {
                $mock->shouldReceive('checkForApplicability')
                    ->once();
            });
        });

        $mock->checkSaleDetailsService->cartItems = collect([]);

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
);

test('checkMember method sets saleMismatches when Member is required for selected promotion', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $this->promotion->allow_registered_member = false;

    $this->saleDiscountService->checkMember($this->promotion);
})->throws(HttpException::class, 'Specified promotion is not allowed for the registered members.');

test('checkEmployee method sets saleMismatches when employee is required for selected promotion', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['employee_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->employee = new Employee();
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $this->promotion->allow_employee = false;

    $this->saleDiscountService->checkEmployee($this->promotion);
})->throws(HttpException::class, 'Specified promotion is not allowed for the employees.');

test('checkMember method return null when Allow Registered Member and not specified member', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = null;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $this->promotion->allow_registered_member = true;

    $response = $this->saleDiscountService->checkMember($this->promotion);
    $this->assertNull($response);
});

test('checkEmployee method return null when Allow Employee and not pass employee id', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['employee_id'] = null;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $this->promotion->allow_employee = true;

    $response = $this->saleDiscountService->checkEmployee($this->promotion);
    $this->assertNull($response);
});

test(
    'checkPromotionIsActive method sets saleMismatches when Specified cart promotion is inactive',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->promotion->status = false;

        $this->saleDiscountService->checkPromotionIsActive($this->promotion);
    }
)->throws(HttpException::class, 'Specified promotion is inactive.');

test(
    'checkPromotionTimeFrame method calls checkLimitedByDates method when promotion timeframe is limited by dates',
    function (): void {
        $this->promotion->timeframe_type_id = PromotionTimeframeTypes::LIMITED_BY_DATES->value;

        $mock = $this->createPartialMock(SaleDiscountService::class, ['checkLimitedByDates']);

        $mock->expects($this->once())
            ->method('checkLimitedByDates');

        $mock->checkPromotionTimeFrame($this->promotion);
    }
);

test(
    'checkPromotionTimeFrame method calls checkLimitByDayOfTheWeek method when promotion timeframe is limit by day of the week',
    function (): void {
        $this->promotion->timeframe_type_id = PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value;

        $mock = $this->createPartialMock(SaleDiscountService::class, ['checkLimitByDayOfTheWeek']);

        $mock->expects($this->once())
            ->method('checkLimitByDayOfTheWeek');

        $mock->checkPromotionTimeFrame($this->promotion);
    }
);

test(
    'checkPromotionTimeFrame method calls checkLimitByDayOfTheMonth method when promotion timeframe is limit by day of the month',
    function (): void {
        $this->promotion->timeframe_type_id = PromotionTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value;

        $mock = $this->createPartialMock(SaleDiscountService::class, ['checkLimitByDayOfTheMonth']);

        $mock->expects($this->once())
            ->method('checkLimitByDayOfTheMonth');

        $mock->checkPromotionTimeFrame($this->promotion);
    }
);

test(
    'checkPromotionTimeFrame method calls checkLimitByHourOfTheDay method when promotion timeframe is limit by hour of the day',
    function (): void {
        $this->promotion->timeframe_type_id = PromotionTimeframeTypes::LIMIT_BY_HOUR_OF_THE_DAY->value;

        $mock = $this->createPartialMock(SaleDiscountService::class, ['checkLimitByHourOfTheDay']);

        $mock->expects($this->once())
            ->method('checkLimitByHourOfTheDay');

        $mock->checkPromotionTimeFrame($this->promotion);
    }
);

test(
    'checkLimitedByDates method sets saleMismatches when Specified promotion is not in the range of the specified date',
    function (Carbon $happenedAt, Carbon $startDate, Carbon $endDate): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['happened_at'] = (string) $happenedAt;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->promotion->start_date = $startDate->format('Y-m-d');
        $this->promotion->end_date = $endDate->format('Y-m-d');

        $this->saleDiscountService->checkLimitedByDates($this->promotion);
    }
)->with(
    [
        [Carbon::now()->subMonthsNoOverflow(2), Carbon::now()->subMonthNoOverflow(), Carbon::now()],
        [Carbon::now()->subDay(), Carbon::now(), Carbon::now()->addMonth()],
        [Carbon::now(), Carbon::now()->addDay(), Carbon::now()->addDays(2)],
        [Carbon::now()->addDays(3), Carbon::now(), Carbon::now()->addDays(2)],
        [Carbon::now()->addMonth(), Carbon::now(), Carbon::now()->addDays(2)],
    ]
)->throws(HttpException::class);

test(
    'checkLimitByDayOfTheWeek method sets saleMismatches when the specified promotion is not allowed for specified day',
    function (): void {
        $saleDetails = $this->saleDetails;
        $happenedAt = Carbon::now()->format('Y-m-d H:i:s');
        $saleDetails['happened_at'] = $happenedAt;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $happenedAt);

        $this->promotion->weekly = collect([
            new PromotionWeekDay([
                'week_day' => $happenedAtFormat->addDay()->format('w'),
            ]),
        ]);

        $this->saleDiscountService->promotion = $this->promotion;

        $this->saleDiscountService->checkLimitByDayOfTheWeek($this->promotion);
    }
)->throws(HttpException::class, 'Promotion is not allowed on this week day.');

test(
    'checkLimitByDayOfTheMonth method sets saleMismatches when specified promotion is not allow for specified date',
    function (): void {
        $saleDetails = $this->saleDetails;
        $happenedAt = Carbon::now()->format('Y-m-d H:i:s');
        $saleDetails['happened_at'] = $happenedAt;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $happenedAt);

        $this->promotion->monthly = collect([
            new PromotionMonthDate([
                'month_date' => $happenedAtFormat->addDay()->format('d'),
            ]),
        ]);

        $this->saleDiscountService->promotion = $this->promotion;

        $this->saleDiscountService->checkLimitByDayOfTheMonth($this->promotion);
    }
)->throws(HttpException::class, 'Promotion is not allowed on this day of the month.');

test(
    'checkLimitByHourOfTheDay method sets saleMismatches when the specified promotion is not allowed for specified date',
    function (): void {
        $saleDetails = $this->saleDetails;
        $happenedAt = '2022-01-10 10:10:00';
        $saleDetails['happened_at'] = $happenedAt;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->promotion->start_date = '2022-01-11';
        $this->promotion->start_time = '10:00';
        $this->promotion->end_time = '10:30';
        $this->saleDiscountService->promotion = $this->promotion;

        $this->saleDiscountService->checkLimitByHourOfTheDay($this->promotion);
    }
)->throws(HttpException::class, 'Promotion is not allowed on this date.');

test(
    'checkLimitByHourOfTheDay method sets saleMismatches when the specified promotion is not allowed for specified date and time',
    function (string $startTime, string $endTime): void {
        $saleDetails = $this->saleDetails;
        $happenedAt = '2022-01-10 10:10:00';
        $saleDetails['happened_at'] = $happenedAt;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->promotion->start_date = '2022-01-10';
        $this->promotion->start_time = $startTime;
        $this->promotion->end_time = $endTime;
        $this->saleDiscountService->promotion = $this->promotion;

        $this->saleDiscountService->checkLimitByHourOfTheDay($this->promotion);
    }
)->with([['09:10', '10:09'], ['10:11', '10:30'], ['15:10', '23:00']])->throws(
    HttpException::class,
    'Promotion is not allowed at this time of the day.'
);

test(
    'It calls the getCartDiscountAmount method of the CartWideAsPerAmountPromotionService class and returns proper response',
    function (): void {
        $this->promotion->promotion_applicable_type_id = PromotionApplicableTypes::CART_WIDE->value;
        $this->promotion->cart_wide_promotion_type_id = CartWidePromotionTypes::AS_PER_AMOUNT->value;
        $this->promotion->id = 1;
        $this->saleDiscountService->promotions = collect([$this->promotion]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $saleData = $this->saleDetails;
        $saleData['cart_promotion_id'] = 1;
        $this->saleDiscountService->checkSaleDetailsService->saleData = new SaleData(...$saleData);
        $this->saleDiscountService->checkSaleDetailsService->cart_promotion_id = 1;

        $this->mock(CartWideAsPerAmountPromotionService::class, function ($mock): void {
            $mock->shouldReceive('getCartDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getCartDiscountAmountFor(20.20);
        $this->assertEquals(10.20, $response['cart_wide_discount']);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'It calls the getItemDiscountAmount method of the GiftWithPurchasePromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value;
        $this->promotion->id = 1;

        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->saleDiscountService->promotions = collect([$this->promotion]);

        $saleData = $this->saleDetails;
        $saleData['items'][0]['promotion_id'] = 1;
        $saleData['items'][0]['item_discount_amount'] = 10;

        $this->mock(GiftWithPurchasePromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'It calls the getItemDiscountAmount method of the ComplimentaryItemService class and returns proper response',
    function (): void {
        $complimentaryItemReason = ComplimentaryItemReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $this->saleDetails['items'][0]['complimentary_item_reason_id'] = $complimentaryItemReason->id;
        $this->saleDetails['items'][0]['complimentary_item_discount'] = 40.2;
        $this->saleDetails['items'][0]['amount'] = 123.00;

        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->saleDiscountService->checkSaleDetailsService->saleMismatches = collect([]);

        $this->mock(ComplimentaryItemService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(40.2);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($this->saleDetails['items'][0], 90.20);
        $this->assertEquals(40.2, $response['total_discount']);
    }
);

test(
    'It calls the getDiscountFor method of the DreamPriceService class and returns proper response',
    function (): void {
        $dreamPrice = DreamPrice::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $this->saleDetails['items'][0]['dream_price_id'] = $dreamPrice->id;
        $this->saleDetails['items'][0]['dream_price_amount'] = 5;
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->mock(DreamPriceService::class, function ($mock): void {
            $mock->shouldReceive('getDiscountFor')
                ->once()
                ->andReturn(40.2);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($this->saleDetails['items'][0], 90.20);
        $this->assertEquals(40.2, $response['total_discount']);
    }
);

test(
    'It calls the getCartDiscountAmountFor method of the same class and returns proper response',
    function (float $cartSubtotal, float $itemSubtotal, float $cartTotalDiscount): void {
        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            ['getCartDiscountAmountFor', 'isCartDiscountItemSequenceInAllItems']
        );

        $mock->expects($this->once())
            ->method('getCartDiscountAmountFor')
            ->will($this->returnValue([
                'total_discount' => $cartTotalDiscount,
                'cart_wide_discount' => $cartTotalDiscount,
                'voucher_discount' => 0.00,
            ]));

        $mock->expects($this->once())
            ->method('isCartDiscountItemSequenceInAllItems')
            ->will($this->returnValue(false));

        $saleData = $this->saleDetails;
        $saleData['cart_promotion_id'] = 1;

        $this->checkSaleDetailsService->saleData = new SaleData(...$saleData);
        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->promotion->cart_wide_promotion_type_id = CartWidePromotionTypes::AS_PER_AMOUNT->value;
        $mock->promotions = collect([$this->promotion]);

        $response = $mock->getItemCartDiscountAmount($cartSubtotal, $itemSubtotal, $this->saleDetails['items'][0]);
        $this->assertTrue(
            CommonFunctions::numberFormat($itemSubtotal * $cartTotalDiscount / $cartSubtotal) === $response
        );
    }
)->with([[500, 10.50, 200], [333.33, 55.66, 111.12]]);

test(
    'It calls the getCartDiscountAmountFor method of the same class and returns proper response when discount_item_sequence pass',
    function (float $cartSubtotal, float $itemSubtotal, float $cartTotalDiscount, float $responseReturn): void {
        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            ['getCartDiscountAmountFor', 'isCartDiscountItemSequenceInAllItems', 'getCalculateItemCartDiscountAmount']
        );

        $mock->expects($this->once())
            ->method('getCartDiscountAmountFor')
            ->will($this->returnValue([
                'total_discount' => $cartTotalDiscount,
                'cart_wide_discount' => $cartTotalDiscount,
                'voucher_discount' => 0.00,
            ]));

        $mock->expects($this->once())
            ->method('getCalculateItemCartDiscountAmount')
            ->will($this->returnValue($responseReturn));

        $mock->expects($this->once())
            ->method('isCartDiscountItemSequenceInAllItems')
            ->will($this->returnValue(true));

        $saleData = $this->saleDetails;
        $saleData['cart_promotion_id'] = 1;

        $this->checkSaleDetailsService->saleData = new SaleData(...$saleData);
        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->promotion->cart_wide_promotion_type_id = CartWidePromotionTypes::AS_PER_AMOUNT->value;
        $mock->promotions = collect([$this->promotion]);

        $response = $mock->getItemCartDiscountAmount($cartSubtotal, $itemSubtotal, $this->saleDetails['items'][0]);
        $this->assertTrue($responseReturn === $response);
    }
)->with([[500, 10.50, 200, 100.00], [333.33, 55.66, 111.12, 102.10]]);

test(
    'It calls the getItemDiscountAmountFor method of the same class and returns proper response',
    function (): void {
        $mock = $this->createPartialMock(SaleDiscountService::class, ['getItemDiscountAmountFor']);

        $mock->expects($this->once())
            ->method('getItemDiscountAmountFor')
            ->will($this->returnValue([
                'total_discount' => 100,
            ]));

        $this->checkSaleDetailsService->cartItems = collect([$this->saleDetails]);
        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $response = $mock->getTotalItemDiscountAmount(20.20);
        $this->assertEquals(100, $response);
    }
);

test(
    'It calls the getItemDiscountAmount method of the LimitedToProductsPromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value;
        $this->promotion->id = 1;
        $this->saleDiscountService->promotions = collect([$this->promotion]);
        $this->checkSaleDetailsService->cartItems = collect([$this->saleDetails]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $saleData = $this->saleDetails;
        $saleData['items'][0]['promotion_id'] = 1;
        $saleData['items'][0]['item_discount_amount'] = 1;

        $this->mock(LimitedToProductsPromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'It calls the getItemDiscountAmount method of the LimitedToCategoriesPromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value;
        $this->promotion->id = 1;
        $this->saleDiscountService->promotions = collect([$this->promotion]);
        $this->checkSaleDetailsService->cartItems = collect([$this->saleDetails]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $saleData = $this->saleDetails;
        $saleData['items'][0]['promotion_id'] = 1;
        $saleData['items'][0]['item_discount_amount'] = 1;

        $this->mock(LimitedToCategoriesPromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'It calls the getItemDiscountAmount method of the LimitedToProductCollectionPromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::LIMITED_TO_PRODUCT_COLLECTION->value;
        $this->promotion->id = 1;
        $this->saleDiscountService->promotions = collect([$this->promotion]);
        $this->checkSaleDetailsService->cartItems = collect([$this->saleDetails]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $saleData = $this->saleDetails;
        $saleData['items'][0]['promotion_id'] = 1;
        $saleData['items'][0]['item_discount_amount'] = 1;

        $this->mock(LimitedToProductCollectionPromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'It calls the getItemDiscountAmount method of the BuyThreeGetOnePromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::BUY_3_GET_1->value;
        $this->promotion->id = 1;
        $this->saleDiscountService->promotions = collect([$this->promotion]);
        $this->checkSaleDetailsService->cartItems = collect([$this->saleDetails]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $saleData = $this->saleDetails;
        $saleData['items'][0]['promotion_id'] = 1;
        $saleData['items'][0]['item_discount_amount'] = 1;

        $this->mock(BuyThreeGetOnePromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'it calls checkForApplicability method of the BundleBuyPromotionService class',
    function (): void {
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 10;

        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            [
                'checkMember',
                'checkWalkInMember',
                'checkEmployee',
                'checkPromotionIsActive',
                'checkPromotionTimeFrame',
                'checkPromotionLocations',
                'checkPromotionProductType',
            ]
        );

        $this->mock(BundleBuyPromotionService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
            ->once();
        });

        $mock->expects($this->once())
            ->method('checkMember');

        $mock->expects($this->once())
            ->method('checkWalkInMember');

        $mock->expects($this->once())
            ->method('checkEmployee');

        $mock->expects($this->once())
            ->method('checkPromotionIsActive');

        $mock->expects($this->once())
            ->method('checkPromotionTimeFrame');

        $mock->expects($this->once())
            ->method('checkPromotionLocations');

        $mock->expects($this->once())
            ->method('checkPromotionProductType');

        $this->promotion->id = 1;
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::BUNDLE_BUY->value;
        $mock->promotions = collect([$this->promotion]);

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
);

test(
    'It calls the getItemDiscountAmount method of the BundleBuyPromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::BUNDLE_BUY->value;

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'ABC',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'upc' => 'abd123',
            'retail_price' => 10.00,
            'has_batch' => false,
            'status' => false,
        ]);

        $promotionTier = PromotionTier::factory()->make([
            'promotion_id' => 1,
            'buy_value' => 10,
            'get_value' => 90,
        ]);

        $this->promotion->id = 1;
        $this->promotion->regularProducts = collect([$product]);
        $this->promotion->promotionTiers = collect([$promotionTier]);

        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 90;
        $this->saleDetails['items'][0]['group_id'] = 1;

        $this->saleDiscountService->promotions = collect([$this->promotion]);
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);

        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->mock(BundleBuyPromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($this->saleDetails['items'][0]);
        $this->assertEquals(10, $response['total_discount']);
    }
);

test(
    'it calls checkForApplicability method of the BuyTwoGetFiftyPercentageOffOnOthersPromotionService class',
    function (): void {
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 10;

        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            [
                'checkMember',
                'checkWalkInMember',
                'checkEmployee',
                'checkPromotionIsActive',
                'checkPromotionTimeFrame',
                'checkPromotionLocations',
                'checkPromotionProductType',
            ]
        );

        $this->mock(BuyTwoGetFiftyPercentageOffOnOthersPromotionService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
            ->once();
        });

        $mock->expects($this->once())
            ->method('checkMember');

        $mock->expects($this->once())
            ->method('checkWalkInMember');

        $mock->expects($this->once())
            ->method('checkEmployee');

        $mock->expects($this->once())
            ->method('checkPromotionIsActive');

        $mock->expects($this->once())
            ->method('checkPromotionTimeFrame');

        $mock->expects($this->once())
            ->method('checkPromotionLocations');

        $mock->expects($this->once())
            ->method('checkPromotionProductType');

        $this->promotion->id = 1;
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value;
        $mock->promotions = collect([$this->promotion]);

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
);

test(
    'It calls the getItemDiscountAmount method of the BuyTwoGetFiftyPercentageOffOnOthersPromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::BUY_2_GET_50_OFF_ON_OTHERS->value;
        $this->promotion->id = 1;

        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->saleDiscountService->promotions = collect([$this->promotion]);

        $saleData = $this->saleDetails;
        $saleData['items'][0]['promotion_id'] = 1;
        $saleData['items'][0]['item_discount_amount'] = 10;

        $this->mock(BuyTwoGetFiftyPercentageOffOnOthersPromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'it calls checkForApplicability method of the CheapestFreePromotionService class',
    function (): void {
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 10;

        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            [
                'checkMember',
                'checkWalkInMember',
                'checkEmployee',
                'checkPromotionIsActive',
                'checkPromotionTimeFrame',
                'checkPromotionLocations',
                'checkPromotionProductType',
            ]
        );

        $this->mock(CheapestFreePromotionService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
            ->once();
        });

        $mock->expects($this->once())
            ->method('checkMember');

        $mock->expects($this->once())
            ->method('checkWalkInMember');

        $mock->expects($this->once())
            ->method('checkEmployee');

        $mock->expects($this->once())
            ->method('checkPromotionIsActive');

        $mock->expects($this->once())
            ->method('checkPromotionTimeFrame');

        $mock->expects($this->once())
            ->method('checkPromotionLocations');

        $mock->expects($this->once())
            ->method('checkPromotionProductType');

        $this->promotion->id = 1;
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::CHEAPEST_FREE->value;
        $mock->promotions = collect([$this->promotion]);

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
);

test(
    'It calls the getItemDiscountAmount method of the CheapestFreePromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::CHEAPEST_FREE->value;
        $this->promotion->id = 1;
        $this->saleDiscountService->promotions = collect([$this->promotion]);
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $saleData = $this->saleDetails;
        $saleData['items'][0]['promotion_id'] = 1;
        $saleData['items'][0]['item_discount_amount'] = 1;

        $this->mock(CheapestFreePromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'it calls checkForApplicability method of the BuyAnyThreeOrMoreAndGetThirtyPercentageOffPromotionService class',
    function (): void {
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 10;

        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            [
                'checkMember',
                'checkWalkInMember',
                'checkEmployee',
                'checkPromotionIsActive',
                'checkPromotionTimeFrame',
                'checkPromotionLocations',
                'checkPromotionProductType',
            ]
        );

        $this->mock(BuyAnyThreeOrMoreAndGetThirtyPercentageOffPromotionService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
            ->once();
        });

        $mock->expects($this->once())
            ->method('checkMember');

        $mock->expects($this->once())
            ->method('checkWalkInMember');

        $mock->expects($this->once())
            ->method('checkEmployee');

        $mock->expects($this->once())
            ->method('checkPromotionIsActive');

        $mock->expects($this->once())
            ->method('checkPromotionTimeFrame');

        $mock->expects($this->once())
            ->method('checkPromotionLocations');

        $mock->expects($this->once())
            ->method('checkPromotionProductType');

        $this->promotion->id = 1;
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value;
        $mock->promotions = collect([$this->promotion]);

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
);

test(
    'It calls the getItemDiscountAmount method of the BuyAnyThreeOrMoreAndGetThirtyPercentageOffPromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_30_OFF->value;
        $this->promotion->id = 1;

        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->saleDiscountService->promotions = collect([$this->promotion]);

        $saleData = $this->saleDetails;
        $saleData['items'][0]['promotion_id'] = 1;
        $saleData['items'][0]['item_discount_amount'] = 10;

        $this->mock(BuyAnyThreeOrMoreAndGetThirtyPercentageOffPromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test('applyDreamPriceOn method returns cart item total after apply dream price', function (): void {
    $this->saleDetails['items'][0]['dream_price_amount'] = 10;

    $this->mock(DreamPriceService::class, function ($mock): void {
        $mock->shouldReceive('getDiscountFor')
            ->once()
            ->andReturn(10);
    });

    $this->saleDiscountService->checkSaleDetailsService = $this->mock(
        CheckSaleDetailsService::class,
        function ($mock): void {
            $mock->appVersion = 0;
            $mock->shouldReceive('getItemSubtotal')
                    ->once()
                    ->andReturn(100);
            $mock->shouldReceive('hasDreamPrice')
                    ->once()
                    ->andReturn(true);
        }
    );

    $response = $this->saleDiscountService->applyDreamPriceOn($this->saleDetails['items'][0]);

    $this->assertTrue(90.00 === $response);
});

test(
    'applyDreamPriceOn method returns cart item total after apply dream price when version more then 44',
    function (): void {
        $this->saleDetails['items'][0]['dream_price_amount'] = 10;

        $this->mock(DreamPriceService::class, function ($mock): void {
            $mock->shouldReceive('getDiscountFor')
                ->once()
                ->andReturn(10);
        });

        $this->saleDiscountService->checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->appVersion = 1520;
                $mock->shouldReceive('getItemSubtotal')
                        ->once()
                        ->andReturn(100);
                $mock->shouldReceive('hasDreamPrice')
                        ->once()
                        ->andReturn(true);
            }
        );

        $response = $this->saleDiscountService->applyDreamPriceOn($this->saleDetails['items'][0]);

        $this->assertTrue(90.00 === $response);
    }
);

test(
    'groupItemsSubtotalWithApplyDreamPriceAndPriceOverride method returns cart group items total after apply dream price',
    function (): void {
        $this->mock(DreamPriceService::class, function ($mock): void {
            $mock->shouldReceive('getDiscountFor')
                ->once()
                ->andReturn(10);
        });

        $this->saleDetails['items'][0]['dream_price_amount'] = 10;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 10;
        $this->saleDetails['items'][0]['group_id'] = 1;

        $this->saleDiscountService->checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->cartItems = collect($this->saleDetails['items']);

                $mock->shouldReceive('getItemSubtotal')
                    ->once()
                    ->andReturn(100);

                $mock->shouldReceive('hasDreamPrice')
                    ->once()
                    ->andReturn(true);

                $mock->shouldReceive('getItemSubtotalByDiscountApplicableType')
                    ->once()
                    ->andReturn(90);
            }
        );

        $response = $this->saleDiscountService->groupItemsSubtotalWithApplyDreamPriceAndPriceOverride(
            $this->saleDetails['items'][0]
        );

        $this->assertTrue(90.00 === $response);
    }
);

test(
    'groupItemsSubtotalWithApplyDreamPriceAndPriceOverride method returns zero if group_id is not specified',
    function (): void {
        $this->saleDetails['items'][0]['dream_price_amount'] = 10;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 10;

        $this->saleDiscountService->checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->cartItems = collect($this->saleDetails['items']);
            }
        );

        $response = $this->saleDiscountService->groupItemsSubtotalWithApplyDreamPriceAndPriceOverride(
            $this->saleDetails['items'][0]
        );

        $this->assertTrue(0.0 === $response);
    }
);

test('getGroupItems method returns cart group items', function (): void {
    $this->saleDetails['items'][0]['promotion_id'] = 1;
    $this->saleDetails['items'][0]['item_discount_amount'] = 10;
    $this->saleDetails['items'][0]['group_id'] = 1;

    $response = $this->saleDiscountService->getGroupItems(
        new Collection($this->saleDetails['items']),
        $this->saleDetails['items'][0]
    );

    expect($response->first())
            ->toHaveKey('price', '10.00')
            ->toHaveKey('quantity', 10)
            ->toHaveKey('group_id', 1)
            ->toHaveKey('promotion_id', 1)
            ->toHaveKey('item_discount_amount', 10);
});

test('getBuyGroupItems method returns cart group items', function (): void {
    $this->saleDetails['items'][0]['promotion_id'] = 1;
    $this->saleDetails['items'][0]['item_discount_amount'] = 0;
    $this->saleDetails['items'][0]['group_id'] = 1;

    $response = $this->saleDiscountService->getBuyGroupItems(
        new Collection($this->saleDetails['items']),
        $this->saleDetails['items'][0]
    );

    expect($response->first())
            ->toHaveKey('price', '10.00')
            ->toHaveKey('quantity', 10)
            ->toHaveKey('group_id', 1)
            ->toHaveKey('promotion_id', 1)
            ->toHaveKey('item_discount_amount', 0);
});

test(
    'It calls the getByVoucherNumberAndCompanyIdWithProductsAndCategories method of the VoucherQueries class and returns proper response',
    function (): void {
        $this->saleDetails['voucher_number'] = 'ABC123';
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->companyId = $this->companyId;

        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $voucher = new Voucher();

        $this->mock(VoucherQueries::class, function ($mock) use ($voucher): void {
            $mock->shouldReceive('getByVoucherNumberAndCompanyIdWithProductsAndCategories')
                ->once()
                ->andReturn($voucher);
        });

        $response = $this->saleDiscountService->getVoucher();
        $this->assertTrue($response === $voucher);
    }
);

test('It calls the checkForApplicability method of the VoucherDiscountService class', function (): void {
    $this->saleDetails['voucher_number'] = 'ABC123';
    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->companyId = $this->companyId;

    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $this->saleDiscountService->voucher = new Voucher();

    $this->mock(VoucherDiscountService::class, function ($mock): void {
        $mock->shouldReceive('checkForApplicability')
            ->once();
    });

    $this->saleDiscountService->checkVoucherDetails(10.20);
});

test(
    'It calls the getDiscountAmount method of the VoucherDiscountService class and returns proper response',
    function (): void {
        $saleData = $this->saleDetails;
        $saleData['voucher_number'] = 'ABC123';
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleData);

        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->saleDiscountService->voucher = new Voucher();

        $this->mock(VoucherDiscountService::class, function ($mock): void {
            $mock->shouldReceive('getDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getCartDiscountAmountFor(20.20);
        $this->assertEquals(10.20, $response['voucher_discount']);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'It calls the getItemDiscountAmount method of the BuyTwoGetRMFiftyOffOnOthersPromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::BUY_2_GET_RM_50_OFF_ON_OTHERS->value;
        $this->promotion->id = 1;

        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->saleDiscountService->promotions = collect([$this->promotion]);

        $saleData = $this->saleDetails;
        $saleData['items'][0]['promotion_id'] = 1;
        $saleData['items'][0]['item_discount_amount'] = 10;

        $this->mock(BuyTwoGetRMFiftyOffOnOthersPromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'It calls the getItemDiscountAmount method of the BuyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::BUY_ANY_3_OR_MORE_AND_GET_RM_30_OFF->value;
        $this->promotion->id = 1;

        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->saleDiscountService->promotions = collect([$this->promotion]);

        $saleData = $this->saleDetails;
        $saleData['items'][0]['promotion_id'] = 1;
        $saleData['items'][0]['item_discount_amount'] = 10;

        $this->mock(BuyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test('the getDirectors method return empty collection if complimentary reason not found', function (): void {
    $this->checkSaleDetailsService->companyId = 1;
    $this->saleDiscountService->complimentaryItemReasons = collect([]);

    $response = $this->saleDiscountService->getDirectors();

    expect($response->toArray())->toBeEmpty();
});

test('the getDirectors method return empty collection if director ids not found', function (): void {
    $complimentaryItemReason = ComplimentaryItemReason::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $this->saleDiscountService->complimentaryItemReasons = collect([$complimentaryItemReason]);
    $this->checkSaleDetailsService->companyId = 1;
    $this->checkSaleDetailsService->cartItems = collect([]);

    $response = $this->saleDiscountService->getDirectors();

    expect($response->toArray())->toBeEmpty();
});

test('the getDirectors method returns list of the directors', function (): void {
    $complimentaryItemReason = ComplimentaryItemReason::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $director = Director::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $this->saleDiscountService->complimentaryItemReasons = collect([$complimentaryItemReason]);
    $this->checkSaleDetailsService->companyId = 1;
    $this->checkSaleDetailsService->cartItems = collect([
        [
            'director_id' => 1,
        ],
    ]);

    $this->mock(DirectorQueries::class, function ($mock) use ($director): void {
        $mock->shouldReceive('getByIds')
            ->once()
            ->andReturn(collect([$director]));
    });

    $response = $this->saleDiscountService->getDirectors();

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'passcode', 'employee_id']);
});

test('the getStoreManagers method return empty collection if complimentary reason not found', function (): void {
    $this->checkSaleDetailsService->companyId = 1;
    $this->saleDiscountService->complimentaryItemReasons = collect([]);

    $response = $this->saleDiscountService->getStoreManagers();

    expect($response->toArray())->toBeEmpty();
});

test('the getStoreManagers method return empty collection if director ids not found', function (): void {
    $complimentaryItemReason = ComplimentaryItemReason::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $this->saleDiscountService->complimentaryItemReasons = collect([$complimentaryItemReason]);
    $this->checkSaleDetailsService->companyId = 1;
    $this->checkSaleDetailsService->cartItems = collect([]);

    $response = $this->saleDiscountService->getStoreManagers();

    expect($response->toArray())->toBeEmpty();
});

test('the getStoreManagers method returns list of the storeManagers', function (): void {
    $complimentaryItemReason = ComplimentaryItemReason::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $this->saleDiscountService->complimentaryItemReasons = collect([$complimentaryItemReason]);
    $this->checkSaleDetailsService->companyId = 1;
    $this->checkSaleDetailsService->cartItems = collect([
        [
            'store_manager_id' => 1,
        ],
    ]);

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getByIds')
            ->once()
            ->andReturn(collect([$storeManager]));
    });

    $response = $this->saleDiscountService->getStoreManagers();

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'passcode', 'employee_id']);
});

test(
    'it calls checkForApplicability method of the BuyTwoAndGetOneQuantityAtRM1PromotionService class',
    function (): void {
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 10;

        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            [
                'checkMember',
                'checkWalkInMember',
                'checkEmployee',
                'checkPromotionIsActive',
                'checkPromotionTimeFrame',
                'checkPromotionLocations',
                'checkPromotionProductType',
            ]
        );

        $this->mock(BuyTwoAndGetOneQuantityAtRM1PromotionService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
            ->once();
        });

        $mock->expects($this->once())
            ->method('checkMember');

        $mock->expects($this->once())
            ->method('checkWalkInMember');

        $mock->expects($this->once())
            ->method('checkEmployee');

        $mock->expects($this->once())
            ->method('checkPromotionIsActive');

        $mock->expects($this->once())
            ->method('checkPromotionTimeFrame');

        $mock->expects($this->once())
            ->method('checkPromotionLocations');

        $mock->expects($this->once())
            ->method('checkPromotionProductType');

        $this->promotion->id = 1;
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value;
        $mock->promotions = collect([$this->promotion]);

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
);

test(
    'It calls the getItemDiscountAmount method of the BuyTwoAndGetOneQuantityAtRM1PromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::BUY_2_AND_GET_1_QUANTITY_AT_RM1->value;

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'ABC',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'upc' => 'abd123',
            'retail_price' => 10.00,
            'has_batch' => false,
            'status' => false,
        ]);

        $promotionTier = PromotionTier::factory()->make([
            'promotion_id' => 1,
            'buy_value' => 10,
            'get_value' => 90,
        ]);

        $this->promotion->id = 1;
        $this->promotion->regularProducts = collect([$product]);
        $this->promotion->promotionTiers = collect([$promotionTier]);

        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 90;
        $this->saleDetails['items'][0]['group_id'] = 1;

        $this->saleDiscountService->promotions = collect([$this->promotion]);
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);

        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->mock(BuyTwoAndGetOneQuantityAtRM1PromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($this->saleDetails['items'][0]);
        $this->assertEquals(10, $response['total_discount']);
    }
);

test('hasGroupId method returns boolean as expected', function (): void {
    $response = $this->saleDiscountService->hasGroupId($this->saleDetails['items'][0]);
    $this->assertFalse($response);

    $this->saleDetails['items'][0]['group_id'] = 1;
    $response = $this->saleDiscountService->hasGroupId($this->saleDetails['items'][0]);
    $this->assertTrue($response);
});

test('groupItems method returns cart group items', function (): void {
    $this->saleDetails['items'][0]['promotion_id'] = 1;
    $this->saleDetails['items'][0]['item_discount_amount'] = 10;
    $this->saleDetails['items'][0]['group_id'] = 1;

    $response = $this->saleDiscountService->groupItems(
        new Collection($this->saleDetails['items']),
        $this->saleDetails['items'][0]
    );

    expect($response->first())
            ->toHaveKey('price', '10.00')
            ->toHaveKey('quantity', 10)
            ->toHaveKey('group_id', 1)
            ->toHaveKey('promotion_id', 1)
            ->toHaveKey('item_discount_amount', 10);
});

test(
    'checkPriceOverrideForCartDetails calls the checkForApplicability method of the SalePriceOverrideService class',
    function (): void {
        $checkSaleDetailsService = new CheckSaleDetailsService();
        $checkSaleDetailsService->appVersion = 100;
        $checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $this->saleDiscountService->checkSaleDetailsService = $checkSaleDetailsService;
        $this->mock(SalePriceOverrideService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
                ->once();
        });

        $this->saleDiscountService->checkPriceOverrideForCartDetails(10.20);
    }
);

test(
    'It calls the getDiscountAmount method of the SalePriceOverrideService class and returns proper response',
    function (): void {
        $saleData = $this->saleDetails;
        $saleData['cashier_id'] = 1;
        $saleData['cart_price_override_amount'] = 10.20;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleData);

        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->mock(SalePriceOverrideService::class, function ($mock): void {
            $mock->shouldReceive('getDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getCartDiscountAmountFor(20.20);
        $this->assertEquals(10.20, $response['price_override_discount']);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'It calls the getItemDiscountAmount method of the AsPerAmountLimitedToBrandsPromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value;
        $this->promotion->id = 1;

        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->saleDiscountService->promotions = collect([$this->promotion]);

        $saleData = $this->saleDetails;
        $saleData['items'][0]['promotion_id'] = 1;
        $saleData['items'][0]['item_discount_amount'] = 10;

        $this->mock(AsPerAmountLimitedToBrandsPromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'it calls checkForApplicability method of the AsPerAmountLimitedToBrandsPromotionService class',
    function (): void {
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 10;

        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            [
                'checkMember',
                'checkWalkInMember',
                'checkEmployee',
                'checkPromotionIsActive',
                'checkPromotionTimeFrame',
                'checkPromotionLocations',
                'checkPromotionProductType',
            ]
        );

        $this->mock(AsPerAmountLimitedToBrandsPromotionService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
                ->once();
        });

        $mock->expects($this->once())
            ->method('checkMember');

        $mock->expects($this->once())
            ->method('checkWalkInMember');

        $mock->expects($this->once())
            ->method('checkEmployee');

        $mock->expects($this->once())
            ->method('checkPromotionIsActive');

        $mock->expects($this->once())
            ->method('checkPromotionTimeFrame');

        $mock->expects($this->once())
            ->method('checkPromotionLocations');

        $mock->expects($this->once())
            ->method('checkPromotionProductType');

        $this->promotion->id = 1;
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_BRANDS->value;
        $mock->promotions = collect([$this->promotion]);

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
);

test(
    'It calls the getItemDiscountAmount method of the AsPerAmountGetOffOnOthersPromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value;
        $this->promotion->id = 1;

        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->saleDiscountService->promotions = collect([$this->promotion]);

        $saleData = $this->saleDetails;
        $saleData['items'][0]['promotion_id'] = 1;
        $saleData['items'][0]['item_discount_amount'] = 10;

        $this->mock(AsPerAmountGetOffOnOthersPromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'it calls checkForApplicability method of the AsPerAmountGetOffOnOthersPromotionService class',
    function (): void {
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 10;

        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            [
                'checkMember',
                'checkWalkInMember',
                'checkEmployee',
                'checkPromotionIsActive',
                'checkPromotionTimeFrame',
                'checkPromotionLocations',
                'checkPromotionProductType',
            ]
        );

        $this->mock(AsPerAmountGetOffOnOthersPromotionService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
                ->once();
        });

        $mock->expects($this->once())
            ->method('checkMember');

        $mock->expects($this->once())
            ->method('checkWalkInMember');

        $mock->expects($this->once())
            ->method('checkEmployee');

        $mock->expects($this->once())
            ->method('checkPromotionIsActive');

        $mock->expects($this->once())
            ->method('checkPromotionTimeFrame');

        $mock->expects($this->once())
            ->method('checkPromotionLocations');

        $mock->expects($this->once())
            ->method('checkPromotionProductType');

        $this->promotion->id = 1;
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::AS_PER_AMOUNT_GET_OFF_ON_OTHERS->value;
        $mock->promotions = collect([$this->promotion]);

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
);

test(
    'buyItemsSubtotalWithApplyDreamPriceAndPriceOverride method returns cart group items total after apply dream price',
    function (): void {
        $this->mock(DreamPriceService::class, function ($mock): void {
            $mock->shouldReceive('getDiscountFor')
                ->once()
                ->andReturn(10);
        });

        $this->saleDetails['items'][0]['dream_price_amount'] = 10;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 0;
        $this->saleDetails['items'][0]['group_id'] = 1;

        $this->saleDiscountService->checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->cartItems = collect($this->saleDetails['items']);

                $mock->shouldReceive('getItemSubtotal')
                    ->once()
                    ->andReturn(100);

                $mock->shouldReceive('hasDreamPrice')
                    ->once()
                    ->andReturn(true);

                $mock->shouldReceive('getItemSubtotalByDiscountApplicableType')
                    ->once()
                    ->andReturn(90);
            }
        );

        $response = $this->saleDiscountService->buyItemsSubtotalWithApplyDreamPriceAndPriceOverride(
            $this->saleDetails['items'][0]
        );

        $this->assertTrue(90.00 === $response);
    }
);

test(
    'buyItemsSubtotalWithApplyDreamPriceAndPriceOverride method returns zero if group_id is not specified',
    function (): void {
        $this->saleDetails['items'][0]['dream_price_amount'] = 10;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 0;

        $this->saleDiscountService->checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->cartItems = collect($this->saleDetails['items']);
            }
        );

        $response = $this->saleDiscountService->buyItemsSubtotalWithApplyDreamPriceAndPriceOverride(
            $this->saleDetails['items'][0]
        );

        $this->assertTrue(0.0 === $response);
    }
);

test(
    'checkPromotionLocations method return null when lcoation not set in promotion',
    function (): void {
        $this->promotion->locations = collect([]);

        $response = $this->saleDiscountService->checkPromotionLocations($this->promotion);
        $this->assertNull($response);
    }
);

test(
    'checkPromotionLocations method return null when location available in promotion',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'name' => 'test',
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->promotion->locations = collect([$location]);
        $this->checkSaleDetailsService->location = $location;
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $response = $this->saleDiscountService->checkPromotionLocations($this->promotion);
        $this->assertNull($response);
    }
);

test(
    'checkPromotionLocations method set mismatches when location not available in promotion',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'name' => 'test',
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);
        $this->checkSaleDetailsService->location = Location::factory()->make([
            'id' => 2,
            'name' => 'test',
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->promotion->locations = collect([$location]);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->saleDiscountService->checkPromotionLocations($this->promotion);
    }
)->throws(HttpException::class, 'Specified promotion is not available for the location test');

test(
    'It calls the getItemDiscountAmount method of the AsPerAmountLimitedToPricePromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value;
        $this->promotion->id = 1;

        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->saleDiscountService->promotions = collect([$this->promotion]);

        $saleData = $this->saleDetails;
        $saleData['items'][0]['promotion_id'] = 1;
        $saleData['items'][0]['item_discount_amount'] = 10;

        $this->mock(AsPerAmountLimitedToPricePromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'it calls checkForApplicability method of the AsPerAmountLimitedToPricePromotionService class',
    function (): void {
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 10;

        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            [
                'checkMember',
                'checkWalkInMember',
                'checkEmployee',
                'checkPromotionIsActive',
                'checkPromotionTimeFrame',
                'checkPromotionLocations',
                'checkPromotionProductType',
            ]
        );

        $this->mock(AsPerAmountLimitedToPricePromotionService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
                ->once();
        });

        $mock->expects($this->once())
            ->method('checkMember');

        $mock->expects($this->once())
            ->method('checkWalkInMember');

        $mock->expects($this->once())
            ->method('checkEmployee');

        $mock->expects($this->once())
            ->method('checkPromotionIsActive');

        $mock->expects($this->once())
            ->method('checkPromotionTimeFrame');

        $mock->expects($this->once())
            ->method('checkPromotionLocations');

        $mock->expects($this->once())
            ->method('checkPromotionProductType');

        $this->promotion->id = 1;
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::AS_PER_AMOUNT_LIMITED_TO_PRICE->value;
        $mock->promotions = collect([$this->promotion]);

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
);

test('checkMember method return null when member not required in promotion', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = null;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $this->promotion->allow_registered_member = false;

    $response = $this->saleDiscountService->checkMember($this->promotion);
    $this->assertNull($response);
    $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
});

test('checkMember method return null when promotion member group not selected', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $this->promotion->memberGroups = collect([]);
    $this->promotion->allow_registered_member = true;

    $response = $this->saleDiscountService->checkMember($this->promotion);
    $this->assertNull($response);
    $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
});

test('checkMember method return null when member group is in promotion member group', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->member = Member::factory()->make([
        'id' => 1,
        'company_id' => '1',
        'type_id' => '1',
        'title_id' => '1',
        'race_id' => '1',
        'gender_id' => '1',
        'created_location_id' => '1',
    ]);

    $this->checkSaleDetailsService->member->memberGroupMembers = collect([
        MemberGroupMember::factory()->make([
            'member_id' => 1,
            'member_group_id' => 1,
        ]),
    ]);

    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $this->promotion->memberGroups = collect([
        MemberGroup::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Test',
            'code' => '123456',
        ]),
    ]);
    $this->promotion->allow_registered_member = true;

    $response = $this->saleDiscountService->checkMember($this->promotion);
    $this->assertNull($response);
    $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
});

test('checkMember method sets saleMismatches when member group is not in promotion member group', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->member = Member::factory()->make([
        'company_id' => '1',
        'type_id' => '1',
        'title_id' => '1',
        'race_id' => '1',
        'gender_id' => '1',
        'created_location_id' => '1',
        'group_id' => '2',
    ]);

    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $this->promotion->memberGroups = collect([
        MemberGroup::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Test',
            'code' => '123456',
        ]),
    ]);

    $this->promotion->allow_registered_member = true;

    $response = $this->saleDiscountService->checkMember($this->promotion);
    $this->assertNull($response);
})->throws(HttpException::class, 'Member is not valid for the specified promotion.');

test('isMemberAttached method returns true when member id is specified', function (): void {
    $this->saleDiscountService->checkSaleDetailsService = $this->mock(
        CheckSaleDetailsService::class,
        function ($mock): void {
            $mock->shouldReceive('isMemberAttached')
                ->once()
                ->andReturn(true);
        }
    );

    $response = $this->saleDiscountService->isMemberAttached();
    $this->assertTrue($response);
});

test('isMemberAttached method returns true when member details is specified', function (): void {
    $this->saleDiscountService->checkSaleDetailsService = $this->mock(
        CheckSaleDetailsService::class,
        function ($mock): void {
            $mock->shouldReceive('isMemberAttached')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('hasMemberDetails')
                ->once()
                ->andReturn(true);
        }
    );

    $response = $this->saleDiscountService->isMemberAttached();
    $this->assertTrue($response);
});

test('isMemberAttached method returns false when member details not specified', function (): void {
    $this->saleDiscountService->checkSaleDetailsService = $this->mock(
        CheckSaleDetailsService::class,
        function ($mock): void {
            $mock->shouldReceive('isMemberAttached')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('hasMemberDetails')
                ->once()
                ->andReturn(false);
        }
    );

    $response = $this->saleDiscountService->isMemberAttached();
    $this->assertFalse($response);
});

test(
    'applyDreamPriceAndItemPromotionOn method returns cart item total after apply dream price',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value;
        $this->promotion->id = 1;

        $this->saleDiscountService->promotions = collect([$this->promotion]);

        $this->mock(DreamPriceService::class, function ($mock): void {
            $mock->shouldReceive('getDiscountFor')
                ->once()
                ->andReturn(10);
        });

        $saleData = $this->saleDetails;
        $saleData['items'][0]['promotion_id'] = 1;
        $saleData['items'][0]['item_discount_amount'] = 10;

        $this->mock(GiftWithPurchasePromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $this->saleDiscountService->checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->appVersion = 0;

                $mock->cartItems = collect($this->saleDetails);

                $mock->shouldReceive('getItemSubtotal')
                        ->once()
                        ->andReturn(100);

                $mock->shouldReceive('hasDreamPrice')
                        ->once()
                        ->andReturn(true);

                $mock->shouldReceive('hasItemPromotion')
                        ->once()
                        ->andReturn(true);
            }
        );

        $cartItem = $saleData['items'][0];
        $cartItem['dream_price_amount'] = 10;

        $response = $this->saleDiscountService->applyDreamPriceAndItemPromotionOn($cartItem);
        $this->assertEquals(79.8, $response);
    }
);

test(
    'checkItemWisePromotionRestrictions method returns null dream price applicable is true in promotion',
    function (): void {
        $promotion = Promotion::factory()->make([
            'company_id' => $this->companyId,
            'dream_price_applicable' => true,
        ]);

        $response = $this->saleDiscountService->checkItemWisePromotionRestrictions(
            $promotion,
            $this->saleDetails['items'][0]
        );
        $this->assertNull($response);
    }
);

test(
    'checkItemWisePromotionRestrictions method returns null dream price not applicable in promotion and DreamPrice not apply',
    function (): void {
        $promotion = Promotion::factory()->make([
            'company_id' => $this->companyId,
            'dream_price_applicable' => false,
        ]);

        $this->saleDiscountService->checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->shouldReceive('hasDreamPrice')
                    ->once()
                    ->andReturn(false);
            }
        );

        $response = $this->saleDiscountService->checkItemWisePromotionRestrictions(
            $promotion,
            $this->saleDetails['items'][0]
        );
        $this->assertNull($response);
    }
);

test(
    'checkItemWisePromotionRestrictions method set mismatches when dream price not applicable in promotion and DreamPrice apply',
    function (): void {
        $promotion = Promotion::factory()->make([
            'company_id' => $this->companyId,
            'dream_price_applicable' => false,
        ]);

        $this->saleDiscountService->checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->saleMismatches = collect([]);
                $mock->shouldReceive('hasDreamPrice')
                    ->once()
                    ->andReturn(true);
            }
        );

        $this->saleDiscountService->checkItemWisePromotionRestrictions($promotion, $this->saleDetails['items'][0]);
    }
)->throws(HttpException::class, 'Specified promotion cannot be applied with the dream price');

test(
    'checkCartWisePromotionRestrictions method returns null dream price applicable is true in promotion',
    function (): void {
        $promotion = Promotion::factory()->make([
            'company_id' => $this->companyId,
            'dream_price_applicable' => true,
        ]);

        $response = $this->saleDiscountService->checkCartWisePromotionRestrictions($promotion);
        $this->assertNull($response);
    }
);

test(
    'checkCartWisePromotionRestrictions method returns null dream price not applicable in promotion and DreamPrice not apply',
    function (): void {
        $promotion = Promotion::factory()->make([
            'company_id' => $this->companyId,
            'dream_price_applicable' => false,
        ]);

        $this->saleDiscountService->checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->cartItems = collect($this->saleDetails['items']);

                $mock->shouldReceive('hasDreamPrice')
                    ->once()
                    ->andReturn(false);
            }
        );

        $response = $this->saleDiscountService->checkCartWisePromotionRestrictions($promotion);
        $this->assertNull($response);
    }
);

test(
    'checkCartWisePromotionRestrictions method set mismatches when dream price not applicable in promotion and DreamPrice apply',
    function (): void {
        $promotion = Promotion::factory()->make([
            'company_id' => $this->companyId,
            'dream_price_applicable' => false,
        ]);

        $this->saleDiscountService->checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->saleMismatches = collect([]);

                $mock->cartItems = collect($this->saleDetails['items']);

                $mock->shouldReceive('hasDreamPrice')
                    ->once()
                    ->andReturn(true);
            }
        );

        $this->saleDiscountService->checkCartWisePromotionRestrictions($promotion);
    }
)->throws(HttpException::class, 'Specified promotion cannot be applied with the dream price');

test('checkWalkInMember method return null when Allow Walk In Member', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = null;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $this->promotion->allow_walk_in_member = true;

    $response = $this->saleDiscountService->checkWalkInMember($this->promotion);
    $this->assertNull($response);
});

test('checkWalkInMember method return null when not Allow Walk In Member and member id pass', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $this->promotion->allow_walk_in_member = false;

    $response = $this->saleDiscountService->checkWalkInMember($this->promotion);
    $this->assertNull($response);
});

test('checkWalkInMember method return null when not Allow Walk In Member and employee id pass', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['employee_id'] = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $this->promotion->allow_walk_in_member = false;

    $response = $this->saleDiscountService->checkWalkInMember($this->promotion);
    $this->assertNull($response);
});

test(
    'checkWalkInMember method set mismatches when not Allow Walk In Member and employee aor member not Specified',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = null;
        $saleDetails['employee_id'] = null;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->promotion->allow_walk_in_member = false;

        $this->saleDiscountService->checkWalkInMember($this->promotion);
    }
)->throws(HttpException::class, 'Specified promotion is not allowed for the walk in member.');

test(
    'checkItemWisePromoCode method set mismatches when we provide the cart wide promotion in item wise.',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['items'][0]['promotion_id'] = $this->promotion->id;
        $saleDetails['items'][0]['item_discount_amount'] = 10;
        $saleDetails['items'][0]['promo_code'] = 'testing@promo_code';
        $saleDetails['employee_id'] = null;
        $saleDetails['member_id'] = null;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $promotionPromoCode = PromotionPromoCode::factory()->make([
            'id' => 1,
            'promotion_id' => $this->promotion->id,
            'promo_code' => '123456',
        ]);

        $this->promotion->allow_walk_in_member = true;
        $this->promotion->is_automatic = false;
        $this->promotion->promotion_applicable_type_id = 1;

        $this->promotion->promotionPromoCodes = $promotionPromoCode;

        $this->saleDiscountService->checkItemWisePromoCode($this->promotion, current($saleDetails['items']));
    }
)->throws(HttpException::class, 'The provided promotion is cart wide cannot be used here.');

test(
    'checkItemWisePromoCode method set mismatches when the promo code is of single use and uses multiple times.',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['items'][0]['promotion_id'] = $this->promotion->id;
        $saleDetails['items'][0]['item_discount_amount'] = 10;
        $saleDetails['items'][0]['promo_code'] = 'testing@promo_code';
        $saleDetails['employee_id'] = null;
        $saleDetails['member_id'] = null;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $promotionPromoCode = PromotionPromoCode::factory()->make([
            'id' => 1,
            'promotion_id' => $this->promotion->id,
            'promo_code' => 'testing@promo_code',
        ]);

        $saleItemDiscount = SaleItemDiscount::factory()->make([
            'id' => 1,
            'sale_item_id' => 1,
            'discountable_id' => $this->promotion->id,
            'discountable_type' => ModelMapping::PROMOTION->name,
            'amount' => 10,
            'promo_code' => $promotionPromoCode->promo_code,
        ]);

        $this->mock(SaleItemDiscountQueries::class, function ($mock) use (
            $saleItemDiscount,
            $promotionPromoCode
        ): void {
            $mock->shouldReceive('fetchSaleItemDiscountByPromotionAndPromoCode')
            ->once()
            ->with($this->promotion->id, $promotionPromoCode->promo_code)
            ->andReturn($saleItemDiscount);
        });

        $this->promotion->allow_walk_in_member = true;
        $this->promotion->is_automatic = false;
        $this->promotion->promotion_applicable_type_id = PromotionApplicableTypes::ITEM_WISE->value;
        $this->promotion->usage_type = PromotionUsageTypes::SINGLE_USE->value;

        $this->promotion->promotionPromoCodes = collect([$promotionPromoCode]);

        $this->saleDiscountService->checkItemWisePromoCode($this->promotion, current($saleDetails['items']));
    }
)->throws(HttpException::class, 'The provided promo code is already been used.');

test(
    'checkItemWisePromoCode method set mismatches when the promo code is not of valid promotion.',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['items'][0]['promotion_id'] = $this->promotion->id;
        $saleDetails['items'][0]['item_discount_amount'] = 10;
        $saleDetails['items'][0]['promo_code'] = 'testing@promo_code';
        $saleDetails['employee_id'] = null;
        $saleDetails['member_id'] = null;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $promotionPromoCode = PromotionPromoCode::factory()->make([
            'id' => 1,
            'promotion_id' => $this->promotion->id,
            'promo_code' => '123456',
        ]);

        $this->promotion->allow_walk_in_member = true;
        $this->promotion->is_automatic = false;
        $this->promotion->promotion_applicable_type_id = PromotionApplicableTypes::ITEM_WISE->value;
        $this->promotion->usage_type = PromotionUsageTypes::SINGLE_USE->value;

        $this->promotion->promotionPromoCodes = collect([$promotionPromoCode]);

        $this->saleDiscountService->checkItemWisePromoCode($this->promotion, current($saleDetails['items']));
    }
)->throws(HttpException::class, 'The provided promo code is not valid for this promotion.');

test(
    'checkCartWidePromoCode method set mismatches when we provide the cart wide promotion in item wise.',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['cart_promotion_id'] = $this->promotion->id;
        $saleDetails['cart_discount_amount'] = 10;
        $saleDetails['cart_promo_code'] = 'testing@promo_code';
        $saleDetails['employee_id'] = null;
        $saleDetails['member_id'] = null;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $promotionPromoCode = PromotionPromoCode::factory()->make([
            'id' => 1,
            'promotion_id' => $this->promotion->id,
            'promo_code' => '123456',
        ]);

        $this->promotion->allow_walk_in_member = true;
        $this->promotion->is_automatic = false;
        $this->promotion->promotion_applicable_type_id = PromotionApplicableTypes::ITEM_WISE->value;
        $this->promotion->usage_type = PromotionUsageTypes::SINGLE_USE->value;

        $this->promotion->promotionPromoCodes = $promotionPromoCode;

        $this->saleDiscountService->checkCartWidePromoCode($this->promotion);
    }
)->throws(HttpException::class, 'The provided promotion is item wise cannot be used here.');

test(
    'checkCartWidePromoCode method set mismatches when the promo code is of single use and uses multiple times.',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['cart_promotion_id'] = $this->promotion->id;
        $saleDetails['cart_discount_amount'] = 10;
        $saleDetails['cart_promo_code'] = 'testing@promo_code';
        $saleDetails['employee_id'] = null;
        $saleDetails['member_id'] = null;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $promotionPromoCode = PromotionPromoCode::factory()->make([
            'id' => 1,
            'promotion_id' => $this->promotion->id,
            'promo_code' => 'testing@promo_code',
        ]);

        $saleDiscount = SaleDiscount::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'discountable_id' => $this->promotion->id,
            'discountable_type' => ModelMapping::PROMOTION->name,
            'amount' => 10,
            'promo_code' => $promotionPromoCode->promo_code,
        ]);

        $this->mock(SaleDiscountQueries::class, function ($mock) use ($saleDiscount, $promotionPromoCode): void {
            $mock->shouldReceive('fetchSaleDiscountByPromotionAndPromoCode')
            ->once()
            ->with($this->promotion->id, $promotionPromoCode->promo_code)
            ->andReturn($saleDiscount);
        });

        $this->promotion->allow_walk_in_member = true;
        $this->promotion->is_automatic = false;
        $this->promotion->promotion_applicable_type_id = PromotionApplicableTypes::CART_WIDE->value;
        $this->promotion->usage_type = PromotionUsageTypes::SINGLE_USE->value;

        $this->promotion->promotionPromoCodes = collect([$promotionPromoCode]);

        $this->saleDiscountService->checkCartWidePromoCode($this->promotion);
    }
)->throws(HttpException::class, 'The provided promo code is already been used.');

test(
    'checkCartWidePromoCode method set mismatches when the promo code is not of valid promotion.',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['cart_promotion_id'] = $this->promotion->id;
        $saleDetails['cart_discount_amount'] = 10;
        $saleDetails['cart_promo_code'] = 'testing@promo_code';
        $saleDetails['employee_id'] = null;
        $saleDetails['member_id'] = null;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $promotionPromoCode = PromotionPromoCode::factory()->make([
            'id' => 1,
            'promotion_id' => $this->promotion->id,
            'promo_code' => '123456',
        ]);

        $this->promotion->allow_walk_in_member = true;
        $this->promotion->is_automatic = false;
        $this->promotion->promotion_applicable_type_id = PromotionApplicableTypes::CART_WIDE->value;
        $this->promotion->usage_type = PromotionUsageTypes::SINGLE_USE->value;

        $this->promotion->promotionPromoCodes = collect([$promotionPromoCode]);

        $this->saleDiscountService->checkCartWidePromoCode($this->promotion, current($saleDetails['items']));
    }
)->throws(HttpException::class, 'The provided promo code is not valid for this promotion.');

test(
    'checkItemWisePromotionDetails method throws en exception when loyalty_point_item_discount not set',
    function (): void {
        $mock = $this->createPartialMock(SaleDiscountService::class, []);

        $this->saleDetails['items'][0]['loyalty_points'] = 100;
        $mock->checkSaleDetailsService = $this->mock(CheckSaleDetailsService::class, function ($mock): void {
            $mock->saleData = new SaleData(...$this->saleDetails);

            $mock->saleMismatches = collect([]);

            $mock->shouldReceive('hasDreamPrice')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasComplimentaryItem')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasHappyHourDiscount')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasProductLoyaltyPoints')
                ->once()
                ->andReturn(true);
        });

        $mock->checkSaleDetailsService->cartItems = collect([]);

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
)->throws(HttpException::class, 'Loyalty Points item discount amount not specified.');

test(
    'checkItemWisePromotionDetails method throws en exception when loyalty_point_item_discount not match',
    function (): void {
        $mock = $this->createPartialMock(SaleDiscountService::class, []);

        $this->saleDetails['items'][0]['loyalty_points'] = 100;
        $this->saleDetails['items'][0]['loyalty_point_item_discount'] = 10;
        $mock->checkSaleDetailsService = $this->mock(CheckSaleDetailsService::class, function ($mock): void {
            $mock->saleData = new SaleData(...$this->saleDetails);

            $mock->saleMismatches = collect([]);

            $mock->shouldReceive('hasDreamPrice')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasComplimentaryItem')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasHappyHourDiscount')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('hasProductLoyaltyPoints')
                ->once()
                ->andReturn(true);

            $mock->shouldReceive('getItemSubtotal')
                ->once()
                ->andReturn(100);
        });

        $mock->checkSaleDetailsService->cartItems = collect([]);

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
)->throws(
    HttpException::class,
    'Provided loyalty point item discount does not match with calculated amount.\nExpected: 100\nReceived: 10'
);

test(
    'It calls the getItemSubtotal method of the checkSaleDetailsService class and returns proper response',
    function (): void {
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails);
        $this->saleDiscountService->checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $mock->saleData = new SaleData(...$this->saleDetails);
                $mock->cartItems = collect($this->saleDetails);

                $mock->shouldReceive('hasDreamPrice')
                    ->once()
                    ->andReturn(false);

                $mock->shouldReceive('hasComplimentaryItem')
                    ->once()
                    ->andReturn(false);

                $mock->shouldReceive('hasHappyHourDiscount')
                    ->once()
                    ->andReturn(false);

                $mock->shouldReceive('hasProductLoyaltyPoints')
                    ->once()
                    ->andReturn(true);
            }
        );

        $saleData = $this->saleDetails;
        $saleData['items'][0]['loyalty_points'] = 100;
        $saleData['items'][0]['loyalty_point_item_discount'] = 100;

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(100, $response['total_discount']);
    }
);

test(
    'It calls the getCartDiscountAmount method returns proper response',
    function (): void {
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $saleData = $this->saleDetails;
        $saleData['cart_loyalty_point_amount'] = 20.20;
        $saleData['cart_loyalty_points'] = 10;
        $this->saleDiscountService->checkSaleDetailsService->saleData = new SaleData(...$saleData);

        $response = $this->saleDiscountService->getCartDiscountAmountFor(20.20);
        $this->assertEquals(20.20, $response['cart_wide_loyalty_point_discount']);
        $this->assertEquals(20.20, $response['total_discount']);
    }
);

test(
    'it calls checkForApplicability method of the PercentageDiscountForNextItemPromotionService class',
    function (): void {
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 10;

        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            [
                'checkMember',
                'checkWalkInMember',
                'checkEmployee',
                'checkPromotionIsActive',
                'checkPromotionTimeFrame',
                'checkPromotionLocations',
                'checkPromotionProductType',
            ]
        );

        $this->mock(PercentageDiscountForNextItemPromotionService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
            ->once();
        });

        $mock->expects($this->once())
            ->method('checkMember');

        $mock->expects($this->once())
            ->method('checkWalkInMember');

        $mock->expects($this->once())
            ->method('checkEmployee');

        $mock->expects($this->once())
            ->method('checkPromotionIsActive');

        $mock->expects($this->once())
            ->method('checkPromotionTimeFrame');

        $mock->expects($this->once())
            ->method('checkPromotionLocations');

        $mock->expects($this->once())
            ->method('checkPromotionProductType');

        $this->promotion->id = 1;
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value;
        $mock->promotions = collect([$this->promotion]);

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
);

test(
    'It calls the getItemDiscountAmount method of the PercentageDiscountForNextItemPromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::PERCENTAGE_DISCOUNT_FOR_NEXT_ITEM->value;
        $this->promotion->id = 1;

        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->saleDiscountService->promotions = collect([$this->promotion]);

        $saleData = $this->saleDetails;
        $saleData['items'][0]['promotion_id'] = 1;
        $saleData['items'][0]['item_discount_amount'] = 10;

        $this->mock(PercentageDiscountForNextItemPromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'it calls checkForApplicability method of the FlatDiscountForNextItemPromotionService class',
    function (): void {
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 10;

        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            [
                'checkMember',
                'checkWalkInMember',
                'checkEmployee',
                'checkPromotionIsActive',
                'checkPromotionTimeFrame',
                'checkPromotionLocations',
                'checkPromotionProductType',
            ]
        );

        $this->mock(FlatDiscountForNextItemPromotionService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
            ->once();
        });

        $mock->expects($this->once())
            ->method('checkMember');

        $mock->expects($this->once())
            ->method('checkWalkInMember');

        $mock->expects($this->once())
            ->method('checkEmployee');

        $mock->expects($this->once())
            ->method('checkPromotionIsActive');

        $mock->expects($this->once())
            ->method('checkPromotionTimeFrame');

        $mock->expects($this->once())
            ->method('checkPromotionLocations');

        $mock->expects($this->once())
            ->method('checkPromotionProductType');

        $this->promotion->id = 1;
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value;
        $mock->promotions = collect([$this->promotion]);

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
);

test(
    'It calls the getItemDiscountAmount method of the FlatDiscountForNextItemPromotionService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::FLAT_DISCOUNT_FOR_NEXT_ITEM->value;
        $this->promotion->id = 1;

        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->saleDiscountService->promotions = collect([$this->promotion]);

        $saleData = $this->saleDetails;
        $saleData['items'][0]['promotion_id'] = 1;
        $saleData['items'][0]['item_discount_amount'] = 10;

        $this->mock(FlatDiscountForNextItemPromotionService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'it calls checkForApplicability method of the HappyHourDiscountSaleService class',
    function (): void {
        $mock = $this->createPartialMock(SaleDiscountService::class, []);

        $mock->checkSaleDetailsService = $this->mock(CheckSaleDetailsService::class, function ($mock): void {
            $mock->saleData = new SaleData(...$this->saleDetails);

            $mock->shouldReceive('hasHappyHourDiscount')
                ->once()
                ->andReturn(true);

            $this->mock(HappyHourDiscountSaleService::class, function ($mock): void {
                $mock->shouldReceive('checkForApplicability')
                    ->once();
            });
        });

        $mock->checkSaleDetailsService->cartItems = collect([]);

        $mock->checkItemWisePromotionDetails(new Product(), $this->saleDetails['items'][0]);
    }
);

test(
    'It calls the getItemDiscountAmountFor method of the HappyHourDiscountSaleService class and returns proper response',
    function (): void {
        $this->promotion->item_wise_promotion_type_id = ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value;
        $this->promotion->id = 1;

        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->saleDiscountService->promotions = collect([$this->promotion]);

        $saleData = $this->saleDetails;
        $saleData['items'][0]['happy_hours_offline_id'] = '255';
        $saleData['items'][0]['happy_hours_discount_amount'] = 10.20;

        $this->mock(HappyHourDiscountSaleService::class, function ($mock): void {
            $mock->shouldReceive('getItemDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getItemDiscountAmountFor($saleData['items'][0]);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);

test(
    'It calls the getByOfflineIdsWithRelations method of the HappyHourDiscountQueries class and returns proper response',
    function (): void {
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails);
        $this->checkSaleDetailsService->companyId = $this->companyId;

        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $happyHourDiscount = new HappyHourDiscount();

        $this->mock(HappyHourDiscountQueries::class, function ($mock) use ($happyHourDiscount): void {
            $mock->shouldReceive('getByOfflineIdsWithRelations')
                ->once()
                ->andReturn(collect([$happyHourDiscount]));
        });

        $response = $this->saleDiscountService->getHappyHourDiscounts();
        $this->assertTrue($response->first() === $happyHourDiscount);
    }
);

test('getHappyHourDiscountIds method returns the happy hours discount ids', function (): void {
    $cartItems = $this->saleDetails['items'];
    $cartItems[0]['happy_hours_offline_id'] = '585';
    $cartItems[1]['happy_hours_offline_id'] = '123';

    $this->checkSaleDetailsService->cartItems = collect($cartItems);
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $response = $this->saleDiscountService->getHappyHourDiscountIds();

    $this->assertTrue($response === ['585', '123']);
});

test(
    'checkPromotionProductType method return null when product type is regular product.',
    function (): void {
        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'ABC',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'upc' => 'abd123',
            'retail_price' => 10.00,
            'has_batch' => false,
            'status' => false,
            'type_id' => ProductTypes::REGULAR_PRODUCT->value,
        ]);

        $cartItem['id'] = 1;

        $this->checkSaleDetailsService->products = collect([$product]);

        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $response = $this->saleDiscountService->checkPromotionProductType($cartItem);

        $this->assertNull($response);
    }
);

test(
    'checkPromotionProductType method throws an exception when product type is bundle product.',
    function (): void {
        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'ABC',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'upc' => 'abd123',
            'retail_price' => 10.00,
            'has_batch' => false,
            'status' => false,
            'type_id' => ProductTypes::REGULAR_PRODUCT->value,
        ]);

        $cartItem['id'] = 1;
        $cartItem['box_product_id'] = 1;

        $this->checkSaleDetailsService->products = collect([$product]);

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $response = $this->saleDiscountService->checkPromotionProductType($cartItem);

        $this->assertNull($response);
    }
)->throws(HttpException::class, 'Specified promotion is apply for bundle products.');

test('getCalculateItemCartDiscountAmount method return last item discount', function (): void {
    $this->saleDetails['items'][0]['item_discount_amount'] = 0;

    $this->saleDetails['items'][1]['id'] = 2;
    $this->saleDetails['items'][1]['price'] = 100;
    $this->saleDetails['items'][1]['quantity'] = 1;

    $this->saleData = new SaleData(...$this->saleDetails);

    $mock = $this->createPartialMock(SaleDiscountService::class, ['getItemSubTotalAfterItemDiscount']);

    $mock->expects($this->once())
        ->method('getItemSubTotalAfterItemDiscount')
        ->will($this->returnValue(100));

    $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);
    $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

    $this->saleDetails['items'][1]['item_discount_amount'] = 0;
    $response = $mock->getCalculateItemCartDiscountAmount($this->saleDetails['items'][1], 80, 200);

    $this->assertEquals(40, $response);
});

test(
    'getCalculateItemCartDiscountAmount method return last item discount when pass cart_discount_item_sequence',
    function (): void {
        $this->saleDetails['items'][0]['item_discount_amount'] = 0;
        $this->saleDetails['items'][0]['discount_item_sequence'] = 1;

        $this->saleDetails['items'][1]['id'] = 2;
        $this->saleDetails['items'][1]['price'] = 100;
        $this->saleDetails['items'][1]['quantity'] = 1;
        $this->saleDetails['items'][1]['discount_item_sequence'] = 2;

        $this->saleData = new SaleData(...$this->saleDetails);

        $mock = $this->createPartialMock(SaleDiscountService::class, ['getItemSubTotalAfterItemDiscount']);

        $mock->expects($this->once())
            ->method('getItemSubTotalAfterItemDiscount')
            ->will($this->returnValue(100));

        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);
        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->saleDetails['items'][1]['item_discount_amount'] = 0;
        $response = $mock->getCalculateItemCartDiscountAmount($this->saleDetails['items'][1], 80, 200);

        $this->assertEquals(40, $response);
    }
);

test(
    'getItemSubTotalAfterItemDiscount method return proper response',
    function (float $discount, float $cartTotal): void {
        $this->saleDetails['items'][1]['id'] = 2;
        $this->saleDetails['items'][1]['price'] = 100;
        $this->saleDetails['items'][1]['quantity'] = 1;
        $this->saleDetails['items'][1]['discount_item_sequence'] = 2;
        $mock = $this->createPartialMock(SaleDiscountService::class, ['getItemDiscountAmountFor']);

        $mock->expects($this->once())
            ->method('getItemDiscountAmountFor')
            ->will($this->returnValue([
                'total_discount' => $discount,
            ]));

        $mock->checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock) use ($cartTotal): void {
                $mock->shouldReceive('getItemSubtotal')
                    ->once()
                    ->andReturn($cartTotal);
            }
        );

        $response = $mock->getItemSubTotalAfterItemDiscount($this->saleDetails['items'][1]);

        $this->assertEquals(CommonFunctions::numberFormat($cartTotal - $discount), $response);
    }
)->with([[200, 300], [150, 500], [20.35, 102.58]]);

test(
    'isCartDiscountItemSequenceInAllItems method returns true when item set cart_discount_item_sequence',
    function (): void {
        $this->saleDetails['items'][0]['cart_discount_item_sequence'] = 1;
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $response = $this->saleDiscountService->isCartDiscountItemSequenceInAllItems();
        $this->assertTrue($response);
    }
);

test(
    'isCartDiscountItemSequenceInAllItems method returns false when item set cart_discount_item_sequence',
    function (): void {
        $this->checkSaleDetailsService->cartItems = collect($this->saleDetails['items']);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $response = $this->saleDiscountService->isCartDiscountItemSequenceInAllItems();
        $this->assertFalse($response);
    }
);

test('isCartDiscountItemSequence method returns boolean as expected', function (): void {
    $cartItem = [];
    $response = $this->saleDiscountService->isCartDiscountItemSequence($cartItem);
    $this->assertFalse($response);

    $cartItem['cart_discount_item_sequence'] = null;
    $response = $this->saleDiscountService->isCartDiscountItemSequence($cartItem);
    $this->assertFalse($response);

    $cartItem['cart_discount_item_sequence'] = 0;
    $response = $this->saleDiscountService->isCartDiscountItemSequence($cartItem);
    $this->assertFalse($response);

    $cartItem['cart_discount_item_sequence'] = 1;
    $response = $this->saleDiscountService->isCartDiscountItemSequence($cartItem);
    $this->assertTrue($response);
});

test('isCartDiscountReturn method returns boolean as expected', function (): void {
    $cartItem['id'] = 1;
    $item['cart_discount_item_sequence'] = null;
    $item['id'] = 2;
    $response = $this->saleDiscountService->isCartDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['cart_discount_item_sequence'] = null;
    $cartItem['id'] = 1;
    $item['id'] = 2;
    $response = $this->saleDiscountService->isCartDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['id'] = 1;
    $item['id'] = 2;
    $response = $this->saleDiscountService->isCartDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['cart_discount_item_sequence'] = null;
    $cartItem['id'] = 1;
    $item['cart_discount_item_sequence'] = null;
    $item['id'] = 2;
    $response = $this->saleDiscountService->isCartDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['cart_discount_item_sequence'] = null;
    $cartItem['id'] = 1;
    $item['cart_discount_item_sequence'] = null;
    $item['id'] = 1;
    $response = $this->saleDiscountService->isCartDiscountReturn($cartItem, $item);
    $this->assertTrue($response);

    $cartItem['cart_discount_item_sequence'] = 1;
    $item['cart_discount_item_sequence'] = 2;
    $response = $this->saleDiscountService->isCartDiscountReturn($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['cart_discount_item_sequence'] = 1;
    $item['cart_discount_item_sequence'] = 1;
    $response = $this->saleDiscountService->isCartDiscountReturn($cartItem, $item);
    $this->assertTrue($response);
});

test('matchItemByCartDiscountItemSequence method returns boolean as expected', function (): void {
    $cartItem['cart_discount_item_sequence'] = 1;
    $item['cart_discount_item_sequence'] = 2;
    $response = $this->saleDiscountService->matchItemByCartDiscountItemSequence($cartItem, $item);
    $this->assertFalse($response);

    $cartItem['cart_discount_item_sequence'] = 1;
    $item['cart_discount_item_sequence'] = 1;
    $response = $this->saleDiscountService->matchItemByCartDiscountItemSequence($cartItem, $item);
    $this->assertTrue($response);
});

test(
    'CheckForApplicability method calls same class methods as expected when promotion type is payment type',
    function (): void {
        $this->promotion->cart_wide_promotion_type_id = CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value;

        $mock = $this->createPartialMock(
            SaleDiscountService::class,
            [
                'checkCartWidePromoCode',
                'checkMember',
                'checkWalkInMember',
                'checkEmployee',
                'checkPromotionIsActive',
                'checkPromotionTimeFrame',
                'checkPromotionLocations',
                'checkCartWisePromotionRestrictions',
            ]
        );

        $mock->expects($this->once())
            ->method('checkCartWidePromoCode');

        $mock->expects($this->once())
            ->method('checkMember');

        $mock->expects($this->once())
            ->method('checkWalkInMember');

        $mock->expects($this->once())
            ->method('checkEmployee');

        $mock->expects($this->once())
            ->method('checkPromotionIsActive');

        $mock->expects($this->once())
            ->method('checkPromotionTimeFrame');

        $mock->expects($this->once())
            ->method('checkPromotionLocations');

        $mock->expects($this->once())
            ->method('checkCartWisePromotionRestrictions');

        $mock->promotions = collect([$this->promotion]);

        $mock->checkSaleDetailsService = $this->mock(
            CheckSaleDetailsService::class,
            function ($mock): void {
                $this->saleDetails['cart_promotion_id'] = 1;
                $mock->saleData = new SaleData(...$this->saleDetails);
                $mock->appVersion = 0;

                $mock->shouldReceive('hasCartPromotion')
                ->once()
                    ->andReturn(true);

                $mock->shouldReceive('getCartSubtotalByDiscountApplicableType')
                ->once()
                    ->andReturn(100);
            }
        );

        $this->mock(CartWideAsPerPaymentTypePromotionService::class, function ($mock): void {
            $mock->shouldReceive('checkForApplicability')
            ->once();
        });

        $response = $mock->checkCartWidePromotionDetails(1);
        $this->assertNull($response);
    }
);

test(
    'It calls the getCartDiscountAmount method of the CartWideAsPerPaymentTypePromotionService class and returns proper response',
    function (): void {
        $this->promotion->promotion_applicable_type_id = PromotionApplicableTypes::CART_WIDE->value;
        $this->promotion->cart_wide_promotion_type_id = CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value;
        $this->promotion->id = 1;
        $this->saleDiscountService->promotions = collect([$this->promotion]);
        $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $saleData = $this->saleDetails;
        $saleData['cart_promotion_id'] = 1;
        $this->saleDiscountService->checkSaleDetailsService->saleData = new SaleData(...$saleData);
        $this->saleDiscountService->checkSaleDetailsService->cart_promotion_id = 1;

        $this->mock(CartWideAsPerPaymentTypePromotionService::class, function ($mock): void {
            $mock->shouldReceive('getCartDiscountAmount')
                ->once()
                ->andReturn(10.20);
        });

        $response = $this->saleDiscountService->getCartDiscountAmountFor(20.20);
        $this->assertEquals(10.20, $response['cart_wide_discount']);
        $this->assertEquals(10.20, $response['total_discount']);
    }
);
test('checkPromotionMembership method return null when membership not required', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = null;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $this->promotion->is_membership_required = false;

    $response = $this->saleDiscountService->checkPromotionMembership($this->promotion);
    $this->assertNull($response);
});

test('checkPromotionMembership method return null when promotion memberships not selected', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = null;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $this->promotion->is_membership_required = true;
    $this->promotion->memberships = collect([]);

    $response = $this->saleDiscountService->checkPromotionMembership($this->promotion);
    $this->assertNull($response);
});

test('checkPromotionMembership method throws an exception when member not Specified', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = null;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->promotion->is_membership_required = true;
    $membership = Membership::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'ABC',
    ]);

    $this->promotion->memberships = collect([$membership]);

    $mock = $this->createPartialMock(SaleDiscountService::class, ['isMemberAttached']);
    $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

    $mock->expects($this->once())
        ->method('isMemberAttached')
        ->will($this->returnValue(false));

    $mock->checkPromotionMembership($this->promotion);
})->throws(HttpException::class, 'Member and Membership is required for the specified promotion.');

test('checkPromotionMembership method return null when member and membership is specified properly', function (): void {
    $saleDetails = $this->saleDetails;
    $saleDetails['member_id'] = null;
    $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
    $this->checkSaleDetailsService->saleMismatches = collect([]);
    $this->checkSaleDetailsService->member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => 1,
        'first_name' => 'ABC',
    ]);

    $this->promotion->is_membership_required = true;
    $membership = Membership::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'ABC',
    ]);

    $this->promotion->memberships = collect([$membership]);

    $mock = $this->createPartialMock(SaleDiscountService::class, ['isMemberAttached']);
    $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

    $mock->expects($this->once())
        ->method('isMemberAttached')
        ->will($this->returnValue(true));

    $response = $mock->checkPromotionMembership($this->promotion);
    $this->assertNull($response);
});

test(
    'checkPromotionMembership method method throws an exception when member and membership is not specified properly',
    function (): void {
        $saleDetails = $this->saleDetails;
        $saleDetails['member_id'] = null;
        $this->checkSaleDetailsService->saleData = new SaleData(...$saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 2,
            'first_name' => 'ABC',
        ]);

        $this->promotion->is_membership_required = true;
        $membership = Membership::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'ABC',
        ]);

        $this->promotion->memberships = collect([$membership]);

        $mock = $this->createPartialMock(SaleDiscountService::class, ['isMemberAttached']);
        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $mock->expects($this->once())
            ->method('isMemberAttached')
            ->will($this->returnValue(true));

        $response = $mock->checkPromotionMembership($this->promotion);
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'The Selected Member membership is not valid for the specified promotion.');
