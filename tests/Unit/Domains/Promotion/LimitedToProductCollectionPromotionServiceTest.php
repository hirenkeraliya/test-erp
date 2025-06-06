<?php

declare(strict_types=1);

use App\CommonFunctions;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Promotion\Services\LimitedToProductCollectionPromotionService;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductCollectionProduct;
use App\Models\Promotion;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->limitedToProductCollectionPromotionService = new LimitedToProductCollectionPromotionService();

    $this->checkSaleDetailsService = new CheckSaleDetailsService();
    $this->saleDiscountService = new SaleDiscountService();

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
        'status' => 2,
    ]);

    $this->promotion = Promotion::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'Item wise limited to collection',
        'promotion_applicable_type_id' => 2,
        'discount_type_id' => 1,
        'item_wise_promotion_type_id' => 2,
        'timeframe_type_id' => 1,
        'percentage' => 0,
        'flat_amount' => 10.2,
        'is_member_required' => false,
        'only_for_employees' => false,
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
                'promotion_id' => 1,
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
    'checkForApplicability method sets the saleMismatches when none of the product product collection are in the promotion product collection',
    function (): void {
        $this->productCollection = ProductCollection::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $this->productCollectionProduct = ProductCollectionProduct::factory()->make([
            'product_collection_id' => $this->productCollection->id,
            'product_id' => $this->product->id,
        ]);

        $this->product->productCollectionProducts = collect($this->productCollectionProduct);

        $this->promotion->productCollections = collect(new ProductCollection());

        $this->checkSaleDetailsService->saleDiscountService = $this->saleDiscountService;

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['item_discount_amount'] = 0;

        $this->limitedToProductCollectionPromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            $this->product,
            0.00,
            new SaleDiscountService(),
        );
    }
)->throws(HttpException::class, 'Specified promotion is not applicable on the given product collections ABC.');

test(
    'checkForApplicability method sets the saleMismatches when none of the product product collection and promotion product collection are in the promotion product collection',
    function (): void {
        $this->productCollectionA = ProductCollection::factory()->make([
            'id' => 2,
            'company_id' => 1,
        ]);

        $this->productCollection = ProductCollection::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $this->productCollectionProduct = ProductCollectionProduct::factory()->make([
            'product_collection_id' => $this->productCollection->id,
            'product_id' => $this->product->id,
        ]);

        $this->product->productCollectionProducts = collect([$this->productCollectionProduct]);

        $this->promotion->productCollections = collect([$this->productCollectionA]);

        $this->checkSaleDetailsService->saleDiscountService = $this->saleDiscountService;

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['item_discount_amount'] = 0;

        $this->limitedToProductCollectionPromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            $this->product,
            0.00,
            new SaleDiscountService(),
        );
    }
)->throws(HttpException::class, 'Specified promotion is not applicable on the given product collections ABC.');

test(
    'checkForApplicability method sets the saleMismatches when the discount amount doest not matched with actual discount amount',
    function (): void {
        $itemDiscountPass = 15;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleDetails['items'][0]['item_discount_amount'] = $itemDiscountPass;
        $this->promotion->discount_type_id = DiscountTypes::FLAT->value;
        $this->checkSaleDetailsService->saleDiscountService = $this->saleDiscountService;

        $this->productCollection = ProductCollection::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $this->productCollectionProduct = ProductCollectionProduct::factory()->make([
            'product_collection_id' => $this->productCollection->id,
            'product_id' => $this->product->id,
        ]);

        $this->product->productCollectionProducts = collect([$this->productCollectionProduct]);

        $this->promotion->productCollections = collect([$this->productCollection]);

        $this->limitedToProductCollectionPromotionService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->promotion,
            $this->saleDetails['items'][0],
            $this->product,
            $this->promotion->flat_amount,
            new SaleDiscountService(),
        );
    }
)->throws(
    HttpException::class,
    'Requested discount amount of 15 does not match with our calculations. The calculated discount amount is 10.2'
);

test(
    'calculateItemDiscountAmount method calls getItemPercentageDiscountAmount method when promotion discount type is percentage',
    function (): void {
        $this->promotion->discount_type_id = DiscountTypes::PERCENTAGE->value;

        $mock = $this->createPartialMock(
            LimitedToProductCollectionPromotionService::class,
            ['getItemPercentageDiscountAmount']
        );

        $mock->expects($this->once())
            ->method('getItemPercentageDiscountAmount')
            ->will($this->returnValue(20.20));

        $response = $mock->calculateItemDiscountAmount($this->promotion, $this->saleDetails['items'][0], 100);
        $this->assertEquals('20.20', $response);
    }
);

test(
    'calculateItemDiscountAmount method returns as expected',
    function (): void {
        $this->promotion->discount_type_id = DiscountTypes::FLAT->value;

        $mock = $this->createPartialMock(
            LimitedToProductCollectionPromotionService::class,
            ['getItemPercentageDiscountAmount']
        );

        $response = $mock->calculateItemDiscountAmount($this->promotion, $this->saleDetails['items'][0], 100);
        $this->assertEquals(100, $response);
    }
);

test(
    'calculateItemDiscountAmount method returns the flat amount when item subtotal is less than flat amount',
    function (): void {
        $this->promotion->discount_type_id = DiscountTypes::FLAT->value;

        $mock = $this->createPartialMock(
            LimitedToProductCollectionPromotionService::class,
            ['getItemPercentageDiscountAmount']
        );

        $response = $mock->calculateItemDiscountAmount($this->promotion, $this->saleDetails['items'][0], 500);
        $this->assertEquals(102.0, $response);
    }
);

test(
    'getItemPercentageDiscountAmount method calls same class methods as expected',
    function ($itemTotal, $percentage): void {
        $this->promotion->percentage = $percentage;

        $response = $this->limitedToProductCollectionPromotionService
            ->getItemPercentageDiscountAmount($itemTotal, $this->promotion);
        $this->assertEquals(CommonFunctions::numberFormat($percentage * $itemTotal / 100), $response);
    }
)->with([[500.30, 10.20], [200.52, 23.95], [698.23, 54.37]]);

test('getItemDiscountAmount method return 0 when not set item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;

    $response = $this->limitedToProductCollectionPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return 0 when set null item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = null;

    $response = $this->limitedToProductCollectionPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(0.0, $response);
});

test('getItemDiscountAmount method return same pass item_discount_amount in cart', function (): void {
    $cartItem['id'] = 2;
    $cartItem['price'] = 1;
    $cartItem['quantity'] = 1;
    $cartItem['item_discount_amount'] = 10.10;

    $response = $this->limitedToProductCollectionPromotionService->getItemDiscountAmount($cartItem);

    $this->assertEquals(10.10, $response);
});
