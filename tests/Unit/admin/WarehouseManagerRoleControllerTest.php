<?php

declare(strict_types=1);

use App\Domains\Role\RoleQueries;
use App\Http\Controllers\Admin\WarehouseManagerRoleController;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('It calls the getPaginatedRoles method of the role queries class and returns proper response', function (): void {
    $requestParameter = [
        'search_text' => null,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'per_page' => '10',
    ];

    $roleQueries = $this->mock(RoleQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getPaginatedRoles')
            ->once()
            ->with($requestParameter, 'warehouse_manager')
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $warehouseManagerRoleController = new WarehouseManagerRoleController($roleQueries);

    $response = $warehouseManagerRoleController->fetch(new Request($requestParameter));
    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls the store method of the role queries class and returns proper response', function (): void {
    $requestParameter = [
        'name' => 'role',
        'permissions' => ['viewer'],
    ];

    $roleQueries = $this->mock(RoleQueries::class, function ($mock): void {
        $mock->shouldReceive('store')
            ->once();
    });

    $request = $this->mock(Request::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('validate')
            ->once()
            ->andReturn($requestParameter);

        $mock->shouldReceive('route');
    });

    $warehouseManagerRoleController = new WarehouseManagerRoleController($roleQueries);

    $response = $warehouseManagerRoleController->store($request);
    $this->assertEquals('Roles & Permissions added successfully.', $response->getSession()->all()['success']);
});

test('It calls the update method of the role queries class and returns proper response', function (): void {
    $requestParameter = [
        'name' => 'role',
        'permissions' => ['viewer'],
    ];

    $roleQueries = $this->mock(RoleQueries::class, function ($mock): void {
        $mock->shouldReceive('update')
            ->once();
    });

    $request = $this->mock(Request::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('validate')
            ->once()
            ->andReturn($requestParameter);

        $mock->shouldReceive('route');
    });

    $warehouseManagerRoleController = new WarehouseManagerRoleController($roleQueries);

    $response = $warehouseManagerRoleController->update($request, 1);
    $this->assertEquals('Roles & Permissions updated successfully.', $response->getSession()->all()['success']);
});
