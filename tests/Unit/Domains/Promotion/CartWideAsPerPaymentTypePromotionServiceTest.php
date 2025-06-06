<?php

declare(strict_types=1);

use App\CommonFunctions;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Promotion\Enums\CartWidePromotionTypes;
use App\Domains\Promotion\Services\CartWideAsPerPaymentTypePromotionService;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Models\PaymentType;
use App\Models\Promotion;
use App\Models\PromotionTier;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->cartWideAsPerPaymentTypePromotionService = new CartWideAsPerPaymentTypePromotionService();
    $this->checkSaleDetailsService = new CheckSaleDetailsService();

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
        'company_id' => 1,
        'name' => 'Cart Wide As Per Payment Type Promotion',
        'promotion_applicable_type_id' => 1,
        'discount_type_id' => 1,
        'cart_wide_promotion_type_id' => CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value,
        'timeframe_type_id' => 1,
        'percentage' => 0,
        'flat_amount' => 0,
        'allow_employee' => false,
        'allow_registered_member' => false,
        'status' => true,
    ]);
});

test(
    'getCalculateCartDiscountAmount method calls getCartPercentageDiscountAmount method when promotion discount type is percentage',
    function (): void {
        $this->promotion->discount_type_id = DiscountTypes::PERCENTAGE->value;

        $mock = $this->createPartialMock(
            CartWideAsPerPaymentTypePromotionService::class,
            ['getCartPercentageDiscountAmount']
        );

        $mock->expects($this->once())
            ->method('getCartPercentageDiscountAmount')
            ->will($this->returnValue(10.20));

        $response = $mock->getCalculateCartDiscountAmount(20.10, $this->promotion);
        $this->assertEquals(10.20, $response);
    }
);

test(
    'getCalculateCartDiscountAmount method calls getCartFlatDiscountAmount method when promotion discount type is flat',
    function (): void {
        $this->promotion->discount_type_id = DiscountTypes::FLAT->value;

        $mock = $this->createPartialMock(
            CartWideAsPerPaymentTypePromotionService::class,
            ['getCartFlatDiscountAmount']
        );

        $mock->expects($this->once())
            ->method('getCartFlatDiscountAmount')
            ->will($this->returnValue(20.20));

        $response = $mock->getCalculateCartDiscountAmount(100, $this->promotion);
        $this->assertEquals('20.20', $response);
    }
);

test('getCartFlatDiscountAmount method calls same class methods as expected', function (): void {
    $mock = $this->createPartialMock(CartWideAsPerPaymentTypePromotionService::class, ['getPromotionTierValue']);

    $mock->expects($this->once())
        ->method('getPromotionTierValue')
        ->will($this->returnValue(5.20));

    $response = $mock->getCartFlatDiscountAmount(10.10, $this->promotion);
    $this->assertEquals(5.20, $response);
});

test(
    'getCartPercentageDiscountAmount method calls same class methods as expected',
    function ($cartSubTotal, $percentage): void {
        $mock = $this->createPartialMock(CartWideAsPerPaymentTypePromotionService::class, ['getPromotionTierValue']);

        $mock->expects($this->once())
            ->method('getPromotionTierValue')
            ->will($this->returnValue($percentage));

        $response = $mock->getCartPercentageDiscountAmount($cartSubTotal, $this->promotion);
        $this->assertEquals(CommonFunctions::numberFormat($percentage * $cartSubTotal / 100), $response);
    }
)->with([[500.30, 10.20], [200.52, 23.95], [698.23, 54.37]]);

test(
    'checkForApplicability method sets the saleMismatches when cart subtotal does not match with promotion tier',
    function (): void {
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->saleDiscountService = new SaleDiscountService();
        $this->checkSaleDetailsService->saleDiscountService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->promotion->promotionTiers = collect([
            new PromotionTier([
                'buy_value' => 10,
                'get_value' => 5,
            ]),
        ]);

        $cartWideAsPerPaymentTypePromotionService = new CartWideAsPerPaymentTypePromotionService();
        $cartWideAsPerPaymentTypePromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion
        );
    }
)->throws(
    HttpException::class,
    'Cart Wide as per payment discount is applied but it is not applicable as per our records. Subtotal: 0'
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

    $response = $this->cartWideAsPerPaymentTypePromotionService->getCartFlatDiscountAmount(
        $cartSubTotal,
        $this->promotion
    );
    $this->assertEquals($getValue, $response);
})->with([[40, 20], [25, 10], [35.68, 15], [11.23, 5], [20, 10], [5.20, 0], [500.20, 20]]);

test(
    'getCartDiscountAmount method returns cart discount amount',
    function (): void {
        $this->saleDetails['cart_discount_amount'] = 100;
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $response = $this->cartWideAsPerPaymentTypePromotionService->getCartDiscountAmount(
            $this->checkSaleDetailsService
        );
        $this->assertTrue(100.00 === $response);
    }
);

test(
    'getTotalAmountOfApplicablePaymentTypes method calls same class methods as expected',
    function (): void {
        $promotion = Promotion::factory()->make([
            'id' => 2,
            'company_id' => 1,
            'name' => 'Cart Wide As Per Payment Type Promotion',
            'promotion_applicable_type_id' => 1,
            'discount_type_id' => 1,
            'cart_wide_promotion_type_id' => CartWidePromotionTypes::AS_PER_PAYMENT_TYPE->value,
            'timeframe_type_id' => 1,
            'percentage' => 0,
            'flat_amount' => 0,
            'allow_employee' => false,
            'allow_registered_member' => false,
            'status' => true,
        ]);

        $paymentType1 = PaymentType::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Cash',
            'status' => true,
        ]);

        $paymentType2 = PaymentType::factory()->make([
            'id' => 2,
            'company_id' => 1,
            'name' => 'Card',
            'status' => true,
        ]);

        $promotion->paymentTypes = collect([$paymentType1, $paymentType2]);

        $this->saleDetails['cart_promotion_id'] = $promotion->id;
        $this->saleDetails['payments'][0] = [
            'type_id' => $paymentType1->id,
            'amount' => '100',
        ];
        $this->saleDetails['payments'][1] = [
            'type_id' => $paymentType2->id,
            'amount' => '200',
        ];
        $this->saleDetails['payments'][2] = [
            'type_id' => 3,
            'amount' => '200',
        ];

        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);

        $response = $this->cartWideAsPerPaymentTypePromotionService->getTotalAmountOfApplicablePaymentTypes(
            collect($this->checkSaleDetailsService->saleData->payments),
            $promotion
        );

        $this->assertEquals(300.00, $response);
    }
);
