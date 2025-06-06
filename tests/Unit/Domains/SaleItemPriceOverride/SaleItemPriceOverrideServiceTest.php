<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Common\Enums\NegotiatorTypes;
use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Company\Enums\DiscountApplicableTypes;
use App\Domains\Director\DirectorQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Domains\SaleItemPriceOverride\Services\SaleItemPriceOverrideService;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCode\Enum\StoreManagerAuthorizationCodeStatuses;
use App\Domains\StoreManagerAuthorizationCode\StoreManagerAuthorizationCodeQueries;
use App\Models\Brand;
use App\Models\Cashier;
use App\Models\CashierGroup;
use App\Models\Company;
use App\Models\Director;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\StoreManager;
use App\Models\StoreManagerAuthorizationCode;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->saleItemPriceOverrideService = new SaleItemPriceOverrideService();

    $this->checkSaleDetailsService = new CheckSaleDetailsService();

    $this->product = commonGetProductDetails();
    $this->product->minimum_price = 200;
    $this->product->wholesale_price = 100;

    $this->saleDetails = [
        'offline_sale_id' => '1',
        'items' => [
            [
                'id' => $this->product->id,
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

    $this->employee = seedEmployeeForSaleItemPriceOverride();

    $this->storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee,
        'passcode' => '123456',
        'price_override_limit_percentage_for_item' => 10,
        'price_override_type' => PriceOverrideTypes::PERCENTAGE->value,
    ]);

    $this->director = Director::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee,
        'passcode' => '123456',
        'price_override_limit_percentage_for_item' => 10,
        'price_override_type' => PriceOverrideTypes::PERCENTAGE->value,
    ]);

    $this->cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee,
        'cashier_group_id' => 1,
    ]);

    $this->cashier->employee = $this->employee;
    $this->storeManager->employee = $this->employee;
    $this->director->employee = $this->employee;

    $this->cashierGroup = CashierGroup::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'price_override_limit_percentage_for_item' => 10,
        'price_override_type' => PriceOverrideTypes::PERCENTAGE->value,
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->brand = Brand::factory()->make([
        'id' => 1,
        'name' => 'test',
        'company_id' => 1,
    ]);

    $this->cashier->cashierGroup = $this->cashierGroup;

    $this->saleData = new SaleData(...$this->saleDetails);

    $this->cartItems = collect($this->saleData->items);
    $this->checkSaleDetailsService->products = collect([
        '0' => $this->product,
    ]);
});

test(
    'checkForApplicability method sets the saleMismatches when store manager passcode does not match with our records.',
    function (): void {
        $this->saleDetails['items'][0]['store_manager_id'] = 1;
        $this->saleDetails['items'][0]['store_manager_passcode'] = 1234;
        $this->saleDetails['items'][0]['price_override_amount'] = $this->storeManager->price_override_limit_percentage_for_item;
        $this->saleDetails['items'][0]['price_override_discount_amount'] = 10;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->appVersion = 100;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $this->storeManager->locations = collect([
            '0' => $this->location,
        ]);

        $this->storeManager->brands = collect([
            '0' => $this->brand,
        ]);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithStores')
                ->once()
                ->with($this->storeManager->id, 1)
                ->andReturn($this->storeManager);
        });

        $this->saleItemPriceOverrideService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );
    }
)->throws(HttpException::class, 'The specified Store Manager passcode for price override does not match our records.');

test(
    'checkForApplicability method sets the saleMismatches when price_override_discount_amount not set.',
    function (): void {
        $this->saleDetails['items'][0]['store_manager_id'] = 1;
        $this->saleDetails['items'][0]['store_manager_passcode'] = 1234;
        $this->saleDetails['items'][0]['price_override_amount'] = $this->storeManager->price_override_limit_percentage_for_item;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->appVersion = 100;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $this->storeManager->locations = collect([
            '0' => $this->location,
        ]);

        $this->storeManager->brands = collect([
            '0' => $this->brand,
        ]);

        $this->saleItemPriceOverrideService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );
    }
    // TODO: Temporary skip due to frontend is not able to take this task.
)->throws(HttpException::class, 'Price override discount amount is required for item price override amount.')->skip();

test(
    'checkForApplicability method sets the saleMismatches when director passcode does not match with our records.',
    function (): void {
        $this->saleDetails['items'][0]['director_id'] = 1;
        $this->saleDetails['items'][0]['director_passcode'] = 1234;
        $this->saleDetails['items'][0]['price_override_amount'] = $this->director->price_override_limit_percentage_for_item;
        $this->saleDetails['items'][0]['price_override_discount_amount'] = 10;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->appVersion = 100;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $this->director->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(DirectorQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithEmployeeAndLocations')
                ->once()
                ->with($this->director->id, 1)
                ->andReturn($this->director);
        });

        $this->saleItemPriceOverrideService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );
    }
)->throws(HttpException::class, 'The specified Director passcode for price override does not match our records.');

test(
    'checkForApplicability method sets the saleMismatches when store manager belongs to a different location.',
    function (): void {
        $this->saleDetails['items'][0]['store_manager_id'] = 1;
        $this->saleDetails['items'][0]['store_manager_passcode'] = $this->storeManager->passcode;
        $this->saleDetails['items'][0]['price_override_amount'] = $this->storeManager->price_override_limit_percentage_for_item;
        $this->saleDetails['items'][0]['price_override_discount_amount'] = 10;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->appVersion = 100;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 2,
            'company_id' => 2,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->storeManager->locations = collect([
            '0' => $location,
        ]);

        $this->storeManager->brands = collect([
            '0' => $this->brand,
        ]);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithStores')
                ->once()
                ->with($this->storeManager->id, 1)
                ->andReturn($this->storeManager);
        });

        $this->saleItemPriceOverrideService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );
    }
)->throws(HttpException::class, 'Specified Store Manager does not have access to the currently opened location.');

test(
    'checkForApplicability method sets the saleMismatches when the director belongs to a different location',
    function (): void {
        $this->saleDetails['items'][0]['director_id'] = 1;
        $this->saleDetails['items'][0]['director_passcode'] = $this->director->passcode;
        $this->saleDetails['items'][0]['price_override_amount'] = $this->director->price_override_limit_percentage_for_item;
        $this->saleDetails['items'][0]['price_override_discount_amount'] = 10;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->appVersion = 100;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 2,
            'company_id' => 2,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->director->locations = collect([
            '0' => $location,
        ]);

        $this->mock(DirectorQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithEmployeeAndLocations')
                ->once()
                ->with($this->director->id, 1)
                ->andReturn($this->director);
        });

        $this->saleItemPriceOverrideService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );
    }
)->throws(HttpException::class, 'Specified Director does not have access to the currently opened location.');

test(
    'checkForApplicability method sets the saleMismatches when the cashier belongs to a different location',
    function (): void {
        $this->saleDetails['items'][0]['cashier_id'] = 1;
        $this->saleDetails['items'][0]['price_override_amount'] = $this->cashier->cashierGroup->price_override_limit_percentage_for_item;
        $this->saleDetails['items'][0]['price_override_discount_amount'] = 10;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->appVersion = 100;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $location = Location::factory()->make([
            'id' => 2,
            'company_id' => 2,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->cashier->locations = collect([
            '0' => $location,
        ]);

        $this->mock(CashierQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithLocations')
                ->once()
                ->with($this->cashier->id, 1)
                ->andReturn($this->cashier);
        });

        $this->saleItemPriceOverrideService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );
    }
)->throws(HttpException::class, 'Specified Cashier does not have access to the currently opened location.');

test(
    'checkForApplicability method sets the saleMismatches when store manager tries the price override more than his limit.',
    function (): void {
        $this->saleDetails['items'][0]['price'] = 100;
        $this->saleDetails['items'][0]['store_manager_id'] = 1;
        $this->saleDetails['items'][0]['store_manager_passcode'] = '123456';
        $this->saleDetails['items'][0]['price_override_amount'] = 80;
        $this->saleDetails['items'][0]['price_override_discount_amount'] = 10;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->appVersion = 100;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $this->storeManager->locations = collect([
            '0' => $this->location,
        ]);

        $this->storeManager->brands = collect([
            '0' => $this->brand,
        ]);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithStores')
                ->once()
                ->with($this->storeManager->id, 1)
                ->andReturn($this->storeManager);
        });

        $mock = $this->createPartialMock(
            SaleItemPriceOverrideService::class,
            ['checkStoreManagerAuthorizationCode', 'getItemPrice', 'getActualPriceOverrideAmount']
        );

        $mock->expects($this->once())
            ->method('checkStoreManagerAuthorizationCode');

        $mock->expects($this->once())
            ->method('getItemPrice')
            ->will($this->returnValue(100));

        $mock->expects($this->once())
            ->method('getActualPriceOverrideAmount')
            ->will($this->returnValue(90));

        $mock->checkForApplicability($this->checkSaleDetailsService, $this->saleDetails['items'][0]);
    }
)->throws(
    HttpException::class,
    'The requested price override amount of (80) is less than the minimum allowed amount for the Store Manager (90)'
);

test(
    'checkForApplicability method sets the saleMismatches when the director tries to price override more than his limit.',
    function (): void {
        $this->saleDetails['items'][0]['price'] = 100;
        $this->saleDetails['items'][0]['director_id'] = 1;
        $this->saleDetails['items'][0]['director_passcode'] = '123456';
        $this->saleDetails['items'][0]['price_override_amount'] = 80;
        $this->saleDetails['items'][0]['price_override_discount_amount'] = 10;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->appVersion = 100;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->director->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(DirectorQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithEmployeeAndLocations')
                ->once()
                ->with($this->director->id, 1)
                ->andReturn($this->director);
        });

        $mock = $this->createPartialMock(
            SaleItemPriceOverrideService::class,
            ['getItemPrice', 'getActualPriceOverrideAmount']
        );

        $mock->expects($this->once())
            ->method('getItemPrice')
            ->will($this->returnValue(100));

        $mock->expects($this->once())
            ->method('getActualPriceOverrideAmount')
            ->will($this->returnValue(90));

        $mock->checkForApplicability($this->checkSaleDetailsService, $this->saleDetails['items'][0]);
    }
)->throws(
    HttpException::class,
    'The requested price override amount of (80) is less than the minimum allowed amount for the Director (90)'
);

test(
    'checkForApplicability method sets the saleMismatches when the cashier tries to price override amount more than his limit',
    function (): void {
        $this->saleDetails['items'][0]['price'] = 100;
        $this->saleDetails['items'][0]['cashier_id'] = 1;
        $this->saleDetails['items'][0]['price_override_amount'] = 80;
        $this->saleDetails['items'][0]['price_override_discount_amount'] = 10;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->appVersion = 100;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $this->cashier->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(CashierQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithLocations')
                ->once()
                ->with($this->cashier->id, 1)
                ->andReturn($this->cashier);
        });

        $mock = $this->createPartialMock(
            SaleItemPriceOverrideService::class,
            ['getItemPrice', 'getActualPriceOverrideAmount']
        );

        $mock->expects($this->once())
            ->method('getItemPrice')
            ->will($this->returnValue(100));

        $mock->expects($this->once())
            ->method('getActualPriceOverrideAmount')
            ->will($this->returnValue(90));

        $mock->checkForApplicability($this->checkSaleDetailsService, $this->saleDetails['items'][0]);
    }
)->throws(
    HttpException::class,
    'The requested price override amount of (80) is less than the minimum allowed amount for the Cashier (90)'
);

test(
    'checkForApplicability method sets the saleMismatches when the cashier is inactive and perform price override',
    function (): void {
        $this->saleDetails['items'][0]['price'] = 100;
        $this->saleDetails['items'][0]['cashier_id'] = 1;
        $this->saleDetails['items'][0]['price_override_amount'] = 60;
        $this->saleDetails['items'][0]['price_override_discount_amount'] = 10;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->employee->status = false;

        $this->cashier->employee = $this->employee;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->appVersion = 100;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $this->cashier->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(CashierQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithLocations')
                ->once()
                ->with($this->cashier->id, 1)
                ->andReturn($this->cashier);
        });

        $this->saleItemPriceOverrideService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );
    }
)->throws(HttpException::class, 'Specified Cashier: Test Test account is inactive. Please contact the admin.');

test(
    'checkForApplicability method sets the saleMismatches when the price override amount is pass zero(0)',
    function ($priceOverrideAmount): void {
        $this->saleDetails['items'][0]['price'] = 100;
        $this->saleDetails['items'][0]['cashier_id'] = 1;
        $this->saleDetails['items'][0]['price_override_discount_amount'] = 10;
        $this->saleDetails['items'][0]['price_override_amount'] = $priceOverrideAmount;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->cashier->employee = $this->employee;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->appVersion = 100;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $this->cashier->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(CashierQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithLocations')
                ->never()
                ->with($this->cashier->id, 1)
                ->andReturn($this->cashier);
        });

        $this->saleItemPriceOverrideService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );
    }
)->with([[0.0], [0]])->throws(
    HttpException::class,
    'The specified product Product 1 price override amount must be more than 0'
);

test(
    'checkForApplicability method sets the saleMismatches when director is inactive and perform price override',
    function (): void {
        $this->saleDetails['items'][0]['price'] = 100;
        $this->saleDetails['items'][0]['director_id'] = 1;
        $this->saleDetails['items'][0]['director_passcode'] = '123456';
        $this->saleDetails['items'][0]['price_override_amount'] = 90;
        $this->saleDetails['items'][0]['price_override_discount_amount'] = 10;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->employee->status = false;

        $this->director->employee = $this->employee;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->appVersion = 100;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $this->director->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(DirectorQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithEmployeeAndLocations')
                ->once()
                ->with($this->director->id, 1)
                ->andReturn($this->director);
        });

        $this->saleItemPriceOverrideService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );
    }
)->throws(HttpException::class, 'Specified Director: Test Test account is inactive. Please contact the admin.');

test(
    'checkForApplicability method sets the saleMismatches when store manager inactive and perform price override',
    function (): void {
        $this->saleDetails['items'][0]['price'] = 100;
        $this->saleDetails['items'][0]['store_manager_id'] = 1;
        $this->saleDetails['items'][0]['store_manager_passcode'] = 123456;
        $this->saleDetails['items'][0]['price_override_amount'] = 80;
        $this->saleDetails['items'][0]['price_override_discount_amount'] = 80;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->employee->status = false;

        $this->storeManager->employee = $this->employee;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->appVersion = 100;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $this->storeManager->locations = collect([
            '0' => $this->location,
        ]);

        $this->storeManager->brands = collect([
            '0' => $this->brand,
        ]);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithStores')
                ->once()
                ->with($this->storeManager->id, 1)
                ->andReturn($this->storeManager);
        });

        $this->saleItemPriceOverrideService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );
    }
)->throws(HttpException::class, 'Specified Store Manager: Test Test account is inactive. Please contact the admin.');

test(
    'getPriceOverrideLimitPercentage method returns the price override limit percentage as expected.',
    function (): void {
        $response = $this->saleItemPriceOverrideService->getPriceOverrideLimitPercentage(
            $this->cashier,
            NegotiatorTypes::CASHIER->value
        );

        expect($response)->toBeFloat();
        $this->assertEquals($this->cashier->cashierGroup->price_override_limit_percentage_for_item, $response);
    }
);

test(
    'getPriceOverrideDiscountAmount method returns the price override discount amount when discount type is flat',
    function (): void {
        $this->storeManager->price_override_type = PriceOverrideTypes::FLAT->value;
        $response = $this->saleItemPriceOverrideService->getPriceOverrideDiscountAmount(
            $this->storeManager,
            $this->checkSaleDetailsService,
            NegotiatorTypes::STORE_MANAGER->value,
            (float) 200,
            $this->saleDetails['items'][0]['id']
        );

        expect($response)->toBe(100.00);
    }
);

test(
    'getPriceOverrideDiscountAmount method returns the price override discount amount when discount type is Percentage',
    function (): void {
        $this->storeManager->price_override_type = PriceOverrideTypes::PERCENTAGE->value;
        $response = $this->saleItemPriceOverrideService->getPriceOverrideDiscountAmount(
            $this->storeManager,
            $this->checkSaleDetailsService,
            NegotiatorTypes::STORE_MANAGER->value,
            (float) $this->saleDetails['items'][0]['price'],
            $this->saleDetails['items'][0]['id']
        );

        expect($response)->toBe(1.00);
    }
);

test(
    'getItemDiscountAmount method returns the item discount amount as expected.',
    function (): void {
        $this->saleDetails['items'][0]['price'] = 100;
        $this->saleDetails['items'][0]['quantity'] = 1;
        $this->saleDetails['items'][0]['price_override_amount'] = 80;

        $mock = $this->createPartialMock(SaleItemPriceOverrideService::class, ['getItemTotal']);

        $mock->expects($this->once())
            ->method('getItemTotal')
            ->will($this->returnValue(100));

        $response = $mock->getItemDiscountAmount($this->checkSaleDetailsService, $this->saleDetails['items'][0]);

        expect($response)->toBe(20.0);
    }
);

test(
    'getItemDiscountAmount method returns the item discount amount as expected when price_override_discount_amount is pass.',
    function (): void {
        $this->saleDetails['items'][0]['price'] = 100;
        $this->saleDetails['items'][0]['quantity'] = 1;
        $this->saleDetails['items'][0]['price_override_amount'] = 80;
        $this->saleDetails['items'][0]['price_override_discount_amount'] = 30;

        $mock = $this->createPartialMock(SaleItemPriceOverrideService::class, ['getItemPrice']);

        $response = $mock->getItemDiscountAmount($this->checkSaleDetailsService, $this->saleDetails['items'][0]);

        expect($response)->toBe(30.0);
    }
);

test(
    'checkNonRegularProduct method add sale mismatches if price over ride apply on non-regular product.',
    function (): void {
        $this->product->type_id = ProductTypes::SPECIAL_ORDER->value;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->saleItemPriceOverrideService->checkNonRegularProduct(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]['id']
        );
    }
)->throws(
    HttpException::class,
    'Price override is applicable only on regular products. The type of product with the name Product 1 is Special Order.'
);

it('returns the staff price when the user is not a member and does not have member details', function (): void {
    $itemPrice = 100.00;
    $allowedPriceOverrideDiscountAmount = 10.00;

    $product = new Product();
    $product->id = 1;
    $product->staff_price = 80.00;

    $customSaleData = $this->saleDetails;
    $customSaleData['employee_id'] = 1;
    unset($customSaleData['member_id']);

    $this->checkSaleDetailsService->saleData = new SaleData(...$customSaleData);

    $this->checkSaleDetailsService->products = collect([$product]);

    $result = $this->saleItemPriceOverrideService->getActualPriceOverrideAmount(
        $allowedPriceOverrideDiscountAmount,
        $product->id,
        $this->saleDetails['items'][0],
        $this->checkSaleDetailsService
    );

    expect($result)->toBe(80.00);
});

it('returns the staff price when the user is a member and does have member details', function (): void {
    $allowedPriceOverrideDiscountAmount = 10.00;
    $cartItemId = 123;

    $product = new Product();
    $product->staff_price = 80.00;

    $this->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData = $this->saleData;

    $this->checkSaleDetailsService->products = collect([$product]);

    $this->checkSaleDetailsService->saleDiscountService = $this->mock(
        SaleDiscountService::class,
        function ($mock): void {
            $mock->shouldReceive('applyDreamPriceAndItemPromotionOn')
                ->once()
                ->andReturn(1000);
        }
    );

    $result = $this->saleItemPriceOverrideService->getActualPriceOverrideAmount(
        $allowedPriceOverrideDiscountAmount,
        $cartItemId,
        $this->saleDetails['items'][0],
        $this->checkSaleDetailsService
    );

    expect($result)->toBe(90.00);
});

test(
    'getItemPrice method return item price when set Discount Applicable Type by Additional Discount On Already Discounted Prices.',
    function (): void {
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->appVersion = 1510;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $this->checkSaleDetailsService->saleDiscountService = $this->mock(
            SaleDiscountService::class,
            function ($mock): void {
                $mock->shouldReceive('applyDreamPriceAndItemPromotionOn')
                    ->once()
                    ->andReturn(500);
            }
        );

        $response = $this->saleItemPriceOverrideService->getItemPrice(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );

        expect($response)->toBe(50.0);
    }
);

test(
    'getItemPrice method return item price when set Discount Applicable Type by Discount Applied To The Original Price.',
    function (): void {
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->appVersion = 101;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::DISCOUNT_APPLIED_TO_THE_ORIGINAL_PRICE->value,
            'default_country_id' => 1,
        ]);

        $this->checkSaleDetailsService->saleDiscountService = $this->mock(
            SaleDiscountService::class,
            function ($mock): void {
                $mock->shouldReceive('applyDreamPriceAndItemPromotionOn')
                    ->times(0)
                    ->andReturn(500);
            }
        );

        $response = $this->saleItemPriceOverrideService->getItemPrice(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );

        expect($response)->toBe(10.00);
    }
);

test(
    'getItemPrice method return item price when appVersion less then 100.',
    function (): void {
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->appVersion = 99;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::DISCOUNT_APPLIED_TO_THE_ORIGINAL_PRICE->value,
            'default_country_id' => 1,
        ]);

        $this->checkSaleDetailsService->saleDiscountService = $this->mock(
            SaleDiscountService::class,
            function ($mock): void {
                $mock->shouldReceive('applyDreamPriceAndItemPromotionOn')
                    ->times(0)
                    ->andReturn(500);
            }
        );

        $response = $this->saleItemPriceOverrideService->getItemPrice(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );

        expect($response)->toBe(10.00);
    }
);

test(
    'getPriceOverrideLimitFlat method returns the price override limit flat when negotiatorType is Cashier.',
    function (): void {
        $response = $this->saleItemPriceOverrideService->getPriceOverrideLimitFlat(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]['id'],
            NegotiatorTypes::CASHIER->value,
            400.00
        );

        expect($response)->toBe(200.00);
    }
);

test(
    'getPriceOverrideLimitFlat method returns the price override limit flat when negotiatorType is not Cashier.',
    function (): void {
        $response = $this->saleItemPriceOverrideService->getPriceOverrideLimitFlat(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]['id'],
            NegotiatorTypes::STORE_MANAGER->value,
            200
        );

        expect($response)->toBe(100.00);
    }
);

test(
    'getPriceOverrideLimitType method returns the price override limit type when negotiatorType is Cashier.',
    function (): void {
        $this->cashierGroup->price_override_type = PriceOverrideTypes::FLAT->value;
        $this->cashier->cashierGroup = $this->cashierGroup;

        $response = $this->saleItemPriceOverrideService->getPriceOverrideLimitType(
            $this->cashier,
            NegotiatorTypes::CASHIER->value
        );

        expect($response)->toBe(PriceOverrideTypes::FLAT->value);
    }
);

test(
    'getPriceOverrideLimitType method returns the price override limit type when negotiatorType is not Cashier.',
    function (): void {
        $response = $this->saleItemPriceOverrideService->getPriceOverrideLimitType(
            $this->storeManager,
            NegotiatorTypes::STORE_MANAGER->value
        );

        expect($response)->toBe(PriceOverrideTypes::PERCENTAGE->value);
    }
);

test(
    'checkForApplicability method sets the saleMismatches when store manager cannot price override the particular brand',
    function (): void {
        $this->saleDetails['items'][0]['price'] = 100;
        $this->saleDetails['items'][0]['store_manager_id'] = 1;
        $this->saleDetails['items'][0]['store_manager_passcode'] = 123456;
        $this->saleDetails['items'][0]['price_override_amount'] = 80;
        $this->saleDetails['items'][0]['price_override_discount_amount'] = 80;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->employee->status = true;
        $this->product->brand = $this->brand;

        $this->storeManager->employee = $this->employee;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->appVersion = 100;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'default_country_id' => 1,
        ]);

        $this->storeManager->locations = collect([
            '0' => $this->location,
        ]);

        $this->storeManager->brands = collect([
            '0' => collect([]),
        ]);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithStores')
                ->once()
                ->with($this->storeManager->id, 1)
                ->andReturn($this->storeManager);
        });

        $this->saleItemPriceOverrideService->checkForApplicability(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );
    }
)->throws(HttpException::class, 'Unfortunately, Store Manager does not permit the sale of test brand products.');

test(
    'checkNonRegularProduct method return null when product type is regular product.',
    function (): void {
        $this->product->type_id = ProductTypes::REGULAR_PRODUCT->value;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->saleItemPriceOverrideService->checkNonRegularProduct(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]['id']
        );

        $this->assertNull($response);
    }
);

test(
    'checkNonRegularProduct method return null when product type is bundle product.',
    function (): void {
        $this->product->type_id = ProductTypes::REGULAR_PRODUCT->value;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->saleItemPriceOverrideService->checkNonRegularProduct(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]['id']
        );

        $this->assertNull($response);
    }
);

test(
    'checkNonRegularProduct method return null when product type is assembly product.',
    function (): void {
        $this->product->type_id = ProductTypes::ASSEMBLY_PRODUCT->value;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->saleItemPriceOverrideService->checkNonRegularProduct(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]['id']
        );

        $this->assertNull($response);
    }
);

test(
    'checkStoreManagerAuthorizationCode method return null when store_manager_authorization_code not set',
    function (): void {
        $cartItem = [];
        $cartItem['id'] = 1;
        $cartItem['store_manager_passcode'] = '123456';
        $cartItem['store_manager_id'] = 1;
        $cartItem['price'] = 12;
        $cartItem['quantity'] = 5;
        $cartItem['item_discount_amount'] = 60;

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->saleItemPriceOverrideService->checkStoreManagerAuthorizationCode(
            $this->checkSaleDetailsService,
            $cartItem
        );
        $this->assertNull($response);
    }
);

test(
    'checkStoreManagerAuthorizationCode method return null when store_manager_authorization_code set null',
    function (): void {
        $cartItem = [];
        $cartItem['id'] = 1;
        $cartItem['store_manager_passcode'] = '123456';
        $cartItem['store_manager_id'] = 1;
        $cartItem['store_manager_authorization_code'] = null;
        $cartItem['price'] = 12;
        $cartItem['quantity'] = 5;
        $cartItem['item_discount_amount'] = 60;

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData->happened_at = now()->format('Y-m-d H:i:s');

        $response = $this->saleItemPriceOverrideService->checkStoreManagerAuthorizationCode(
            $this->checkSaleDetailsService,
            $cartItem
        );

        $this->assertNull($response);
    }
);

test('checkStoreManagerAuthorizationCode method throw exception when code not match in database', function (): void {
    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn(null);
    });

    $cartItem = [];
    $cartItem['id'] = 1;
    $cartItem['store_manager_passcode'] = '123456';
    $cartItem['store_manager_authorization_code'] = '1234';
    $cartItem['store_manager_id'] = 1;
    $cartItem['price'] = 12;
    $cartItem['quantity'] = 5;
    $cartItem['item_discount_amount'] = 60;

    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData->happened_at = now()->format('Y-m-d H:i:s');

    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->saleItemPriceOverrideService->checkStoreManagerAuthorizationCode($this->checkSaleDetailsService, $cartItem);
})->throws(
    HttpException::class,
    'Specified Store manager authorization code does not correspond with our records.'
);

test(
    'checkStoreManagerAuthorizationCode method throw exception when code not match with store manager',
    function (): void {
        $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
            'id' => 1,
            'store_manager_id' => 2,
            'code' => '1234',
            'expiry_date' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
            $storeManagerAuthorizationCode
        ): void {
            $mock->shouldReceive('getByCode')
                ->once()
                ->andReturn($storeManagerAuthorizationCode);
        });
        $cartItem = [];
        $cartItem['id'] = 1;
        $cartItem['store_manager_passcode'] = '123456';
        $cartItem['store_manager_authorization_code'] = '1234';
        $cartItem['store_manager_id'] = 1;
        $cartItem['price'] = 12;
        $cartItem['quantity'] = 5;
        $cartItem['item_discount_amount'] = 60;

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData->happened_at = now()->format('Y-m-d H:i:s');

        $this->saleItemPriceOverrideService->checkStoreManagerAuthorizationCode(
            $this->checkSaleDetailsService,
            $cartItem
        );
    }
)->throws(HttpException::class, 'Specified Store manager authorization code and store manager not match.');

test('checkStoreManagerAuthorizationCode method throw exception when code not active', function (): void {
    $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'code' => '1234',
        'expiry_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'status' => StoreManagerAuthorizationCodeStatuses::CANCELLED->value,
    ]);

    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
        $storeManagerAuthorizationCode
    ): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn($storeManagerAuthorizationCode);
    });

    $cartItem = [];
    $cartItem['id'] = 1;
    $cartItem['store_manager_passcode'] = '123456';
    $cartItem['store_manager_authorization_code'] = '1234';
    $cartItem['store_manager_id'] = 1;
    $cartItem['price'] = 12;
    $cartItem['quantity'] = 5;
    $cartItem['item_discount_amount'] = 60;

    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData->happened_at = now()->format('Y-m-d H:i:s');

    $this->saleItemPriceOverrideService->checkStoreManagerAuthorizationCode($this->checkSaleDetailsService, $cartItem);
})->throws(HttpException::class, 'Specified Store manager authorization code is not active.');

test(
    'checkStoreManagerAuthorizationCode method throw exception when code is expire and happened_at set null',
    function (): void {
        $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'code' => '1234',
            'expiry_date' => now()->subDay()->format('Y-m-d H:i:s'),
            'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE,
        ]);

        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
            $storeManagerAuthorizationCode
        ): void {
            $mock->shouldReceive('getByCode')
                ->once()
                ->andReturn($storeManagerAuthorizationCode);
        });

        $cartItem = [];
        $cartItem['id'] = 1;
        $cartItem['store_manager_passcode'] = '123456';
        $cartItem['store_manager_authorization_code'] = '1234';
        $cartItem['store_manager_id'] = 1;
        $cartItem['price'] = 12;
        $cartItem['quantity'] = 5;
        $cartItem['item_discount_amount'] = 60;

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData->happened_at = now()->format('Y-m-d H:i:s');
        $this->saleItemPriceOverrideService->checkStoreManagerAuthorizationCode(
            $this->checkSaleDetailsService,
            $cartItem
        );
    }
)->throws(HttpException::class, 'Specified Store manager authorization code is expiry.');

test('checkStoreManagerAuthorizationCode return null as expected', function (): void {
    $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'code' => '1234',
        'expiry_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE,
    ]);

    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
        $storeManagerAuthorizationCode
    ): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn($storeManagerAuthorizationCode);
    });

    $cartItem = [];
    $cartItem['id'] = 1;
    $cartItem['store_manager_passcode'] = '123456';
    $cartItem['store_manager_authorization_code'] = '1234';
    $cartItem['store_manager_id'] = 1;
    $cartItem['price'] = 12;
    $cartItem['quantity'] = 5;
    $cartItem['item_discount_amount'] = 60;

    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData->happened_at = now()->format('Y-m-d H:i:s');

    $response = $this->saleItemPriceOverrideService->checkStoreManagerAuthorizationCode(
        $this->checkSaleDetailsService,
        $cartItem
    );

    $this->assertNull($response);
});

test(
    'checkForApplicability method sets the saleMismatches when the discount amount more then limit.',
    function (): void {
        $this->saleDetails['items'][0]['price'] = 100;
        $this->saleDetails['items'][0]['director_id'] = 1;
        $this->saleDetails['items'][0]['director_passcode'] = '123456';
        $this->saleDetails['items'][0]['price_override_amount'] = 80;
        $this->saleDetails['items'][0]['price_override_discount_amount'] = 40;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->appVersion = 100;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->director->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(DirectorQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithEmployeeAndLocations')
                ->once()
                ->with($this->director->id, 1)
                ->andReturn($this->director);
        });

        $mock = $this->createPartialMock(
            SaleItemPriceOverrideService::class,
            ['getItemPrice', 'getActualPriceOverrideAmount']
        );

        $mock->expects($this->once())
            ->method('getItemPrice')
            ->will($this->returnValue(100));

        $mock->expects($this->once())
            ->method('getActualPriceOverrideAmount')
            ->will($this->returnValue(90));

        $mock->checkForApplicability($this->checkSaleDetailsService, $this->saleDetails['items'][0]);
    }
)->throws(
    HttpException::class,
    'The requested price override amount of (80) is less than the minimum allowed amount for the Director (90)'
);

test(
    'getItemTotal method return item price when set Discount Applicable Type by Additional Discount On Already Discounted Prices.',
    function (): void {
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->appVersion = 1510;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'default_country_id' => 1,
        ]);

        $this->checkSaleDetailsService->saleDiscountService = $this->mock(
            SaleDiscountService::class,
            function ($mock): void {
                $mock->shouldReceive('applyDreamPriceAndItemPromotionOn')
                    ->once()
                    ->andReturn(500);
            }
        );

        $response = $this->saleItemPriceOverrideService->getItemTotal(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );

        expect($response)->toBe(500.0);
    }
);

test(
    'getItemTotal method return item price when set Discount Applicable Type by Discount Applied To The Original Price.',
    function (): void {
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->appVersion = 101;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::DISCOUNT_APPLIED_TO_THE_ORIGINAL_PRICE->value,
            'default_country_id' => 1,
        ]);

        $this->checkSaleDetailsService->saleDiscountService = $this->mock(
            SaleDiscountService::class,
            function ($mock): void {
                $mock->shouldReceive('applyDreamPriceAndItemPromotionOn')
                    ->times(0)
                    ->andReturn(500);
            }
        );

        $response = $this->saleItemPriceOverrideService->getItemTotal(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );

        expect($response)->toBe(100.00);
    }
);

test(
    'getItemTotal method return item price when appVersion less then 100.',
    function (): void {
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->appVersion = 99;
        $this->checkSaleDetailsService->company = Company::factory()->make([
            'discount_applicable_type' => DiscountApplicableTypes::DISCOUNT_APPLIED_TO_THE_ORIGINAL_PRICE->value,
            'default_country_id' => 1,
        ]);

        $this->checkSaleDetailsService->saleDiscountService = $this->mock(
            SaleDiscountService::class,
            function ($mock): void {
                $mock->shouldReceive('applyDreamPriceAndItemPromotionOn')
                    ->times(0)
                    ->andReturn(500);
            }
        );

        $response = $this->saleItemPriceOverrideService->getItemTotal(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );

        expect($response)->toBe(100.00);
    }
);

test(
    'getItemPriceAfterDreamPriceAndItemPromotion method return item price.',
    function (): void {
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->saleDiscountService = $this->mock(
            SaleDiscountService::class,
            function ($mock): void {
                $mock->shouldReceive('applyDreamPriceAndItemPromotionOn')
                    ->times(1)
                    ->andReturn(500);
            }
        );

        $response = $this->saleItemPriceOverrideService->getItemPriceAfterDreamPriceAndItemPromotion(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]
        );

        expect($response)->toBe(50.00);
    }
);

function seedEmployeeForSaleItemPriceOverride(): Employee
{
    return Employee::factory()->make([
        'id' => 2,
        'company_id' => 1,
        'designation_id' => 1,
        'first_name' => 'Test',
        'last_name' => 'Test',
    ]);
}
