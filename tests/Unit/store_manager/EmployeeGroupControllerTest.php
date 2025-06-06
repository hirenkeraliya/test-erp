<?php

declare(strict_types=1);

use App\Domains\EmployeeGroup\DataObjects\EmployeeGroupData;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\EmployeeGroup\Enums\LimitResetTypes;
use App\Domains\EmployeeGroup\Enums\PurchaseLimitTypes;
use App\Http\Controllers\StoreManager\EmployeeGroupController;
use App\Models\EmployeeGroup;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
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

        $companyId = 1;

        setStoreManagerStoreCompanyIdInSession($companyId);

        $employeeGroupQueries = $this->mock(EmployeeGroupQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $employeeGroupController = new EmployeeGroupController($employeeGroupQueries);

        $response = $employeeGroupController->fetchEmployeeGroups(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls addNew method of the employee group queries class', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $employeeGroupRecord = new EmployeeGroupData(
        'employee group name',
        'employee group code',
        PurchaseLimitTypes::BY_SALE->value,
        1,
        LimitResetTypes::BY_MONTH->value,
        10
    );

    $request = new Request();

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $employeeGroupQueries = $this->mock(EmployeeGroupQueries::class, function ($mock) use (
        $employeeGroupRecord,
        $companyId,
        $storeManager
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($employeeGroupRecord, $companyId, $storeManager);
    });

    $employeeGroupController = new EmployeeGroupController($employeeGroupQueries);
    $redirectResponse = $employeeGroupController->store($employeeGroupRecord, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Employee group added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('store-manager/employee-groups', $redirectResponse->getTargetUrl());
});

test('It calls get by id method of the employee group queries class and returns proper response', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = EmployeeGroup::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    $employeeGroupQueries = $this->mock(EmployeeGroupQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new EmployeeGroup($requestParameter));
    });

    $employeeGroupController = new EmployeeGroupController($employeeGroupQueries);
    $response = $employeeGroupController->edit(1);
    $response->rootView('store_manager.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has(
            'employeeGroup',
            fn (Assert $employeeGroup): Assert => $employeeGroup
                ->where('name', $requestParameter['name'])
                ->etc()
        )
    );
});

test('It calls update method of the employee group queries class', function (): void {
    Cache::spy();

    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $employeeGroupRecord = new EmployeeGroupData(
        'name',
        'code',
        PurchaseLimitTypes::BY_SALE->value,
        1,
        LimitResetTypes::BY_MONTH->value,
        10
    );

    $employeeGroupQueries = $this->mock(EmployeeGroupQueries::class, function ($mock) use (
        $employeeGroupRecord,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($employeeGroupRecord, 1, $companyId);
    });

    $employeeGroupController = new EmployeeGroupController($employeeGroupQueries);
    $redirectResponse = $employeeGroupController->update($employeeGroupRecord, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Employee group updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('store-manager/employee-groups', $redirectResponse->getTargetUrl());
});

test('It calls the exportEmployeeGroups method and returns a proper response', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $employeeGroupQueries = $this->mock(EmployeeGroupQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getEmployeeGroupsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new EmployeeGroup()));
    });

    $employeeGroupController = new EmployeeGroupController($employeeGroupQueries);

    $response = $employeeGroupController->exportEmployeeGroups('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
