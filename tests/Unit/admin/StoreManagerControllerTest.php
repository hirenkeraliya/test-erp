<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Company\CompanyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Role\RoleQueries;
use App\Domains\StoreManager\DataObjects\ChangePasscodeData;
use App\Domains\StoreManager\DataObjects\ChangePasswordData;
use App\Domains\StoreManager\DataObjects\StoreManagerData;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Admin\StoreManagerController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the List query method of the store manager queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => null,
            'sort_direction' => 'desc',
            'per_page' => 1,
            'location_ids' => ['null'],
        ];

        $storeManagerQueries = $this->mock(StoreManagerQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 20, 15));
        });

        $storeManagerController = new StoreManagerController($storeManagerQueries);

        $response = $storeManagerController->fetchStoreManagers(new Request($requestParameter));

        $this->assertEquals(20, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']);
    }
);

test('It calls addNew method of the store manager queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $storeManager = seedStoreManagerRecord();

    $storeManagerRecord = new StoreManagerData(...$storeManager);

    $this->mock(LocationQueries::class, function ($mock) use ($storeManager, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $storeManager['location_ids'])
            ->andReturn(true);
    });

    $this->mock(BrandQueries::class, function ($mock) use ($storeManager, $companyId): void {
        $mock->shouldReceive('doExistsById')
            ->once()
            ->with($companyId, $storeManager['brand_ids'])
            ->andReturn(true);
    });

    $storeManagerQueries = $this->mock(StoreManagerQueries::class, function ($mock) use (
        $storeManagerRecord
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($storeManagerRecord);
    });

    $storeManagerController = new StoreManagerController($storeManagerQueries);
    $redirectResponse = $storeManagerController->store($storeManagerRecord);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Store Manager added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/store-managers', $redirectResponse->getTargetUrl());
});

test('It calls get by id of the store manager queries class and return proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'employee_id' => 1,
        'username' => 'store_manager_username1',
    ];

    $roles = [[
        'id' => '1',
        'name' => 'ABC',
        'guard_name' => 'store_manager',
    ]];

    $this->mock(CompanyQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('getAllowPriceOverrideCartLevel')
            ->once()
            ->with($companyId)
            ->andReturn(true);
        $mock->shouldReceive('getByIdWithBrands')
            ->once()
            ->with(1)
            ->andReturn(new Company());
    });

    $storeManagerQueries = $this->mock(StoreManagerQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getByIdWithStores')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new StoreManager($requestParameter));
    });

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getFormattedEmployeesOf')
            ->once()
            ->with(1)
            ->andReturn(new Collection([]));
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getStoreWithBasicColumns')
            ->once()
            ->with(1)
            ->andReturn(new SupportCollection([]));
    });

    $this->mock(RoleQueries::class, function ($mock) use ($roles): void {
        $mock->shouldReceive('getRoles')
            ->once()
            ->andReturn(collect([$roles]));
    });

    $storeManagerController = new StoreManagerController($storeManagerQueries);
    $response = $storeManagerController->edit(1);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has(
            'storeManager',
            fn (Assert $storeManager): Assert => $storeManager
                ->where('username', $requestParameter['username'])
                ->where('employee_id', $requestParameter['employee_id'])
                ->etc()
        )
    );
});

test('It calls update method of the store manager queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $storeManager = seedStoreManagerRecord();

    $storeManagerRecord = new StoreManagerData(...$storeManager);

    $this->mock(LocationQueries::class, function ($mock) use ($storeManager, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $storeManager['location_ids'])
            ->andReturn(true);
    });

    $this->mock(BrandQueries::class, function ($mock) use ($storeManager, $companyId): void {
        $mock->shouldReceive('doExistsById')
            ->once()
            ->with($companyId, $storeManager['brand_ids'])
            ->andReturn(true);
    });

    $storeManagerQueries = $this->mock(StoreManagerQueries::class, function ($mock) use (
        $storeManagerRecord,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($storeManagerRecord, 1, $companyId);
    });

    $storeManagerController = new StoreManagerController($storeManagerQueries);
    $redirectResponse = $storeManagerController->update($storeManagerRecord, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Store Manager updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/store-manager', $redirectResponse->getTargetUrl());
});

test('It calls change password method of the store manager queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $changePasswordData = new ChangePasswordData('111111');

    $storeManagerQueries = $this->mock(StoreManagerQueries::class, function ($mock) use (
        $companyId,
        $storeManager,
        $changePasswordData
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn($storeManager);
        $mock->shouldReceive('changePassword')
            ->once()
            ->with($storeManager, $changePasswordData);
    });

    $storeManagerController = new StoreManagerController($storeManagerQueries);
    $redirectResponse = $storeManagerController->updatePassword($changePasswordData, 1);
    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Password updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/store-managers', $redirectResponse->getTargetUrl());
});

test('It calls change passcode method of the store manager queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $changePasscodeData = new ChangePasscodeData('111111');

    $storeManagerQueries = $this->mock(StoreManagerQueries::class, function ($mock) use (
        $companyId,
        $storeManager,
        $changePasscodeData
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn($storeManager);
        $mock->shouldReceive('changePasscode')
            ->once()
            ->with($storeManager, $changePasscodeData);
    });

    $storeManagerController = new StoreManagerController($storeManagerQueries);
    $redirectResponse = $storeManagerController->updatePasscode($changePasscodeData, 1);
    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Passcode updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/store-managers', $redirectResponse->getTargetUrl());
});

test('An exception is thrown if store_id does not match the company_id', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $storeManager = seedStoreManagerRecord();
    $storeManager['location_ids'] = [];
    $storeManager['brand_ids'] = [];

    $storeManagerRecord = new StoreManagerData(...$storeManager);

    $this->mock(LocationQueries::class, function ($mock) use ($storeManager, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $storeManager['location_ids'])
            ->andReturn(false);
    });

    $storeManagerQueries = resolve(StoreManagerQueries::class);

    $storeManagerController = new StoreManagerController($storeManagerQueries);
    $storeManagerController->store($storeManagerRecord);
})->throws(RedirectWithErrorException::class);

test(
    'It calls the getStoresStoreManagers queries method of store manager query and get store manager list',
    function (): void {
        $locationId = 1;

        $employee = Employee::factory()->make([
            'id' => 1,
            'first_name' => 'test 1',
            'last_name' => 'last',
            'company_id' => 1,
            'designation_id' => 1,
        ]);

        $request = new Request([
            'location_ids' => [$locationId],
        ]);

        $storeManager = StoreManager::factory()->make([
            'employee_id' => $employee->id,
        ]);

        $storeManager->employee = $employee;

        $storeManagerData = [
            'id' => $employee->id,
            'name' => $employee->first_name . ' ' . $employee->last_name,
        ];

        $storeManagerQueries = $this->mock(StoreManagerQueries::class, function ($mock) use (
            $locationId,
            $storeManager
        ): void {
            $mock->shouldReceive('getByStoreIdsWithEmployee')
                ->once()
                ->with([$locationId])
                ->andReturn(collect([$storeManager]));
        });
        $storeManagerController = new StoreManagerController($storeManagerQueries);
        $response = $storeManagerController->getStoresStoreManagers($request);
        $this->assertEquals(collect([$storeManagerData]), $response['store_managers']);
    }
);

test('It calls the exportStoreManagers method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'location_ids' => 'null',
    ];

    $storeManagerQueries = $this->mock(StoreManagerQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getStoreManagersExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new StoreManager()));
    });

    $storeManagerController = new StoreManagerController($storeManagerQueries);

    $response = $storeManagerController->exportStoreManagers('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the exportBulkUpdateStoreManagers method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $storeManagerQueries = $this->mock(StoreManagerQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('getStoreManagersForBulkUpdate')
            ->once()
            ->with($companyId)
            ->andReturn(collect(new StoreManager()));
    });

    $storeManagerController = new StoreManagerController($storeManagerQueries);

    $response = $storeManagerController->exportBulkUpdateStoreManagers();

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

function seedStoreManagerRecord(): array
{
    return [
        'employee_id' => 1,
        'username' => 'store_manager_username1',
        'password' => '123456',
        'passcode' => '123456',
        'price_override_type' => PriceOverrideTypes::PERCENTAGE->value,
        'price_override_limit_percentage_for_item' => 10.00,
        'price_override_limit_percentage_for_cart' => 10.00,
        'can_manage_wholesale' => false,
        'location_ids' => [1],
        'brand_ids' => [1],
        'role_ids' => [1],
    ];
}
