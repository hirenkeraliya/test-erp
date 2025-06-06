<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Common\Enums\NegotiatorTypes;
use App\Domains\Director\DirectorQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\SalePriceOverride\Services\SalePriceOverrideService;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCode\Enum\StoreManagerAuthorizationCodeStatuses;
use App\Domains\StoreManagerAuthorizationCode\StoreManagerAuthorizationCodeQueries;
use App\Models\Cashier;
use App\Models\CashierGroup;
use App\Models\Company;
use App\Models\Director;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StoreManager;
use App\Models\StoreManagerAuthorizationCode;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->salePriceOverrideService = new SalePriceOverrideService();

    $this->checkSaleDetailsService = new CheckSaleDetailsService();

    $this->product = commonGetProductDetails();

    $this->company = Company::factory()->make([
        'id' => 1,
        'code' => '135',
        'allow_price_override_cart_level' => 1,
        'default_country_id' => 1,
    ]);

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
        'cashier_id' => null,
        'store_manager_id' => null,
        'store_manager_passcode' => null,
        'director_id' => null,
        'director_passcode' => null,
        'cart_price_override_amount' => null,
        'cart_price_override_discount_amount' => 10,
    ];

    $this->employee = seedEmployeeForSalePriceOverride();

    $this->storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
        'passcode' => '123456',
        'price_override_limit_percentage_for_cart' => 10,
    ]);

    $this->director = Director::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
        'passcode' => '123456',
        'price_override_limit_percentage_for_cart' => 10,
    ]);

    $this->cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
        'cashier_group_id' => 1,
    ]);

    $this->cashierGroup = CashierGroup::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'price_override_limit_percentage_for_cart' => 10,
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->cashier->employee = $this->employee;
    $this->storeManager->employee = $this->employee;
    $this->director->employee = $this->employee;

    $this->cashier->cashierGroup = $this->cashierGroup;

    $this->saleData = new SaleData(...$this->saleDetails);

    $this->checkSaleDetailsService->company = $this->company;
});

test(
    'checkForApplicability method sets the saleMismatches when the cart_price_override_discount_amount not set.',
    function (): void {
        $company = Company::factory()->make([
            'id' => 1,
            'code' => '135',
            'allow_price_override_cart_level' => 1,
            'default_country_id' => 1,
        ]);
        $this->saleDetails['store_manager_id'] = 1;
        $this->saleDetails['store_manager_passcode'] = '1234';
        unset($this->saleDetails['cart_price_override_discount_amount']);
        $this->saleDetails['cart_price_override_amount'] = $this->storeManager->price_override_limit_percentage_for_cart;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->company = $company;
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->storeManager->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithStores')
                ->never()
                ->with($this->storeManager->id, 1)
                ->andReturn($this->storeManager);
        });

        $this->salePriceOverrideService->checkForApplicability($this->checkSaleDetailsService, 500, 500);
    }
    // TODO: Temporary commenting due to frontend is not able to take this task.
)->throws(HttpException::class, 'offline id: 1 cart price override discount amount required')->skip();

test(
    'checkForApplicability method sets the saleMismatches when the cart_price_override_discount_amount set 0.',
    function (): void {
        $company = Company::factory()->make([
            'id' => 1,
            'code' => '135',
            'allow_price_override_cart_level' => 1,
            'default_country_id' => 1,
        ]);
        $this->saleDetails['store_manager_id'] = 1;
        $this->saleDetails['store_manager_passcode'] = '1234';
        $this->saleDetails['cart_price_override_discount_amount'] = 0;
        $this->saleDetails['cart_price_override_amount'] = $this->storeManager->price_override_limit_percentage_for_cart;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->company = $company;
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->storeManager->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithStores')
                ->never()
                ->with($this->storeManager->id, 1)
                ->andReturn($this->storeManager);
        });

        $this->salePriceOverrideService->checkForApplicability($this->checkSaleDetailsService, 500, 500);
    }
    // TODO: Temporary commenting due to frontend is not able to take this task.
)->throws(HttpException::class, 'offline id: 1 cart price override discount amount required')->skip();

test(
    'checkForApplicability method sets the saleMismatches when the cart level price override option is disabled at company level.',
    function (): void {
        $company = Company::factory()->make([
            'id' => 1,
            'code' => '135',
            'allow_price_override_cart_level' => 0,
            'default_country_id' => 1,
        ]);
        $this->saleDetails['store_manager_id'] = 1;
        $this->saleDetails['store_manager_passcode'] = '1234';
        $this->saleDetails['cart_price_override_amount'] = $this->storeManager->price_override_limit_percentage_for_cart;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->company = $company;
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->storeManager->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithStores')
                ->never()
                ->with($this->storeManager->id, 1)
                ->andReturn($this->storeManager);
        });

        $this->salePriceOverrideService->checkForApplicability($this->checkSaleDetailsService, 500, 500);
    }
)->throws(HttpException::class, 'The ability to override prices at the cart level has been deactivated.');

test(
    'checkForApplicability method sets the saleMismatches when store manager passcode does not match with our records.',
    function (): void {
        $this->saleDetails['store_manager_id'] = 1;
        $this->saleDetails['store_manager_passcode'] = '1234';
        $this->saleDetails['cart_price_override_amount'] = $this->storeManager->price_override_limit_percentage_for_cart;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->storeManager->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithStores')
                ->once()
                ->with($this->storeManager->id, 1)
                ->andReturn($this->storeManager);
        });

        $this->salePriceOverrideService->checkForApplicability($this->checkSaleDetailsService, 500, 500);
    }
)->throws(
    HttpException::class,
    'The Store Manager The provided passcode for price override does not correspond with our records.'
);

test(
    'checkForApplicability method sets the saleMismatches when director passcode does not match with our records.',
    function (): void {
        $this->saleDetails['director_id'] = 1;
        $this->saleDetails['director_passcode'] = '1234';
        $this->saleDetails['cart_price_override_amount'] = $this->director->price_override_limit_percentage_for_cart;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

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

        $this->salePriceOverrideService->checkForApplicability($this->checkSaleDetailsService, 500, 500);
    }
)->throws(
    HttpException::class,
    'The Director The provided passcode for price override does not correspond with our records.'
);

test(
    'checkForApplicability method sets the saleMismatches when store manager belongs to a different location.',
    function (): void {
        $this->saleDetails['store_manager_id'] = 1;
        $this->saleDetails['store_manager_passcode'] = $this->storeManager->passcode;
        $this->saleDetails['cart_price_override_amount'] = $this->storeManager->price_override_limit_percentage_for_cart;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $location = Location::factory()->make([
            'id' => 2,
            'company_id' => 2,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $this->storeManager->locations = collect([
            '0' => $location,
        ]);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithStores')
                ->once()
                ->with($this->storeManager->id, 1)
                ->andReturn($this->storeManager);
        });

        $this->salePriceOverrideService->checkForApplicability($this->checkSaleDetailsService, 500, 500);
    }
)->throws(
    HttpException::class,
    'The Store Manager you selected does not have permission to access the currently open location.'
);

test(
    'checkForApplicability method sets the saleMismatches when the director belongs to a different location',
    function (): void {
        $this->saleDetails['director_id'] = 1;
        $this->saleDetails['director_passcode'] = $this->director->passcode;
        $this->saleDetails['cart_price_override_amount'] = $this->director->price_override_limit_percentage_for_cart;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

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

        $this->salePriceOverrideService->checkForApplicability($this->checkSaleDetailsService, 500, 500);
    }
)->throws(
    HttpException::class,
    'The Director you selected does not have permission to access the currently open location.'
);

test(
    'checkForApplicability method sets the saleMismatches when the cashier belongs to a different location',
    function (): void {
        $this->saleDetails['cashier_id'] = 1;
        $this->saleDetails['cart_price_override_amount'] = $this->cashier->cashierGroup->price_override_limit_percentage_for_cart;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

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

        $this->salePriceOverrideService->checkForApplicability($this->checkSaleDetailsService, 100, 500);
    }
)->throws(
    HttpException::class,
    'The Cashier you selected does not have permission to access the currently open location.'
);

test(
    'checkForApplicability method sets the saleMismatches when store manager tries the price override more than his limit.',
    function (): void {
        $this->saleDetails['store_manager_id'] = 1;
        $this->saleDetails['store_manager_passcode'] = '123456';
        $this->saleDetails['cart_price_override_amount'] = 80;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->storeManager->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithStores')
                ->once()
                ->with($this->storeManager->id, 1)
                ->andReturn($this->storeManager);
        });

        $mock = $this->createPartialMock(SalePriceOverrideService::class, ['checkStoreManagerAuthorizationCode']);

        $mock->expects($this->once())
            ->method('checkStoreManagerAuthorizationCode');

        $mock->checkForApplicability($this->checkSaleDetailsService, 100, 100);
    }
)->throws(
    HttpException::class,
    'Requested Price override amount (80) is less than what is minimum allowed to the Store Manager (90)'
);

test(
    'checkForApplicability method sets the saleMismatches when store manager tries the price override discount amount more than his limit.',
    function (): void {
        $this->saleDetails['store_manager_id'] = 1;
        $this->saleDetails['store_manager_passcode'] = '123456';
        $this->saleDetails['cart_price_override_amount'] = 80;
        $this->saleDetails['cart_price_override_discount_amount'] = 80;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->storeManager->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithStores')
                ->once()
                ->with($this->storeManager->id, 1)
                ->andReturn($this->storeManager);
        });

        $mock = $this->createPartialMock(SalePriceOverrideService::class, ['checkStoreManagerAuthorizationCode']);

        $mock->expects($this->once())
            ->method('checkStoreManagerAuthorizationCode');

        $mock->checkForApplicability($this->checkSaleDetailsService, 100);
    }
)->throws(
    HttpException::class,
    'Requested Price override discount amount (80) is more than what is minimum discount amount allowed to the Store Manager (10)'
    // TODO: Temporary commenting due to frontend is not able to take this task.
)->skip();

test(
    'checkForApplicability method sets the saleMismatches when the director tries to price override more than his limit.',
    function (): void {
        $this->saleDetails['director_id'] = 1;
        $this->saleDetails['director_passcode'] = '123456';
        $this->saleDetails['cart_price_override_amount'] = 80;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

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

        $this->salePriceOverrideService->checkForApplicability($this->checkSaleDetailsService, 100, 100);
    }
)->throws(
    HttpException::class,
    'Requested Price override amount (80) is less than what is minimum allowed to the Director (90)'
);

test(
    'checkForApplicability method sets the saleMismatches when the cashier tries to price override amount more than his limit',
    function (): void {
        $this->saleDetails['cashier_id'] = 1;
        $this->saleDetails['cart_price_override_amount'] = 80;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->cashier->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(CashierQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithLocations')
                ->once()
                ->with($this->cashier->id, 1)
                ->andReturn($this->cashier);
        });

        $this->salePriceOverrideService->checkForApplicability($this->checkSaleDetailsService, 100, 100);
    }
)->throws(
    HttpException::class,
    'Requested Price override amount (80) is less than what is minimum allowed to the Cashier (90)'
);

test(
    'checkForApplicability method sets the saleMismatches when the cashier inactive and perform price override',
    function (): void {
        $this->saleDetails['cashier_id'] = 1;
        $this->saleDetails['cart_price_override_amount'] = 90;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->employee->status = false;

        $this->cashier->employee = $this->employee;

        $this->cashier->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(CashierQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithLocations')
                ->once()
                ->with($this->cashier->id, 1)
                ->andReturn($this->cashier);
        });

        $this->salePriceOverrideService->checkForApplicability($this->checkSaleDetailsService, 100, 500);
    }
)->throws(HttpException::class, 'Specified Cashier: Test Test account is inactive. Please contact admin.');

test(
    'checkForApplicability method sets the saleMismatches when the director inactive and perform price override.',
    function (): void {
        $this->saleDetails['director_id'] = 1;
        $this->saleDetails['director_passcode'] = '123456';
        $this->saleDetails['cart_price_override_amount'] = 90;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->employee->status = false;

        $this->director->employee = $this->employee;

        $this->director->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(DirectorQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithEmployeeAndLocations')
                ->once()
                ->with($this->director->id, 1)
                ->andReturn($this->director);
        });

        $this->salePriceOverrideService->checkForApplicability($this->checkSaleDetailsService, 100, 500);
    }
)->throws(HttpException::class, 'Specified Director: Test Test account is inactive. Please contact admin.');

test(
    'checkForApplicability method sets the saleMismatches when store manager inactive and perform price override',
    function (): void {
        $this->saleDetails['store_manager_id'] = 1;
        $this->saleDetails['store_manager_passcode'] = '123456';
        $this->saleDetails['cart_price_override_amount'] = 90;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->employee->status = false;

        $this->storeManager->employee = $this->employee;

        $this->storeManager->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithStores')
                ->once()
                ->with($this->storeManager->id, 1)
                ->andReturn($this->storeManager);
        });

        $this->salePriceOverrideService->checkForApplicability($this->checkSaleDetailsService, 100, 500);
    }
)->throws(HttpException::class, 'Specified Store Manager: Test Test account is inactive. Please contact admin.');

test(
    'checkForApplicability method sets the saleMismatches when the cart price override amount is pass zero(0)',
    function (): void {
        $this->saleDetails['store_manager_id'] = 1;
        $this->saleDetails['store_manager_passcode'] = '123456';
        $this->saleDetails['cart_price_override_amount'] = 0.0;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->storeManager->employee = $this->employee;

        $this->storeManager->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithStores')
                ->never()
                ->with($this->storeManager->id, 1)
                ->andReturn($this->storeManager);
        });

        $this->salePriceOverrideService->checkForApplicability($this->checkSaleDetailsService, 100, 500);
    }
)->throws(HttpException::class, 'offline id: 1 cart price override amount must be more than 0');

test(
    'getPriceOverrideLimitPercentage method returns the price override limit percentage as expected.',
    function (): void {
        $response = $this->salePriceOverrideService->getPriceOverrideLimitPercentage(
            $this->cashier,
            NegotiatorTypes::CASHIER->value
        );
        expect($response)->toBeFloat();
        $this->assertEquals($this->cashier->cashierGroup->price_override_limit_percentage_for_cart, $response);
    }
);

test(
    'getPriceOverrideDiscountAmount method returns the price override discount amount as expected.',
    function (): void {
        $response = $this->salePriceOverrideService->getPriceOverrideDiscountAmount(10, (float) 100);

        $this->assertEquals($response, 10);
    }
);

test(
    'getDiscountAmount method returns the item discount amount as expected.',
    function (): void {
        $this->saleDetails['cart_price_override_amount'] = 80;

        $response = $this->salePriceOverrideService->getDiscountAmount(100, 80, null);

        $this->assertEquals($response, 20);
    }
);

test(
    'getDiscountAmount method returns the item discount amount as expected when discount amount set.',
    function (): void {
        $this->saleDetails['cart_price_override_amount'] = 80;

        $response = $this->salePriceOverrideService->getDiscountAmount(100, 80, 30);

        $this->assertEquals($response, 30);
    }
);

test(
    'checkForApplicability method thrown an exceptions if Cart price override amount more than the cart subtotal.',
    function (): void {
        $this->saleDetails['cashier_id'] = 1;
        $this->saleDetails['cart_price_override_amount'] = 200;
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->cashier->locations = collect([
            '0' => $this->location,
        ]);

        $this->mock(CashierQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithLocations')
                ->once()
                ->with($this->cashier->id, 1)
                ->andReturn($this->cashier);
        });

        $this->salePriceOverrideService->checkForApplicability($this->checkSaleDetailsService, 100, 500);
    }
)->throws(HttpException::class, 'The price override amount for the cart should not exceed the subtotal of the cart.');

test(
    'checkStoreManagerAuthorizationCode method return null when store_manager_authorization_code not set',
    function (): void {
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $response = $this->salePriceOverrideService->checkStoreManagerAuthorizationCode($this->checkSaleDetailsService);
        $this->assertNull($response);
    }
);

test('checkStoreManagerAuthorizationCode method throw exception when code not match in database', function (): void {
    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn(null);
    });

    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData = $this->saleData;

    $this->checkSaleDetailsService->saleData->store_manager_authorization_code = '1234';

    $this->salePriceOverrideService->checkStoreManagerAuthorizationCode($this->checkSaleDetailsService);
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

        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->saleData->store_manager_authorization_code = '1234';

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->salePriceOverrideService->checkStoreManagerAuthorizationCode($this->checkSaleDetailsService);
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

    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData = $this->saleData;

    $this->checkSaleDetailsService->saleData->store_manager_authorization_code = '1234';
    $this->checkSaleDetailsService->saleData->store_manager_id = 1;

    $this->salePriceOverrideService->checkStoreManagerAuthorizationCode($this->checkSaleDetailsService);
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

        $this->checkSaleDetailsService->saleMismatches = collect([]);
        $this->saleData = new SaleData(...$this->saleDetails);
        $this->checkSaleDetailsService->saleData = $this->saleData;

        $this->checkSaleDetailsService->saleData->store_manager_authorization_code = '1234';
        $this->checkSaleDetailsService->saleData->store_manager_id = 1;
        $this->checkSaleDetailsService->saleData->happened_at = now()->format('Y-m-d H:i:s');
        $this->salePriceOverrideService->checkStoreManagerAuthorizationCode($this->checkSaleDetailsService);
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

    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData = $this->saleData;

    $this->checkSaleDetailsService->saleData->store_manager_authorization_code = '1234';
    $this->checkSaleDetailsService->saleData->store_manager_id = 1;

    $response = $this->salePriceOverrideService->checkStoreManagerAuthorizationCode($this->checkSaleDetailsService);

    $this->assertNull($response);
});

function seedEmployeeForSalePriceOverride(): Employee
{
    return Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
        'first_name' => 'Test',
        'last_name' => 'Test',
    ]);
}
