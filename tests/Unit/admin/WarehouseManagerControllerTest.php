<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Role\RoleQueries;
use App\Domains\WarehouseManager\DataObjects\ChangePasswordData;
use App\Domains\WarehouseManager\DataObjects\WarehouseManagerData;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Admin\WarehouseManagerController;
use App\Models\WarehouseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the List query method of the warehouse manager queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => null,
            'sort_direction' => 'desc',
            'per_page' => 1,
            'location_ids' => 'null',
        ];

        $warehouseManagerQueries = $this->mock(WarehouseManagerQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 20, 15));
        });

        $warehouseManagerController = new WarehouseManagerController($warehouseManagerQueries);

        $response = $warehouseManagerController->fetchWarehouseManagers(new Request($requestParameter));

        $this->assertEquals(20, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']);
    }
);

test('It calls addNew method of the warehouse manager queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $warehouseManager = seedWarehouseManagerRecord();

    $warehouseManagerRecord = new WarehouseManagerData(...$warehouseManager);

    $this->mock(LocationQueries::class, function ($mock) use ($warehouseManager, $companyId): void {
        $mock->shouldReceive('doAllWarehousesExist')
            ->once()
            ->with($companyId, $warehouseManager['location_ids'])
            ->andReturn(true);
    });

    $warehouseManagerQueries = $this->mock(WarehouseManagerQueries::class, function ($mock) use (
        $warehouseManagerRecord
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($warehouseManagerRecord);
    });

    $warehouseManagerController = new WarehouseManagerController($warehouseManagerQueries);
    $redirectResponse = $warehouseManagerController->store($warehouseManagerRecord);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Warehouse Manager added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/warehouse-managers', $redirectResponse->getTargetUrl());
});

test('It calls get by id of the warehouse manager queries class and return proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'employee_id' => 1,
        'username' => 'warehouse_manager_username1',
    ];

    $roles = [[
        'id' => '1',
        'name' => 'ABC',
        'guard_name' => 'warehouse_manager',
    ]];

    $warehouseManagerQueries = $this->mock(WarehouseManagerQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getByIdWithWarehouses')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new WarehouseManager($requestParameter));
    });

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getFormattedEmployeesOf')
            ->once()
            ->with(1)
            ->andReturn(new Collection([]));
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getWithBasicColumnsOfWarehouse')
            ->once()
            ->with(1)
            ->andReturn(new SupportCollection([]));
    });

    $this->mock(RoleQueries::class, function ($mock) use ($roles): void {
        $mock->shouldReceive('getRoles')
            ->once()
            ->andReturn(collect([$roles]));
    });

    $warehouseManagerController = new WarehouseManagerController($warehouseManagerQueries);
    $response = $warehouseManagerController->edit(1);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has(
            'warehouseManager',
            fn (Assert $warehouseManager): Assert => $warehouseManager
                ->where('username', $requestParameter['username'])
                ->where('employee_id', $requestParameter['employee_id'])
                ->etc()
        )
    );
});

test('It calls update method of the warehouse manager queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $warehouseManager = seedWarehouseManagerRecord();

    $warehouseManagerRecord = new WarehouseManagerData(...$warehouseManager);

    $this->mock(LocationQueries::class, function ($mock) use ($warehouseManager, $companyId): void {
        $mock->shouldReceive('doAllWarehousesExist')
            ->once()
            ->with($companyId, $warehouseManager['location_ids'])
            ->andReturn(true);
    });

    $warehouseManagerQueries = $this->mock(WarehouseManagerQueries::class, function ($mock) use (
        $warehouseManagerRecord,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($warehouseManagerRecord, 1, $companyId);
    });

    $warehouseManagerController = new WarehouseManagerController($warehouseManagerQueries);
    $redirectResponse = $warehouseManagerController->update($warehouseManagerRecord, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Warehouse Manager updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/warehouse-manager', $redirectResponse->getTargetUrl());
});

test('It calls change password method of the warehouse manager queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $warehouseManager = WarehouseManager::factory()->make([
        'employee_id' => 1,
    ]);

    $changePasswordData = new ChangePasswordData('111111');

    $warehouseManagerQueries = $this->mock(WarehouseManagerQueries::class, function ($mock) use (
        $companyId,
        $warehouseManager,
        $changePasswordData
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn($warehouseManager);
        $mock->shouldReceive('changePassword')
            ->once()
            ->with($warehouseManager, $changePasswordData);
    });

    $warehouseManagerController = new WarehouseManagerController($warehouseManagerQueries);
    $redirectResponse = $warehouseManagerController->updatePassword($changePasswordData, 1);
    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Password updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/warehouse-managers', $redirectResponse->getTargetUrl());
});

test('An exception is thrown if warehouse_id does not match the company_id', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $warehouseManager = seedWarehouseManagerRecord();
    $warehouseManager['location_ids'] = [];

    $warehouseManagerRecord = new WarehouseManagerData(...$warehouseManager);

    $this->mock(LocationQueries::class, function ($mock) use ($warehouseManager, $companyId): void {
        $mock->shouldReceive('doAllWarehousesExist')
            ->once()
            ->with($companyId, $warehouseManager['location_ids'])
            ->andReturn(false);
    });

    $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);

    $warehouseManagerController = new WarehouseManagerController($warehouseManagerQueries);
    $warehouseManagerController->store($warehouseManagerRecord);
})->throws(RedirectWithErrorException::class);

test('It calls the exportWarehouseManagers method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'location_ids' => 'null',
    ];

    $warehouseManagerQueries = $this->mock(WarehouseManagerQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getWarehouseManagersExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new WarehouseManager()));
    });

    $warehouseManagerController = new WarehouseManagerController($warehouseManagerQueries);

    $response = $warehouseManagerController->exportWarehouseManagers('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

function seedWarehouseManagerRecord(): array
{
    return [
        'employee_id' => 1,
        'username' => 'warehouse_manager_username1',
        'password' => '123456',
        'location_ids' => [1],
        'role_ids' => [1],
    ];
}
