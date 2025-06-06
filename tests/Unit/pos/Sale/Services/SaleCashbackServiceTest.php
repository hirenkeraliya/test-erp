<?php

declare(strict_types=1);

use App\Domains\Cashback\CashbackQueries;
use App\Domains\Cashback\Enums\ConditionTypes;
use App\Domains\Cashback\Enums\ExcludeByTypes;
use App\Domains\CashMovement\CashMovementQueries;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleCashbackService;
use App\Domains\SaleCashback\SaleCashbackQueries;
use App\Models\Cashback;
use App\Models\CashbackPrice;
use App\Models\Cashier;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->saleCashbackService = new SaleCashbackService();
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
                'total_price_paid' => '90',
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
        'cart_promotion_id' => null,
    ];

    $this->cashback = Cashback::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'exclude_by_type' => ExcludeByTypes::PRODUCTS->value,
        'name' => 'Cashback',
        'discount_value' => 10,
        'minimum_spend_amount' => 100,
        'status' => true,
    ]);

    $this->product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'upc' => 'abd123',
        'has_batch' => false,
    ]);
});

test('setDetails method works as expected', function (): void {
    $mock = $this->createPartialMock(SaleCashbackService::class, ['getCashback']);
    $cashback = new Cashback();

    $mock->expects($this->once())
        ->method('getCashback')
        ->will($this->returnValue($cashback));

    $mock->setDetails(new CheckSaleDetailsService());
    $this->assertTrue($mock->cashback === $cashback);
});

test('getCashback method works as expected', function (): void {
    $this->saleDetails['cashback_id'] = 1;
    $this->checkSaleDetailsService->companyId = 1;
    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $this->saleCashbackService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $cashback = Cashback::factory()->make([
        'company_id' => 1,
        'name' => 'Test',
        'discount_value' => 10.10,
        'minimum_spend_amount' => 10.20,
        'start_date' => now()->format('Y-m-d'),
        'end_date' => now()->format('Y-m-d'),
    ]);

    $this->mock(CashbackQueries::class, function ($mock) use ($cashback): void {
        $mock->shouldReceive('getByIdWithRelations')
            ->once()
            ->andReturn($cashback);
    });

    $response = $this->saleCashbackService->getCashback();
    expect($response->toArray())
            ->toHaveKey('company_id', $cashback->company_id)
            ->toHaveKey('name', $cashback->name)
            ->toHaveKey('discount_value', $cashback->discount_value)
            ->toHaveKey('minimum_spend_amount', $cashback->minimum_spend_amount)
            ->toHaveKey('start_date', $cashback->start_date)
            ->toHaveKey('end_date', $cashback->end_date);
});

test('saveCashback method calls respective methods of queries class as expected', function (): void {
    $this->saleDetails['cashback_id'] = 1;
    $this->saleCashbackService->cashback = $this->cashback;
    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $this->saleCashbackService->checkSaleDetailsService = $this->checkSaleDetailsService;

    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => $cashier->counter_update_id,
    ]);

    $this->mock(SaleCashbackQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(CashMovementQueries::class, function ($mock): void {
        $mock->shouldReceive('addNewForCashback')
            ->once();
    });

    $this->saleCashbackService->saveCashback($sale, $cashier->counter_update_id);
});

test(
    'getCashbackAmount method returns total cashback amount as expected when discount type is flat',
    function (): void {
        $this->cashback->discount_type_id = DiscountTypes::FLAT->value;
        $this->saleCashbackService->cashback = $this->cashback;
        $response = $this->saleCashbackService->getCashbackAmount(320);
        expect($response)->toBeFloat();
        $this->assertEquals(30, $response);
    }
);

test(
    'getCashbackAmount method returns total cashback amount as expected when discount type is percentage',
    function (): void {
        $this->cashback->discount_type_id = DiscountTypes::PERCENTAGE->value;
        $this->cashback->discount_value = 25;
        $this->saleCashbackService->cashback = $this->cashback;
        $response = $this->saleCashbackService->getCashbackAmount(320);
        expect($response)->toBeFloat();
        $this->assertEquals(80, $response);
    }
);

test(
    'checkForApplicability method throw exception when cash back not available for Specified date',
    function (): void {
        $this->saleDetails['cashback_id'] = 1;
        $this->saleDetails['cashback_amount'] = 40;
        $this->saleDetails['happened_at'] = '2023-08-20 10:10:10';
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleCashbackService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->cashback->start_date = '2023-08-21';
        $this->cashback->end_date = '2023-08-22';
        $this->saleCashbackService->cashback = $this->cashback;

        $this->saleCashbackService->checkForApplicability(100);
    }
)->throws(
    HttpException::class,
    'Specified cashback is available between 2023-08-21 to 2023-08-22 only. The sale date is 2023-08-20.'
);

test(
    'checkForApplicability method throw exception when specified cashback is not available for the lcoation',
    function (): void {
        $this->saleDetails['cashback_id'] = 1;
        $this->saleDetails['cashback_amount'] = 40;
        $this->saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Test',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->saleCashbackService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->cashback->start_date = now()->subDay()->format('Y-m-d');
        $this->cashback->end_date = now()->addDay()->format('Y-m-d');
        $this->cashback->locations = collect([
            Location::factory()->make([
                'id' => 2,
                'company_id' => 1,
                'type_id' => LocationTypes::STORE->value,
            ]),
        ]);
        $this->saleCashbackService->cashback = $this->cashback;

        $this->saleCashbackService->checkForApplicability(100);
    }
)->throws(HttpException::class, 'Specified cashback is not available for the location Test');

test(
    'checkForApplicability method throw exception when Minimum spend amount not match',
    function (): void {
        $this->saleDetails['cashback_id'] = 1;
        $this->saleDetails['cashback_amount'] = 40;
        $this->saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Test',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $mock = $this->createPartialMock(SaleCashbackService::class, ['getExcludeAmountForCashback']);

        $mock->expects($this->once())
            ->method('getExcludeAmountForCashback')
            ->will($this->returnValue(40));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->cashback->start_date = now()->subDay()->format('Y-m-d');
        $this->cashback->end_date = now()->addDay()->format('Y-m-d');
        $this->cashback->minimum_spend_amount = 100;
        $this->cashback->locations = collect([
            Location::factory()->make([
                'id' => 1,
                'company_id' => 1,
                'type_id' => LocationTypes::STORE->value,
            ]),
        ]);
        $mock->cashback = $this->cashback;

        $mock->checkForApplicability(100);
    }
)->throws(HttpException::class, 'Minimum spend amount for selected cashback is 100. But, the cart total is 60.');

test(
    'checkForApplicability method throw exception when cashback amount not match',
    function (): void {
        $this->saleDetails['cashback_id'] = 1;
        $this->saleDetails['cashback_amount'] = 40;
        $this->saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Test',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $mock = $this->createPartialMock(
            SaleCashbackService::class,
            ['getExcludeAmountForCashback', 'getCashbackAmount']
        );

        $mock->expects($this->once())
            ->method('getExcludeAmountForCashback')
            ->will($this->returnValue(10));

        $mock->expects($this->once())
            ->method('getCashbackAmount')
            ->will($this->returnValue(50));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->cashback->start_date = now()->subDay()->format('Y-m-d');
        $this->cashback->end_date = now()->addDay()->format('Y-m-d');
        $this->cashback->minimum_spend_amount = 20;
        $this->cashback->locations = collect([
            Location::factory()->make([
                'id' => 1,
                'company_id' => 1,
                'type_id' => LocationTypes::STORE->value,
            ]),
        ]);
        $mock->cashback = $this->cashback;

        $mock->checkForApplicability(100);
    }
)->throws(
    HttpException::class,
    'Cashback amount mismatched. The expected cashback amount is 50. And given cashback amount is 40.'
);

test(
    'checkForApplicability method return null when all check pass',
    function (): void {
        $this->saleDetails['cashback_id'] = 1;
        $this->saleDetails['cashback_amount'] = 40;
        $this->saleDetails['happened_at'] = now()->format('Y-m-d H:i:s');
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Test',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $mock = $this->createPartialMock(
            SaleCashbackService::class,
            ['getExcludeAmountForCashback', 'getCashbackAmount']
        );

        $mock->expects($this->once())
            ->method('getExcludeAmountForCashback')
            ->will($this->returnValue(10));

        $mock->expects($this->once())
            ->method('getCashbackAmount')
            ->will($this->returnValue(40));

        $mock->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->cashback->start_date = now()->subDay()->format('Y-m-d');
        $this->cashback->end_date = now()->addDay()->format('Y-m-d');
        $this->cashback->minimum_spend_amount = 20;
        $this->cashback->locations = collect([
            Location::factory()->make([
                'id' => 1,
                'company_id' => 1,
                'type_id' => LocationTypes::STORE->value,
            ]),
        ]);
        $mock->cashback = $this->cashback;

        $response = $mock->checkForApplicability(100);
        $this->assertNull($response);
    }
);

test(
    'getExcludeAmountForCashback method returns total exclude amount for cashback when exclude type is product',
    function (): void {
        $this->checkSaleDetailsService->products = collect([
            '0' => $this->product,
        ]);
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->checkSaleDetailsService->saleData->items);
        $this->saleCashbackService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->cashback->products = collect([
            '0' => $this->product,
        ]);
        $this->saleCashbackService->cashback = $this->cashback;
        $this->saleCashbackService->checkSaleDetailsService->saleMismatches = new Collection([]);

        $response = $this->saleCashbackService->getExcludeAmountForCashback();
        $this->assertTrue(90.0 === $response);
    }
);

test(
    'getExcludeAmountForCashback method returns total exclude amount for cashback when exclude type is product and product not match',
    function (): void {
        $this->checkSaleDetailsService->products = collect([
            '0' => $this->product,
        ]);
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->checkSaleDetailsService->saleData->items);
        $this->saleCashbackService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->cashback->products = collect([
            '0' => Product::factory()->make([
                'id' => 2,
                'company_id' => 1,
                'unit_of_measure_id' => 1,
                'season_id' => 1,
                'department_id' => 1,
                'color_id' => 1,
                'size_id' => 1,
                'brand_id' => 1,
                'style_id' => 1,
                'upc' => 'abd123',
                'has_batch' => false,
            ]),
        ]);
        $this->saleCashbackService->cashback = $this->cashback;
        $this->saleCashbackService->checkSaleDetailsService->saleMismatches = new Collection([]);

        $response = $this->saleCashbackService->getExcludeAmountForCashback();
        $this->assertTrue(0.0 === $response);
    }
);

test(
    'getExcludeAmountForCashback method returns total exclude amount for cashback when exclude type is categories',
    function (): void {
        $this->product->categories = collect([
            Category::factory()->make([
                'id' => 1,
                'company_id' => 1,
            ]),
        ]);
        $this->checkSaleDetailsService->products = collect([
            '0' => $this->product,
        ]);
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->checkSaleDetailsService->saleData->items);
        $this->saleCashbackService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->cashback->exclude_by_type = ExcludeByTypes::CATEGORIES->value;
        $this->cashback->categories = collect([
            Category::factory()->make([
                'id' => 1,
                'company_id' => 1,
            ]),
        ]);
        $this->saleCashbackService->cashback = $this->cashback;
        $this->saleCashbackService->checkSaleDetailsService->saleMismatches = new Collection([]);

        $response = $this->saleCashbackService->getExcludeAmountForCashback();
        $this->assertTrue(90.0 === $response);
    }
);

test(
    'getExcludeAmountForCashback method returns total exclude amount for cashback when exclude type is categories and category not match',
    function (): void {
        $this->product->categories = collect([
            Category::factory()->make([
                'id' => 1,
                'company_id' => 1,
            ]),
        ]);
        $this->checkSaleDetailsService->products = collect([
            '0' => $this->product,
        ]);
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->checkSaleDetailsService->saleData->items);
        $this->saleCashbackService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->cashback->exclude_by_type = ExcludeByTypes::CATEGORIES->value;
        $this->cashback->categories = collect([
            Category::factory()->make([
                'id' => 2,
                'company_id' => 1,
            ]),
        ]);
        $this->saleCashbackService->cashback = $this->cashback;
        $this->saleCashbackService->checkSaleDetailsService->saleMismatches = new Collection([]);

        $response = $this->saleCashbackService->getExcludeAmountForCashback();
        $this->assertTrue(0.0 === $response);
    }
);

test(
    'getExcludeAmountForCashback method returns total exclude amount for cashback when exclude type is original item price',
    function (): void {
        $this->checkSaleDetailsService->products = collect([
            '0' => $this->product,
        ]);
        $this->saleDetails['cashback_id'] = 1;
        $this->saleDetails['cashback_amount'] = 10;
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->checkSaleDetailsService->saleData->items);
        $this->saleCashbackService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->cashback->exclude_by_type = ExcludeByTypes::ORIGINAL_ITEM_PRICE->value;
        $this->cashback->cashbackPrices = collect([
            CashbackPrice::factory()->make([
                'id' => 1,
                'cashback_id' => $this->cashback->id,
                'condition_operator_type_id' => ConditionTypes::EQUAL->value,
                'amount' => 10.00,
            ]),
        ]);
        $this->saleCashbackService->cashback = $this->cashback;
        $this->saleCashbackService->checkSaleDetailsService->saleMismatches = new Collection([]);

        $response = $this->saleCashbackService->getExcludeAmountForCashback();
        $this->assertTrue(90.0 === $response);
    }
);

test(
    'getExcludeAmountForCashback method returns total exclude amount for cashback when exclude type is original item price not match',
    function (): void {
        $this->checkSaleDetailsService->products = collect([
            '0' => $this->product,
        ]);
        $this->saleDetails['cashback_id'] = 1;
        $this->saleDetails['cashback_amount'] = 10;
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->checkSaleDetailsService->saleData->items);
        $this->saleCashbackService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->cashback->exclude_by_type = ExcludeByTypes::ORIGINAL_ITEM_PRICE->value;
        $this->cashback->cashbackPrices = collect([
            CashbackPrice::factory()->make([
                'id' => 1,
                'cashback_id' => $this->cashback->id,
                'condition_operator_type_id' => ConditionTypes::EQUAL->value,
                'amount' => 9.00,
            ]),
        ]);
        $this->saleCashbackService->cashback = $this->cashback;
        $this->saleCashbackService->checkSaleDetailsService->saleMismatches = new Collection([]);

        $response = $this->saleCashbackService->getExcludeAmountForCashback();
        $this->assertTrue(0.0 === $response);
    }
);

test(
    'getExcludeAmountForCashback method returns total exclude amount for cashback when exclude type is discount item price',
    function (): void {
        $this->checkSaleDetailsService->products = collect([
            '0' => $this->product,
        ]);
        $this->saleDetails['cashback_id'] = 1;
        $this->saleDetails['cashback_amount'] = 10;
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->checkSaleDetailsService->saleData->items);
        $this->saleCashbackService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->cashback->exclude_by_type = ExcludeByTypes::DISCOUNT_ITEM_PRICE->value;
        $this->cashback->cashbackPrices = collect([
            CashbackPrice::factory()->make([
                'id' => 1,
                'cashback_id' => $this->cashback->id,
                'condition_operator_type_id' => ConditionTypes::EQUAL->value,
                'amount' => 90.00,
            ]),
        ]);
        $this->saleCashbackService->cashback = $this->cashback;
        $this->saleCashbackService->checkSaleDetailsService->saleMismatches = new Collection([]);

        $response = $this->saleCashbackService->getExcludeAmountForCashback();

        $this->assertTrue(90.0 === $response);
    }
);

test(
    'getExcludeAmountForCashback method returns total exclude amount for cashback when exclude type is discount item price not match',
    function (): void {
        $this->checkSaleDetailsService->products = collect([
            '0' => $this->product,
        ]);
        $this->saleDetails['cashback_id'] = 1;
        $this->saleDetails['cashback_amount'] = 10;
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->cartItems = collect($this->checkSaleDetailsService->saleData->items);
        $this->saleCashbackService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $this->cashback->exclude_by_type = ExcludeByTypes::DISCOUNT_ITEM_PRICE->value;
        $this->cashback->cashbackPrices = collect([
            CashbackPrice::factory()->make([
                'id' => 1,
                'cashback_id' => $this->cashback->id,
                'condition_operator_type_id' => ConditionTypes::EQUAL->value,
                'amount' => 9.00,
            ]),
        ]);
        $this->saleCashbackService->cashback = $this->cashback;
        $this->saleCashbackService->checkSaleDetailsService->saleMismatches = new Collection([]);

        $response = $this->saleCashbackService->getExcludeAmountForCashback();
        $this->assertTrue(0.0 === $response);
    }
);

test(
    'checkForApplicability method throw exception when cash back apply in layaway sale',
    function (): void {
        $this->saleDetails['cashback_id'] = 1;
        $this->saleDetails['cashback_amount'] = 40;
        $this->saleDetails['happened_at'] = '2023-08-20 10:10:10';
        $this->saleDetails['is_layaway'] = true;
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleCashbackService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->cashback->start_date = '2023-08-21';
        $this->cashback->end_date = '2023-08-22';
        $this->saleCashbackService->cashback = $this->cashback;

        $this->saleCashbackService->checkForApplicability(100);
    }
)->throws(HttpException::class, 'Cashback cannot be generated for Layaway Sales.');

test(
    'checkForApplicability method throw exception when cash back apply in credit sale',
    function (): void {
        $this->saleDetails['cashback_id'] = 1;
        $this->saleDetails['cashback_amount'] = 40;
        $this->saleDetails['happened_at'] = '2023-08-20 10:10:10';
        $this->saleDetails['is_credit_sale'] = true;
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleCashbackService->checkSaleDetailsService = $this->checkSaleDetailsService;

        $this->cashback->start_date = '2023-08-21';
        $this->cashback->end_date = '2023-08-22';
        $this->saleCashbackService->cashback = $this->cashback;

        $this->saleCashbackService->checkForApplicability(100);
    }
)->throws(HttpException::class, 'Cashback cannot be generated for Credit Sales.');
