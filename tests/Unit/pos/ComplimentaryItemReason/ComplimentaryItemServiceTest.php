<?php

declare(strict_types=1);

use App\Domains\ComplimentaryItemReason\Services\ComplimentaryItemService;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\StoreManagerAuthorizationCode\Enum\StoreManagerAuthorizationCodeStatuses;
use App\Domains\StoreManagerAuthorizationCode\StoreManagerAuthorizationCodeQueries;
use App\Models\ComplimentaryItemReason;
use App\Models\Director;
use App\Models\Employee;
use App\Models\StoreManager;
use App\Models\StoreManagerAuthorizationCode;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->product = commonGetProductDetails();

    $this->complimentaryItemService = new ComplimentaryItemService();
    $this->checkSaleDetailsService = new CheckSaleDetailsService();
    $this->checkSaleDetailsService->products = collect([
        '0' => $this->product,
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
    ];
});

test(
    'checkForApplicability method throws an exception when Specified Complimentary Item Reason is not available in our records',
    function (): void {
        $this->complimentaryItemService->checkForApplicability(
            $this->checkSaleDetailsService,
            null,
            $this->saleDetails['items'],
            collect([]),
            collect([])
        );
    }
)->throws(HttpException::class, 'Specified Complimentary Item Reason is not available in our records.');

test(
    'checkForApplicability method throws an exception when the director is not available in our records',
    function (): void {
        $cartItem = [];
        $cartItem['id'] = 1;
        $cartItem['director_passcode'] = '123456';
        $cartItem['director_id'] = 2;
        $cartItem['quantity'] = 5;
        $cartItem['item_discount_amount'] = 60;

        $this->checkSaleDetailsService->companyId = 1;

        $employee = seedEmployeeForComplimentary();

        $director = Director::factory()->make([
            'id' => 1,
            'employee_id' => $employee->id,
        ]);

        $director->employee = $employee;

        $complimentaryItemReason = ComplimentaryItemReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $this->complimentaryItemService->checkForApplicability(
            $this->checkSaleDetailsService,
            $complimentaryItemReason,
            $cartItem,
            collect([$director]),
            collect([])
        );
    }
)->throws(HttpException::class, 'Specified Director is not available in our records.');

test(
    'checkForApplicability method throws an exception when the store manager is not available in our records',
    function (): void {
        $cartItem = [];
        $cartItem['id'] = 1;
        $cartItem['store_manager_passcode'] = '123456';
        $cartItem['store_manager_id'] = 2;
        $cartItem['quantity'] = 5;
        $cartItem['item_discount_amount'] = 60;

        $this->checkSaleDetailsService->companyId = 1;

        $employee = seedEmployeeForComplimentary();

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => $employee->id,
        ]);

        $storeManager->employee = $employee;

        $complimentaryItemReason = ComplimentaryItemReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $this->complimentaryItemService->checkForApplicability(
            $this->checkSaleDetailsService,
            $complimentaryItemReason,
            $cartItem,
            collect([]),
            collect([$storeManager])
        );
    }
)->throws(HttpException::class, 'Specified StoreManager is not available in our records.');

test(
    'checkForApplicability method throws an exception when Complimentary item not allowed without an authorization from the director or store manager',
    function (): void {
        $cartItem = [];
        $cartItem['id'] = 1;
        $cartItem['quantity'] = 5;
        $cartItem['item_discount_amount'] = 60;

        $this->checkSaleDetailsService->companyId = 1;

        $complimentaryItemReason = ComplimentaryItemReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $this->complimentaryItemService->checkForApplicability(
            $this->checkSaleDetailsService,
            $complimentaryItemReason,
            $cartItem,
            collect([]),
            collect([])
        );
    }
)->throws(
    HttpException::class,
    'Complimentary item not allowed without an authorization from the director or store manager.'
);

test(
    'checkForApplicability method sets the saleMismatches when director passcode does not match with our records',
    function (): void {
        $cartItem = [];
        $cartItem['id'] = 1;
        $cartItem['director_passcode'] = '123456';
        $cartItem['director_id'] = 1;
        $cartItem['store_manager_passcode'] = '123456';
        $cartItem['store_manager_id'] = 1;
        $cartItem['price'] = 10;
        $cartItem['quantity'] = 5;
        $cartItem['item_discount_amount'] = 60;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->companyId = 1;

        $employee = seedEmployeeForComplimentary();

        $director = Director::factory()->make([
            'id' => 1,
            'employee_id' => $employee->id,
            'passcode' => '1234',
        ]);

        $director->employee = $employee;

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => $employee->id,
            'passcode' => '1234',
        ]);

        $storeManager->employee = $employee;

        $complimentaryItemReason = ComplimentaryItemReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $this->complimentaryItemService->checkForApplicability(
            $this->checkSaleDetailsService,
            $complimentaryItemReason,
            $cartItem,
            collect([$director]),
            collect([$storeManager])
        );
    }
)->throws(
    HttpException::class,
    'Specified 123456 passcode of director id: 1 for complimentary item does not match with our records.'
);

test(
    'checkForApplicability method sets the saleMismatches when the item discount amount is not specified',
    function (): void {
        $cartItem = [];
        $cartItem['id'] = 1;
        $cartItem['store_manager_passcode'] = '123456';
        $cartItem['price'] = 12;
        $cartItem['store_manager_id'] = 1;
        $cartItem['quantity'] = 5;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->companyId = 1;

        $employee = seedEmployeeForComplimentary();

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => $employee->id,
            'passcode' => '123456',
        ]);

        $storeManager->employee = $employee;

        $complimentaryItemReason = ComplimentaryItemReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $mock = $this->createPartialMock(ComplimentaryItemService::class, ['checkStoreManagerAuthorizationCode']);

        $mock->expects($this->once())
            ->method('checkStoreManagerAuthorizationCode');

        $mock->checkForApplicability(
            $this->checkSaleDetailsService,
            $complimentaryItemReason,
            $cartItem,
            collect([]),
            collect([$storeManager])
        );
    }
)->throws(HttpException::class, 'Item discount amount is required for the complimentary item discount.');

test(
    'checkForApplicability method sets the saleMismatches when store manager inactive and perform complimentary',
    function (): void {
        $cartItem = [];
        $cartItem['id'] = 1;
        $cartItem['store_manager_passcode'] = '123456';
        $cartItem['price'] = 12;
        $cartItem['store_manager_id'] = 1;
        $cartItem['quantity'] = 5;
        $cartItem['item_discount_amount'] = 60;

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->companyId = 1;

        $employee = seedEmployeeForComplimentary();
        $employee->status = false;

        $storeManager = StoreManager::factory()->make([
            'id' => 1,
            'employee_id' => $employee->id,
            'passcode' => '123456',
        ]);

        $storeManager->employee = $employee;

        $complimentaryItemReason = ComplimentaryItemReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $this->complimentaryItemService->checkForApplicability(
            $this->checkSaleDetailsService,
            $complimentaryItemReason,
            $cartItem,
            collect([]),
            collect([$storeManager])
        );
    }
)->throws(HttpException::class, 'Specified Store Manager: ABC DEF account is inactive. Please contact admin.');

test(
    'checkForApplicability method sets the saleMismatches when director inactive and perform complimentary',
    function (): void {
        $cartItem = [];
        $cartItem['id'] = 1;
        $cartItem['director_passcode'] = '123456';
        $cartItem['director_id'] = 1;
        $cartItem['price'] = 12;
        $cartItem['quantity'] = 5;
        $cartItem['item_discount_amount'] = 60;

        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->checkSaleDetailsService->companyId = 1;

        $employee = seedEmployeeForComplimentary();
        $employee->status = false;

        $director = Director::factory()->make([
            'id' => 1,
            'employee_id' => $employee->id,
            'passcode' => '123456',
        ]);

        $director->employee = $employee;

        $complimentaryItemReason = ComplimentaryItemReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $this->complimentaryItemService->checkForApplicability(
            $this->checkSaleDetailsService,
            $complimentaryItemReason,
            $cartItem,
            collect([$director]),
            collect([])
        );
    }
)->throws(HttpException::class, 'Specified Director: ABC DEF account is inactive. Please contact admin.');

test('getItemDiscountAmount method returns response as expected', function (): void {
    $response = $this->complimentaryItemService->getItemDiscountAmount(100);
    $this->assertEquals(100.00, $response);
});

test(
    'checkNonRegularProduct method thrown an exceptions if complimentary apply on non-regular product.',
    function (): void {
        $this->product->type_id = ProductTypes::SPECIAL_ORDER->value;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $this->complimentaryItemService->checkNonRegularProduct(
            $this->checkSaleDetailsService,
            $this->saleDetails['items'][0]['id']
        );
    }
)->throws(
    HttpException::class,
    'Complimentary is applicable on regular products only. The type of the product with the name Product 1 is Special Order.'
);

test(
    'checkNonRegularProduct method return null when product type is regular product.',
    function (): void {
        $this->product->type_id = ProductTypes::REGULAR_PRODUCT->value;
        $this->checkSaleDetailsService->saleMismatches = collect([]);

        $response = $this->complimentaryItemService->checkNonRegularProduct(
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

        $response = $this->complimentaryItemService->checkNonRegularProduct(
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

        $response = $this->complimentaryItemService->checkNonRegularProduct(
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

        $response = $this->complimentaryItemService->checkStoreManagerAuthorizationCode(
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

        $response = $this->complimentaryItemService->checkStoreManagerAuthorizationCode(
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

    $this->checkSaleDetailsService->saleMismatches = collect([]);

    $this->checkSaleDetailsService->saleData = new SaleData(...$this->saleDetails);
    $this->checkSaleDetailsService->saleData->happened_at = now()->format('Y-m-d H:i:s');

    $this->complimentaryItemService->checkStoreManagerAuthorizationCode($this->checkSaleDetailsService, $cartItem);
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

        $this->complimentaryItemService->checkStoreManagerAuthorizationCode($this->checkSaleDetailsService, $cartItem);
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

    $this->complimentaryItemService->checkStoreManagerAuthorizationCode($this->checkSaleDetailsService, $cartItem);
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
        $this->complimentaryItemService->checkStoreManagerAuthorizationCode($this->checkSaleDetailsService, $cartItem);
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

    $response = $this->complimentaryItemService->checkStoreManagerAuthorizationCode(
        $this->checkSaleDetailsService,
        $cartItem
    );

    $this->assertNull($response);
});

function seedEmployeeForComplimentary(): Employee
{
    return Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
        'first_name' => 'ABC',
        'last_name' => 'DEF',
    ]);
}
