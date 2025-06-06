<?php

declare(strict_types=1);

use App\Domains\Promotion\Services\GiftWithPurchasePromotionService;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionTier;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->giftWithPurchasePromotionService = new GiftWithPurchasePromotionService();
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
        'name' => 'Gift With Purchase',
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
    'checkForApplicability method sets the saleMismatches when some of the specified products for the gift with purchase promotion do not match with our records',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['is_gift_with_purchase'] = true;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);

        $this->promotion->regularProducts = collect([]);

        $this->promotion->promotionTiers = collect([
            new PromotionTier([
                'buy_value' => 10,
                'get_value' => 1,
            ]),
            new PromotionTier([
                'buy_value' => 20,
                'get_value' => 2,
            ]),
        ]);

        $this->giftWithPurchasePromotionService->checkForApplicability(
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
    'Some of the specified products do not qualify for the gift with purchase promotion as per our records.'
);

test(
    'checkForApplicability method sets the saleMismatches when requested free products is more then applicable',
    function (): void {
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['is_gift_with_purchase'] = true;
        $this->saleDetails['items'][0]['item_discount_amount'] = 1;
        $this->saleData = new SaleData(...$this->saleDetails);

        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->promotion->regularProducts = collect([$this->product]);
        $this->promotion->promotionTiers = collect([
            new PromotionTier([
                'buy_value' => 10,
                'get_value' => 1,
            ]),
            new PromotionTier([
                'buy_value' => 20,
                'get_value' => 2,
            ]),
        ]);

        $this->giftWithPurchasePromotionService->checkForApplicability(
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
    'Only 2 units can be given for this gift with purchase promotion. But requested quantities are 10.'
);

test('getPromotionTierValue method returns response as expected', function ($cartSubTotal, $getValue): void {
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

    $response = $this->giftWithPurchasePromotionService->getPromotionTierValue($cartSubTotal, $this->promotion);
    $this->assertEquals($getValue, $response);
})->with([[40, 20], [25, 10], [35.68, 15], [11.23, 5], [20, 10], [5.20, 0], [500.20, 20]]);

test(
    'checkForApplicability method return null when cart item is not gift item',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['promotion_id'] = 1;

        $response = $this->giftWithPurchasePromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );

        $this->assertNull($response);
    }
);

test(
    'checkForApplicability method return null when is_gift_with_purchase flag is false',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['is_gift_with_purchase'] = false;

        $response = $this->giftWithPurchasePromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );

        $this->assertNull($response);
    }
);

test(
    'checkForApplicability method sets the saleMismatches when the discount amount does not match with our calculations',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['quantity'] = 2;
        $this->saleDetails['items'][0]['is_gift_with_purchase'] = true;
        $this->saleDetails['items'][0]['item_discount_amount'] = 10;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);

        $this->promotion->regularProducts = collect([$this->product]);

        $this->promotion->promotionTiers = collect([
            new PromotionTier([
                'buy_value' => 10,
                'get_value' => 1,
            ]),
            new PromotionTier([
                'buy_value' => 20,
                'get_value' => 2,
            ]),
        ]);

        $this->giftWithPurchasePromotionService->checkForApplicability(
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
    'Specified discount amount does not match with our calculations. The actual discount amount is 100. and requested discount amount is 10.'
);

test(
    'checkForApplicability method works as expected',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['promotion_id'] = 1;
        $this->saleDetails['items'][0]['is_gift_with_purchase'] = true;
        $this->saleDetails['items'][0]['item_discount_amount'] = 100;
        $this->saleDetails['items'][0]['quantity'] = 2;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->saleData->items);

        $this->promotion->regularProducts = collect([$this->product]);

        $this->promotion->promotionTiers = collect([
            new PromotionTier([
                'buy_value' => 10,
                'get_value' => 1,
            ]),
            new PromotionTier([
                'buy_value' => 20,
                'get_value' => 2,
            ]),
        ]);

        $this->giftWithPurchasePromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            new Product(),
            100,
            new SaleDiscountService(),
        );

        $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
    }
);

test('getItemDiscountAmount method return 0 when not set item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;

    $response = $this->giftWithPurchasePromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return 0 when set null item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = null;

    $response = $this->giftWithPurchasePromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return same pass item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = 10.10;

    $response = $this->giftWithPurchasePromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(10.10, $response);
});
