<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountDataForPos;
use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Domains\HappyHourDiscount\Services\HappyHourDiscountCheckService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCode\Enum\StoreManagerAuthorizationCodeStatuses;
use App\Domains\StoreManagerAuthorizationCode\StoreManagerAuthorizationCodeQueries;
use App\Domains\Style\StyleQueries;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StoreManager;
use App\Models\StoreManagerAuthorizationCode;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->company = Company::factory()->make([
        'id' => 1,
        'allow_happy_hour_discount' => 1,
        'default_country_id' => 1,
    ]);

    $this->location = Location::factory()->make([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->counter = Counter::factory()->make([
        'location_id' => $this->location->id,
    ]);

    $this->employee = Employee::factory()->make([
        'company_id' => $this->company->id,
        'designation_id' => 1,
    ]);

    $this->cashier = Cashier::factory()->make([
        'employee_id' => $this->employee->id,
        'cashier_group_id' => 1,
    ]);

    $this->cashier->employee = $this->employee;

    $this->counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $this->counter->id,
        'cashier_id' => $this->cashier->id,
        'opened_by_pos_at' => now()->format('Y-m-d H:i:s'),
    ]);

    $this->counterUpdate->counter = $this->counter;
    $this->counterUpdate->cashier = $this->cashier;

    $this->cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => $this->counterUpdate->id,
    ]);

    $this->cashier->counterUpdate = $this->counterUpdate;

    $this->storeManager = StoreManager::factory()->make([
        'employee_id' => $this->employee->id,
    ]);

    $this->storeManager->employee = $this->employee;
});

function getHappyHourDiscountData(): HappyHourDiscountDataForPos
{
    $happyHourDiscountData = [
        'offline_id' => '123456',
        'product_type_id' => ProductTypes::BRAND->value,
        'name' => 'abc',
        'new_price' => '500',
        'start_date' => now()->addMinute()->format('Y-m-d H:i:s'),
        'end_date' => now()->addMinute()->format('Y-m-d H:i:s'),
        'happened_at' => now()->addMinute()->format('Y-m-d H:i:s'),
        'store_manager_id' => 1,
        'store_manager_passcode' => '123456',
        'director_id' => null,
        'director_passcode' => null,
        'brand_ids' => [1],
    ];

    return new HappyHourDiscountDataForPos(...$happyHourDiscountData);
}

test(
    'checkHappenedAtDate method throw exception when happened_at date can be past compared to counter opened_at date',
    function (): void {
        $happyHourDiscountDataForPos = getHappyHourDiscountData();

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->andReturn(true);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithEmployee')
                ->once()
                ->andReturn($this->storeManager);
        });

        $this->mock(BrandQueries::class, function ($mock): void {
            $mock->shouldReceive('doExistsById')
                ->once()
                ->andReturn(true);
        });

        $this->counterUpdate->opened_by_pos_at = now()->addYear();

        $mock = $this->createPartialMock(HappyHourDiscountCheckService::class, ['checkStoreManagerAuthorizationCode']);

        $mock->expects($this->once())
            ->method('checkStoreManagerAuthorizationCode');

        $mock->happyHourDiscountMismatches = collect([]);
        $mock->validateHappyHourData($happyHourDiscountDataForPos, 1, $this->cashier);
    }
)->throws(HttpException::class);

test(
    'checkProductTypeIds method throw exception when brands are not available in this counter',
    function (): void {
        $happyHourDiscountDataForPos = getHappyHourDiscountData();

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->andReturn(true);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithEmployee')
                ->once()
                ->andReturn($this->storeManager);
        });

        $this->mock(BrandQueries::class, function ($mock): void {
            $mock->shouldReceive('doExistsById')
                ->once()
                ->andReturn(false);
        });

        $mock = $this->createPartialMock(HappyHourDiscountCheckService::class, ['checkStoreManagerAuthorizationCode']);

        $mock->expects($this->once())
            ->method('checkStoreManagerAuthorizationCode');

        $mock->happyHourDiscountMismatches = collect([]);
        $mock->validateHappyHourData($happyHourDiscountDataForPos, 1, $this->cashier);
    }
)->throws(HttpException::class);

test(
    'checkProductTypeIds method throw exception when categories are not available in this counter',
    function (): void {
        $happyHourDiscountDataForPos = getHappyHourDiscountData();
        $happyHourDiscountDataForPos->product_type_id = ProductTypes::CATEGORY->value;
        $happyHourDiscountDataForPos->category_ids = [1];

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->andReturn(true);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithEmployee')
                ->once()
                ->andReturn($this->storeManager);
        });

        $this->mock(CategoryQueries::class, function ($mock): void {
            $mock->shouldReceive('doAllCategoriesExist')
                ->once()
                ->andReturn(false);
        });

        $mock = $this->createPartialMock(HappyHourDiscountCheckService::class, ['checkStoreManagerAuthorizationCode']);

        $mock->expects($this->once())
            ->method('checkStoreManagerAuthorizationCode');

        $mock->happyHourDiscountMismatches = collect([]);
        $mock->validateHappyHourData($happyHourDiscountDataForPos, 1, $this->cashier);
    }
)->throws(HttpException::class);

test(
    'checkProductTypeIds method throw exception when styles are not available in this counter',
    function (): void {
        $happyHourDiscountDataForPos = getHappyHourDiscountData();
        $happyHourDiscountDataForPos->product_type_id = ProductTypes::STYLE->value;
        $happyHourDiscountDataForPos->style_ids = [1];

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->andReturn(true);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithEmployee')
                ->once()
                ->andReturn($this->storeManager);
        });

        $this->mock(StyleQueries::class, function ($mock): void {
            $mock->shouldReceive('doAllStylesExist')
                ->once()
                ->andReturn(false);
        });

        $mock = $this->createPartialMock(HappyHourDiscountCheckService::class, ['checkStoreManagerAuthorizationCode']);

        $mock->expects($this->once())
            ->method('checkStoreManagerAuthorizationCode');

        $mock->happyHourDiscountMismatches = collect([]);
        $mock->validateHappyHourData($happyHourDiscountDataForPos, 1, $this->cashier);
    }
)->throws(HttpException::class);

test(
    'checkProductTypeIds method throw exception when departments are not available in this counter',
    function (): void {
        $happyHourDiscountDataForPos = getHappyHourDiscountData();
        $happyHourDiscountDataForPos->product_type_id = ProductTypes::DEPARTMENTS->value;
        $happyHourDiscountDataForPos->department_ids = [1];

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->andReturn(true);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithEmployee')
                ->once()
                ->andReturn($this->storeManager);
        });

        $this->mock(DepartmentQueries::class, function ($mock): void {
            $mock->shouldReceive('doAllDepartmentExist')
                ->once()
                ->andReturn(false);
        });

        $mock = $this->createPartialMock(HappyHourDiscountCheckService::class, ['checkStoreManagerAuthorizationCode']);

        $mock->expects($this->once())
            ->method('checkStoreManagerAuthorizationCode');

        $mock->happyHourDiscountMismatches = collect([]);
        $mock->validateHappyHourData($happyHourDiscountDataForPos, 1, $this->cashier);
    }
)->throws(HttpException::class);

test(
    'checkAuthorized method throw exception when store_manager_id,store_manager_passcode,director_id,director_passcode id are present',
    function (): void {
        $happyHourDiscountDataForPos = getHappyHourDiscountData();
        $happyHourDiscountDataForPos->director_id = 1;
        $happyHourDiscountDataForPos->director_passcode = '123456';

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->andReturn(true);
        });

        $happyHourDiscountCheckService = new HappyHourDiscountCheckService();
        $happyHourDiscountCheckService->happyHourDiscountMismatches = collect([]);
        $happyHourDiscountCheckService->validateHappyHourData($happyHourDiscountDataForPos, 1, $this->cashier);
    }
)->throws(HttpException::class);

test(
    'checkAuthorized method throw exception when store_manager_id,store_manager_passcode,director_id,director_passcode id are not present',
    function (): void {
        $happyHourDiscountDataForPos = getHappyHourDiscountData();
        $happyHourDiscountDataForPos->store_manager_id = null;
        $happyHourDiscountDataForPos->store_manager_passcode = null;

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->andReturn(true);
        });

        $happyHourDiscountCheckService = new HappyHourDiscountCheckService();
        $happyHourDiscountCheckService->happyHourDiscountMismatches = collect([]);
        $happyHourDiscountCheckService->validateHappyHourData($happyHourDiscountDataForPos, 1, $this->cashier);
    }
)->throws(HttpException::class);

test(
    'checkAuthorized method throw exception when store_manager_id is present but store_manager_passcode,director_id,director_passcode id are not present',
    function (): void {
        $happyHourDiscountDataForPos = getHappyHourDiscountData();
        $happyHourDiscountDataForPos->store_manager_id = 1;
        $happyHourDiscountDataForPos->store_manager_passcode = null;

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->andReturn(true);
        });

        $happyHourDiscountCheckService = new HappyHourDiscountCheckService();
        $happyHourDiscountCheckService->happyHourDiscountMismatches = collect([]);
        $happyHourDiscountCheckService->validateHappyHourData($happyHourDiscountDataForPos, 1, $this->cashier);
    }
)->throws(HttpException::class);

test(
    'checkAuthorized method throw exception when store_manager_passcode is present but store_manager_id,director_id,director_passcode id are not present',
    function (): void {
        $happyHourDiscountDataForPos = getHappyHourDiscountData();
        $happyHourDiscountDataForPos->store_manager_id = null;
        $happyHourDiscountDataForPos->store_manager_passcode = '123456';

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->andReturn(true);
        });

        $happyHourDiscountCheckService = new HappyHourDiscountCheckService();
        $happyHourDiscountCheckService->happyHourDiscountMismatches = collect([]);
        $happyHourDiscountCheckService->validateHappyHourData($happyHourDiscountDataForPos, 1, $this->cashier);
    }
)->throws(HttpException::class);

test(
    'checkAuthorized method throw exception when director_id is present but director_passcode,store_manager_id, store_manager_passcode are not present',
    function (): void {
        $happyHourDiscountDataForPos = getHappyHourDiscountData();
        $happyHourDiscountDataForPos->store_manager_id = null;
        $happyHourDiscountDataForPos->store_manager_passcode = null;
        $happyHourDiscountDataForPos->director_id = 1;

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->andReturn(true);
        });

        $happyHourDiscountCheckService = new HappyHourDiscountCheckService();
        $happyHourDiscountCheckService->happyHourDiscountMismatches = collect([]);
        $happyHourDiscountCheckService->validateHappyHourData($happyHourDiscountDataForPos, 1, $this->cashier);
    }
)->throws(HttpException::class);

test(
    'checkAuthorized method throw exception when director_passcode is present but director_id,store_manager_id, store_manager_passcode are not present',
    function (): void {
        $happyHourDiscountDataForPos = getHappyHourDiscountData();
        $happyHourDiscountDataForPos->store_manager_id = null;
        $happyHourDiscountDataForPos->store_manager_passcode = null;
        $happyHourDiscountDataForPos->director_passcode = '123456';

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->andReturn(true);
        });

        $happyHourDiscountCheckService = new HappyHourDiscountCheckService();
        $happyHourDiscountCheckService->happyHourDiscountMismatches = collect([]);
        $happyHourDiscountCheckService->validateHappyHourData($happyHourDiscountDataForPos, 1, $this->cashier);
    }
)->throws(HttpException::class);

test(
    'checkAuthorized method throw exception when Store Manager does not correspond with our records',
    function (): void {
        $happyHourDiscountDataForPos = getHappyHourDiscountData();

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->andReturn(true);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithEmployee')
                ->once();
        });

        $happyHourDiscountCheckService = new HappyHourDiscountCheckService();
        $happyHourDiscountCheckService->happyHourDiscountMismatches = collect([]);
        $happyHourDiscountCheckService->validateHappyHourData($happyHourDiscountDataForPos, 1, $this->cashier);
    }
)->throws(HttpException::class);

test(
    'checkAuthorized method throw exception when employee is inactive',
    function (): void {
        $happyHourDiscountDataForPos = getHappyHourDiscountData();

        $this->employee->status = 0;

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->andReturn(true);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithEmployee')
                ->once()
                ->andReturn($this->storeManager);
        });

        $happyHourDiscountCheckService = new HappyHourDiscountCheckService();
        $happyHourDiscountCheckService->happyHourDiscountMismatches = collect([]);
        $happyHourDiscountCheckService->validateHappyHourData($happyHourDiscountDataForPos, 1, $this->cashier);
    }
)->throws(HttpException::class);

test(
    'checkAuthorized method throw exception when passcode is not correct',
    function (): void {
        $happyHourDiscountDataForPos = getHappyHourDiscountData();

        $this->storeManager->passcode = 'sfafsfafassfasfa';

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->andReturn(true);
        });

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithEmployee')
                ->once()
                ->andReturn($this->storeManager);
        });

        $happyHourDiscountCheckService = new HappyHourDiscountCheckService();
        $happyHourDiscountCheckService->happyHourDiscountMismatches = collect([]);
        $happyHourDiscountCheckService->validateHappyHourData($happyHourDiscountDataForPos, 1, $this->cashier);
    }
)->throws(HttpException::class);

test(
    'checkAllowHappyHourDiscount method throw exception when company not allow happy hour discount.',
    function (): void {
        $happyHourDiscountDataForPos = getHappyHourDiscountData();

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllowHappyHourDiscount')
                ->once()
                ->andReturn(false);
        });

        $happyHourDiscountCheckService = new HappyHourDiscountCheckService();
        $happyHourDiscountCheckService->happyHourDiscountMismatches = collect([]);
        $happyHourDiscountCheckService->validateHappyHourData($happyHourDiscountDataForPos, 1, $this->cashier);
    }
)->throws(HttpException::class);

test(
    'checkStoreManagerAuthorizationCode method return null when store_manager_authorization_code not set',
    function (): void {
        $happyHourDiscountDataForPos = getHappyHourDiscountData();

        $happyHourDiscountCheckService = new HappyHourDiscountCheckService();
        $happyHourDiscountCheckService->happyHourDiscountMismatches = collect([]);
        $response = $happyHourDiscountCheckService->checkStoreManagerAuthorizationCode($happyHourDiscountDataForPos);
        $this->assertNull($response);
    }
);

test('checkStoreManagerAuthorizationCode method throw exception when code not match in database', function (): void {
    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn(null);
    });

    $happyHourDiscountDataForPos = getHappyHourDiscountData();
    $happyHourDiscountDataForPos->store_manager_authorization_code = '1234';

    $happyHourDiscountCheckService = new HappyHourDiscountCheckService();
    $happyHourDiscountCheckService->happyHourDiscountMismatches = collect([]);
    $happyHourDiscountCheckService->checkStoreManagerAuthorizationCode($happyHourDiscountDataForPos);
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

        $happyHourDiscountDataForPos = getHappyHourDiscountData();
        $happyHourDiscountDataForPos->store_manager_authorization_code = '1234';

        $happyHourDiscountCheckService = new HappyHourDiscountCheckService();
        $happyHourDiscountCheckService->happyHourDiscountMismatches = collect([]);
        $happyHourDiscountCheckService->checkStoreManagerAuthorizationCode($happyHourDiscountDataForPos);
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

    $happyHourDiscountDataForPos = getHappyHourDiscountData();
    $happyHourDiscountDataForPos->store_manager_authorization_code = '1234';

    $happyHourDiscountCheckService = new HappyHourDiscountCheckService();
    $happyHourDiscountCheckService->happyHourDiscountMismatches = collect([]);
    $happyHourDiscountCheckService->checkStoreManagerAuthorizationCode($happyHourDiscountDataForPos);
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

        $happyHourDiscountDataForPos = getHappyHourDiscountData();
        $happyHourDiscountDataForPos->store_manager_authorization_code = '1234';

        $happyHourDiscountCheckService = new HappyHourDiscountCheckService();
        $happyHourDiscountCheckService->happyHourDiscountMismatches = collect([]);
        $happyHourDiscountCheckService->checkStoreManagerAuthorizationCode($happyHourDiscountDataForPos);
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

    $happyHourDiscountDataForPos = getHappyHourDiscountData();
    $happyHourDiscountDataForPos->store_manager_authorization_code = '1234';

    $happyHourDiscountCheckService = new HappyHourDiscountCheckService();
    $happyHourDiscountCheckService->happyHourDiscountMismatches = collect([]);
    $response = $happyHourDiscountCheckService->checkStoreManagerAuthorizationCode($happyHourDiscountDataForPos);

    $this->assertNull($response);
});
