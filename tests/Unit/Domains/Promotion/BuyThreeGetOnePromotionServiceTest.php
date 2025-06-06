<?php

declare(strict_types=1);

use App\Domains\Promotion\Services\BuyThreeGetOnePromotionService;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionTier;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->buyThreeGetOnePromotionService = new BuyThreeGetOnePromotionService();

    $this->checkSaleDetailsService = new CheckSaleDetailsService();

    $this->product = Product::factory()->make([
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

    $this->promotion = Promotion::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'Cart Wide Automatic Promotion',
        'promotion_applicable_type_id' => 1,
        'discount_type_id' => 1,
        'cart_wide_promotion_type_id' => 1,
        'timeframe_type_id' => 1,
        'percentage' => 0,
        'flat_amount' => 10.20,
        'allow_employee' => false,
        'allow_registered_member' => false,
        'status' => true,
    ]);

    $this->promotionTier = PromotionTier::factory()->make([
        'promotion_id' => $this->promotion->id,
    ]);

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
        'sale_round_off_amount' => 0.01,
    ];

    $this->saleData = new SaleData(...$this->saleDetails);

    $this->cartItems = collect($this->saleData->items);
});

test(
    'checkForApplicability method sets the saleMismatches when the group id not specified',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->buyThreeGetOnePromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );
    }
)->throws(HttpException::class, 'group id is required for Buy 3 get 1 promotions but not passed.');

test(
    'checkForApplicability method sets the saleMismatches when the group id is null',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = null;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->getProducts = collect([$this->product]);
        $this->promotion->buyProducts = collect([$this->product]);

        $mock = $this->createPartialMock(BuyThreeGetOnePromotionService::class, ['getTotalFreeQuantities']);

        $mock->expects($this->never())
            ->method('getTotalFreeQuantities')
            ->will($this->returnValue(0.1));

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );
    }
)->throws(HttpException::class, 'group id is required for Buy 3 get 1 promotions but not passed.');

test(
    'checkForApplicability method sets the saleMismatches when Some of the free products are not matched with our records for the buy 3 get 1 free promotion',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->getProducts = collect([]);
        $this->promotion->buyProducts = collect([$this->product]);

        $mock = $this->createPartialMock(BuyThreeGetOnePromotionService::class, ['getTotalFreeQuantities']);

        $mock->expects($this->never())
            ->method('getTotalFreeQuantities')
            ->will($this->returnValue(0.1));

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );
    }
)->throws(
    HttpException::class,
    'Some of the specified free products do not qualify for the buy 3 get 1 promotion as per our records.'
);

test(
    'checkForApplicability method sets the saleMismatches when Specified promotion free quantities is more than free quantities',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->getProducts = collect([$this->product]);
        $this->promotion->buyProducts = collect([$this->product]);

        $mock = $this->createPartialMock(BuyThreeGetOnePromotionService::class, ['getTotalFreeQuantities']);

        $mock->expects($this->once())
            ->method('getTotalFreeQuantities')
            ->will($this->returnValue(0.1));

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );
    }
)->throws(
    HttpException::class,
    'Only 0.1 units can be given for free for this buy 3 get 1 free promotion. But requested free quantities are 10.'
);

test(
    'checkForApplicability method sets the saleMismatches when Some of the buy products are not matched with our records for the buy 3 get 1 free promotion',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $this->saleDetails['items'][1]['group_id'] = 1;
        $this->saleDetails['items'][1]['promotion_id'] = 1;
        $this->saleDetails['items'][1]['item_discount_amount'] = 0;

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->getProducts = collect([$this->product]);
        $this->promotion->buyProducts = collect([]);

        $mock = $this->createPartialMock(BuyThreeGetOnePromotionService::class, ['getTotalFreeQuantities']);

        $mock->expects($this->never())
            ->method('getTotalFreeQuantities')
            ->will($this->returnValue(0.1));

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );
    }
)->throws(
    HttpException::class,
    'Some of the specified buy products are not matched with our records for the buy 3 get 1 free promotion.'
);

test(
    'checkForApplicability method sets the saleMismatches when item discount amount are not matched with our records for the buy 3 get 1 free promotion',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['group_id'] = 1;
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;

        $this->saleDetails['items'][1]['group_id'] = 1;
        $this->saleDetails['items'][1]['id'] = 1;
        $this->saleDetails['items'][1]['promotion_id'] = 1;
        $this->saleDetails['items'][1]['item_discount_amount'] = 0;
        $this->saleDetails['items'][1]['quantity'] = 1;

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->promotion->getProducts = collect([$this->product]);
        $this->promotion->buyProducts = collect([$this->product]);
        $this->promotion->promotionTiers = collect([
            '0' => $this->promotionTier,
        ]);

        $itemTotal = (float) ($this->saleDetails['items'][0]['quantity'] * $this->saleDetails['items'][0]['price']);

        $this->buyThreeGetOnePromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            $itemTotal,
            new SaleDiscountService(),
        );
    }
)->throws(
    HttpException::class,
    'Only 0 units can be given for free for this buy 3 get 1 free promotion. But requested free quantities are 10.'
);

test('getTotalFreeQuantities method returns response as expected', function ($totalBuyQuantity, $getValue): void {
    $this->promotion->promotionTiers = collect([
        new PromotionTier([
            'buy_value' => 5,
            'get_value' => 2,
        ]),
        new PromotionTier([
            'buy_value' => 10,
            'get_value' => 4,
        ]),
    ]);

    $response = $this->buyThreeGetOnePromotionService->getTotalFreeQuantities($totalBuyQuantity, $this->promotion);
    $this->assertEquals($getValue, $response);
})->with([[10, 4], [15, 6], [22, 8], [8, 2], [64, 24], [11, 4], [14, 4]]);

test('getPromotionTierValue method returns response as expected', function (): void {
    $this->promotion->promotionTiers = collect([
        new PromotionTier([
            'buy_value' => 10,
            'get_value' => 5,
        ]),
        new PromotionTier([
            'buy_value' => 20,
            'get_value' => 10,
        ]),
        new PromotionTier([
            'buy_value' => 30,
            'get_value' => 15,
        ]),
        new PromotionTier([
            'buy_value' => 40,
            'get_value' => 20,
        ]),
    ]);

    $response = $this->buyThreeGetOnePromotionService->getPromotionTierValue(20, $this->promotion);

    expect($response->toArray())
        ->toHaveKey('buy_value', 20)
        ->toHaveKey('get_value', 10);
});

test('getItemDiscountAmount method return 0 when not set item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;

    $response = $this->buyThreeGetOnePromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return 0 when set null item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = null;

    $response = $this->buyThreeGetOnePromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return same pass item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = 10.10;

    $response = $this->buyThreeGetOnePromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(10.10, $response);
});
