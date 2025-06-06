<?php

declare(strict_types=1);

use App\Domains\EmployeeGroup\DataObjects\SuperAdminEmployeeGroupData;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\EmployeeGroup\Enums\LimitResetTypes;
use App\Domains\EmployeeGroup\Enums\PurchaseLimitTypes;
use App\Http\Controllers\SuperAdmin\EmployeeGroupController;
use App\Models\EmployeeGroup;
use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the list query method of the employee group queries class and returns proper response',
    function (): void {
        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $employeeGroupQueries = $this->mock(EmployeeGroupQueries::class, function ($mock) use (
            $requestParameter
        ): void {
            $mock->shouldReceive('listQueryForSuperAdmin')
            ->once()
            ->with($requestParameter)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $employeeGroupController = new EmployeeGroupController($employeeGroupQueries);

        $response = $employeeGroupController->fetchEmployeeGroups(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls addNew method of the employee group queries class', function (): void {
    $superAdmin = SuperAdmin::factory()->make();
    loginSuperAdmin($superAdmin);

    $employeeGroupRecord = new SuperAdminEmployeeGroupData(
        1,
        'employee group name',
        'employee group code',
        PurchaseLimitTypes::BY_SALE->value,
        1,
        LimitResetTypes::BY_MONTH->value,
        10
    );

    $employeeGroupQueries = $this->mock(EmployeeGroupQueries::class, function ($mock) use (
        $employeeGroupRecord,
        $superAdmin
    ): void {
        $mock->shouldReceive('addForSuperAdmin')
            ->once()
            ->with($employeeGroupRecord, $superAdmin);
    });

    $employeeGroupController = new EmployeeGroupController($employeeGroupQueries);
    $redirectResponse = $employeeGroupController->store($employeeGroupRecord);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Employee group added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/employee-groups', $redirectResponse->getTargetUrl());
});

test('It calls update method of the employee group queries class', function (): void {
    Cache::spy();

    $employeeGroupRecord = new SuperAdminEmployeeGroupData(
        1,
        'name',
        'code',
        PurchaseLimitTypes::BY_SALE->value,
        1,
        LimitResetTypes::BY_MONTH->value,
        10
    );

    $employeeGroupQueries = $this->mock(EmployeeGroupQueries::class, function ($mock) use (
        $employeeGroupRecord,
    ): void {
        $mock->shouldReceive('updateForSuperAdmin')
            ->once()
            ->with($employeeGroupRecord, 1);
    });

    $employeeGroupController = new EmployeeGroupController($employeeGroupQueries);
    $redirectResponse = $employeeGroupController->update($employeeGroupRecord, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Employee group updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/employee-groups', $redirectResponse->getTargetUrl());
});

test('It calls the exportEmployeeGroups method and returns a proper response', function (): void {
    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $employeeGroupQueries = $this->mock(EmployeeGroupQueries::class, function ($mock) use (
        $requestParameter,
    ): void {
        $mock->shouldReceive('getSuperAdminEmployeeGroupsExport')
            ->once()
            ->with($requestParameter)
            ->andReturn(collect(new EmployeeGroup()));
    });

    $employeeGroupController = new EmployeeGroupController($employeeGroupQueries);

    $response = $employeeGroupController->exportEmployeeGroups('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
