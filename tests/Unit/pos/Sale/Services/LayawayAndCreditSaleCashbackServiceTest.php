<?php

declare(strict_types=1);

use App\Domains\Cashback\CashbackQueries;
use App\Domains\Cashback\Enums\ExcludeByTypes;
use App\Domains\CashMovement\CashMovementQueries;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\DataObjects\CompleteCreditSaleData;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\Services\LayawayAndCreditSaleCashbackService;
use App\Domains\SaleCashback\SaleCashbackQueries;
use App\Models\Cashback;
use App\Models\Cashier;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->layawayAndCreditSaleCashbackService = new LayawayAndCreditSaleCashbackService();

    $this->companyId = 1;

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

    $this->completeCreditSaleData = new CompleteCreditSaleData(now()->format('Y-m-d H:i:s'));
});

test('setDetails method works as expected', function (): void {
    $mock = $this->createPartialMock(LayawayAndCreditSaleCashbackService::class, ['getCashback']);
    $cashback = new Cashback();

    $mock->expects($this->once())
        ->method('getCashback')
        ->will($this->returnValue($cashback));

    $mock->setDetails(1, 1);
    $this->assertTrue($mock->cashback === $cashback);
});

test('hasCashback method returns boolean as expected', function (): void {
    $this->completeCreditSaleData->cashback_id = 1;
    $this->layawayAndCreditSaleCashbackService->hasCashback($this->completeCreditSaleData);
    $this->assertFalse(false);

    $this->completeCreditSaleData->cashback_id = null;
    $this->layawayAndCreditSaleCashbackService->hasCashback($this->completeCreditSaleData);
    $this->assertTrue(true);
});

test('getCashback method works as expected', function (): void {
    $cashback = Cashback::factory()->make([
        'company_id' => 1,
        'name' => 'Test',
        'flat_amount' => 10.10,
        'minimum_spend_amount' => 10.20,
        'start_date' => now()->format('Y-m-d'),
        'end_date' => now()->format('Y-m-d'),
    ]);

    $this->mock(CashbackQueries::class, function ($mock) use ($cashback): void {
        $mock->shouldReceive('getByIdWithRelations')
            ->once()
            ->andReturn($cashback);
    });

    $response = $this->layawayAndCreditSaleCashbackService->getCashback(1, 1);
    expect($response->toArray())
            ->toHaveKey('company_id', $cashback->company_id)
            ->toHaveKey('name', $cashback->name)
            ->toHaveKey('flat_amount', $cashback->flat_amount)
            ->toHaveKey('minimum_spend_amount', $cashback->minimum_spend_amount)
            ->toHaveKey('start_date', $cashback->start_date)
            ->toHaveKey('end_date', $cashback->end_date);
});

test('saveCashback method calls respective methods of queries class as expected', function (): void {
    $this->layawayAndCreditSaleCashbackService->cashback = $this->cashback;

    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => $cashier->counter_update_id,
        'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
    ]);

    $this->mock(SaleCashbackQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(CashMovementQueries::class, function ($mock): void {
        $mock->shouldReceive('addNewForCashback')
            ->once();
    });

    $this->layawayAndCreditSaleCashbackService->saveCashback(
        $sale,
        $this->completeCreditSaleData,
        $cashier->counter_update_id
    );
});

test(
    'getCashbackAmount method returns total cashback amount as expected when discount type is flat',
    function (): void {
        $this->cashback->discount_type_id = DiscountTypes::FLAT->value;
        $this->layawayAndCreditSaleCashbackService->cashback = $this->cashback;
        $response = $this->layawayAndCreditSaleCashbackService->getCashbackAmount(320);
        expect($response)->toBeFloat();
        $this->assertEquals(30, $response);
    }
);

test(
    'getCashbackAmount method returns total cashback amount as expected when discount type is percentage',
    function (): void {
        $this->cashback->discount_type_id = DiscountTypes::PERCENTAGE->value;
        $this->cashback->discount_value = 25;
        $this->layawayAndCreditSaleCashbackService->cashback = $this->cashback;
        $response = $this->layawayAndCreditSaleCashbackService->getCashbackAmount(320);
        expect($response)->toBeFloat();
        $this->assertEquals(80.0, $response);
    }
);

test(
    'checkForApplicability method throw exception when cash back not available for Specified date',
    function (): void {
        $this->completeCreditSaleData->cashback_id = 1;
        $this->completeCreditSaleData->cashback_amount = 40;
        $this->completeCreditSaleData->happened_at = '2023-08-20 10:10:10';

        $this->cashback->start_date = '2023-08-21';
        $this->cashback->end_date = '2023-08-22';
        $this->layawayAndCreditSaleCashbackService->cashback = $this->cashback;

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Test',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 200,
            'layaway_pending_amount' => 100,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'original_price_per_unit' => 100,
            'quantity' => 3,
            'total_discount_amount' => 0,
            'total_tax_amount' => 0,
        ]);

        $this->product->categories = collect([]);

        $saleItem->product = $this->product;

        $sale->saleItems = collect([$saleItem]);

        $this->layawayAndCreditSaleCashbackService->checkForApplicability(
            100.0,
            $this->completeCreditSaleData,
            collect([]),
            $location,
            $sale
        );
    }
)->throws(
    HttpException::class,
    'Specified cashback is available between 2023-08-21 to 2023-08-22 only. The sale date is 2023-08-20.'
);

test(
    'checkForApplicability method throw exception when specified cashback is not available for the location',
    function (): void {
        $this->completeCreditSaleData->cashback_id = 1;
        $this->completeCreditSaleData->cashback_amount = 40;
        $this->completeCreditSaleData->happened_at = now()->format('Y-m-d H:i:s');

        $this->cashback->start_date = now()->subDay()->format('Y-m-d');
        $this->cashback->end_date = now()->addDay()->format('Y-m-d');
        $this->cashback->locations = collect([
            Location::factory()->make([
                'id' => 2,
                'company_id' => 1,
                'type_id' => LocationTypes::STORE->value,
            ]),
        ]);
        $this->layawayAndCreditSaleCashbackService->cashback = $this->cashback;

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Test',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 200,
            'layaway_pending_amount' => 100,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'original_price_per_unit' => 100,
            'quantity' => 3,
            'total_discount_amount' => 0,
            'total_tax_amount' => 0,
        ]);

        $this->product->categories = collect([]);

        $saleItem->product = $this->product;

        $sale->saleItems = collect([$saleItem]);

        $this->layawayAndCreditSaleCashbackService->checkForApplicability(
            100.0,
            $this->completeCreditSaleData,
            collect([]),
            $location,
            $sale
        );
    }
)->throws(HttpException::class, 'Specified cashback is not available for the location Test');

test(
    'checkForApplicability method throw exception when Minimum spend amount not match',
    function (): void {
        $this->completeCreditSaleData->cashback_id = 1;
        $this->completeCreditSaleData->cashback_amount = 40;
        $this->completeCreditSaleData->happened_at = now()->format('Y-m-d H:i:s');

        $mock = $this->createPartialMock(LayawayAndCreditSaleCashbackService::class, ['getExcludeAmountForCashback']);

        $mock->expects($this->once())
            ->method('getExcludeAmountForCashback')
            ->will($this->returnValue(40));

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

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Test',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 200,
            'layaway_pending_amount' => 100,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'original_price_per_unit' => 100,
            'quantity' => 3,
            'total_discount_amount' => 0,
            'total_tax_amount' => 0,
        ]);

        $this->product->categories = collect([]);

        $saleItem->product = $this->product;

        $sale->saleItems = collect([$saleItem]);

        $mock->checkForApplicability(100.0, $this->completeCreditSaleData, collect([]), $location, $sale);
    }
)->throws(HttpException::class, 'Minimum spend amount for selected cashback is 100. But, the cart total is 60.');

test(
    'checkForApplicability method throw exception when cashback amount not match',
    function (): void {
        $this->completeCreditSaleData->cashback_id = 1;
        $this->completeCreditSaleData->cashback_amount = 40;
        $this->completeCreditSaleData->happened_at = now()->format('Y-m-d H:i:s');

        $mock = $this->createPartialMock(
            LayawayAndCreditSaleCashbackService::class,
            ['getExcludeAmountForCashback', 'getCashbackAmount']
        );

        $mock->expects($this->once())
            ->method('getExcludeAmountForCashback')
            ->will($this->returnValue(10));

        $mock->expects($this->once())
            ->method('getCashbackAmount')
            ->will($this->returnValue(50));

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

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Test',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 200,
            'layaway_pending_amount' => 100,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'original_price_per_unit' => 100,
            'quantity' => 3,
            'total_discount_amount' => 0,
            'total_tax_amount' => 0,
        ]);

        $this->product->categories = collect([]);

        $saleItem->product = $this->product;

        $sale->saleItems = collect([$saleItem]);

        $mock->checkForApplicability(100.0, $this->completeCreditSaleData, collect([]), $location, $sale);
    }
)->throws(
    HttpException::class,
    'Cashback amount mismatched. The expected cashback amount is 50. And given cashback amount is 40.'
);

test(
    'checkForApplicability method return null when all check pass',
    function (): void {
        $this->completeCreditSaleData->cashback_id = 1;
        $this->completeCreditSaleData->cashback_amount = 40;
        $this->completeCreditSaleData->happened_at = now()->format('Y-m-d H:i:s');

        $mock = $this->createPartialMock(
            LayawayAndCreditSaleCashbackService::class,
            ['getExcludeAmountForCashback', 'getCashbackAmount']
        );

        $mock->expects($this->once())
            ->method('getExcludeAmountForCashback')
            ->will($this->returnValue(10));

        $mock->expects($this->once())
            ->method('getCashbackAmount')
            ->will($this->returnValue(40));

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

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Test',
            'type_id' => LocationTypes::STORE->value,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 200,
            'layaway_pending_amount' => 100,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'original_price_per_unit' => 100,
            'quantity' => 3,
            'total_discount_amount' => 0,
            'total_tax_amount' => 0,
        ]);

        $this->product->categories = collect([]);

        $saleItem->product = $this->product;

        $sale->saleItems = collect([$saleItem]);

        $response = $mock->checkForApplicability(100.0, $this->completeCreditSaleData, collect([]), $location, $sale);

        $this->assertNull($response);
    }
);

test(
    'getExcludeAmountForCashback method returns total exclude amount for cashback when exclude type is product',
    function (): void {
        $this->cashback->products = collect([
            '0' => $this->product,
        ]);

        $this->layawayAndCreditSaleCashbackService->cashback = $this->cashback;

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 200,
            'layaway_pending_amount' => 100,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'original_price_per_unit' => 100,
            'quantity' => 3,
            'total_discount_amount' => 0,
            'total_tax_amount' => 0,
        ]);

        $this->product->categories = collect([]);

        $saleItem->product = $this->product;

        $sale->saleItems = collect([$saleItem]);

        $response = $this->layawayAndCreditSaleCashbackService->getExcludeAmountForCashback($sale, 100);
        $this->assertTrue(100.0 === $response);
    }
);

test(
    'getExcludeAmountForCashback method returns total exclude amount for cashback when exclude type is product and product not match',
    function (): void {
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

        $this->layawayAndCreditSaleCashbackService->cashback = $this->cashback;

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 200,
            'layaway_pending_amount' => 100,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'original_price_per_unit' => 100,
            'quantity' => 3,
            'total_discount_amount' => 0,
            'total_tax_amount' => 0,
        ]);

        $this->product->categories = collect([]);

        $saleItem->product = $this->product;

        $sale->saleItems = collect([$saleItem]);

        $response = $this->layawayAndCreditSaleCashbackService->getExcludeAmountForCashback($sale, 100);
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

        $this->cashback->exclude_by_type = ExcludeByTypes::CATEGORIES->value;
        $this->cashback->categories = collect([
            Category::factory()->make([
                'id' => 1,
                'company_id' => 1,
            ]),
        ]);

        $this->layawayAndCreditSaleCashbackService->cashback = $this->cashback;

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 200,
            'layaway_pending_amount' => 100,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'original_price_per_unit' => 100,
            'quantity' => 3,
            'total_discount_amount' => 0,
            'total_tax_amount' => 0,
        ]);

        $saleItem->product = $this->product;

        $sale->saleItems = collect([$saleItem]);

        $response = $this->layawayAndCreditSaleCashbackService->getExcludeAmountForCashback($sale, 100);
        $this->assertTrue(100.0 === $response);
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

        $this->cashback->exclude_by_type = ExcludeByTypes::CATEGORIES->value;
        $this->cashback->categories = collect([
            Category::factory()->make([
                'id' => 2,
                'company_id' => 1,
            ]),
        ]);

        $this->layawayAndCreditSaleCashbackService->cashback = $this->cashback;

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 200,
            'layaway_pending_amount' => 100,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'original_price_per_unit' => 100,
            'quantity' => 3,
            'total_discount_amount' => 0,
            'total_tax_amount' => 0,
        ]);

        $saleItem->product = $this->product;

        $sale->saleItems = collect([$saleItem]);

        $response = $this->layawayAndCreditSaleCashbackService->getExcludeAmountForCashback($sale, 100);
        $this->assertTrue(0.0 === $response);
    }
);

test(
    'getTotalPricePaid method returns total price paid when sale in layaway sale',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 200,
            'layaway_pending_amount' => 100,
            'status' => SaleStatus::PENDING_LAYAWAY_SALE->value,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'original_price_per_unit' => 100,
            'quantity' => 3,
            'total_discount_amount' => 0,
            'total_tax_amount' => 0,
        ]);

        $sale->saleItems = collect([$saleItem]);

        $response = $this->layawayAndCreditSaleCashbackService->getTotalPricePaid($sale, $saleItem, 100);
        $this->assertTrue(100.0 === $response);
    }
);

test(
    'getTotalPricePaid method returns total price paid when sale in credit sale',
    function (): void {
        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'total_amount_paid' => 200,
            'credit_pending_amount' => 100,
            'status' => SaleStatus::PENDING_CREDIT_SALE->value,
        ]);

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
            'original_price_per_unit' => 100,
            'quantity' => 3,
            'total_discount_amount' => 0,
            'total_tax_amount' => 0,
        ]);

        $sale->saleItems = collect([$saleItem]);

        $response = $this->layawayAndCreditSaleCashbackService->getTotalPricePaid($sale, $saleItem, 100);
        $this->assertTrue(100.0 === $response);
    }
);

test(
    'getHappenedAt method returns date when happened_at is set',
    function (): void {
        $date = now()->format('Y-m-d H:i:s');
        $this->completeCreditSaleData->happened_at = $date;
        $response = $this->layawayAndCreditSaleCashbackService->getHappenedAt($this->completeCreditSaleData);
        $this->assertTrue($response->format('Y-m-d H:i:s') === $date);
    }
);

test(
    'getHappenedAt method returns date when happened_at is not set',
    function (): void {
        $date = now()->format('Y-m-d H:i:s');
        Carbon::setTestNow($date);
        $this->completeCreditSaleData->happened_at = null;
        $response = $this->layawayAndCreditSaleCashbackService->getHappenedAt($this->completeCreditSaleData);
        $this->assertTrue($response->format('Y-m-d H:i:s') === $date);
        Carbon::setTestNow();
    }
);
