<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Cashier\DataObjects\CashierChangePinData;
use App\Domains\Cashier\DataObjects\CashierData;
use App\Domains\CashierGroup\CashierGroupQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\StoreManager\CashierController;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the list query method of the cashier queries class and returns proper response', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'location_ids' => 'null',
    ];

    $cashierQueries = $this->mock(CashierQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $cashierController = new CashierController($cashierQueries);

    $response = $cashierController->fetchCashiers(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls addNew method of the cashier queries class', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $cashierData = cashierDataForStoreManager();

    $cashierRecord = new CashierData(...$cashierData);

    $request = new Request();

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $this->mock(LocationQueries::class, function ($mock) use ($cashierData, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $cashierData['location_ids'])
            ->andReturn(true);
    });

    $cashierQueries = $this->mock(CashierQueries::class, function ($mock) use (
        $cashierRecord,
        $storeManager
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($cashierRecord, $storeManager);
    });

    $cashierController = new CashierController($cashierQueries);
    $redirectResponse = $cashierController->store($cashierRecord, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Cashier added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('store-manager/cashiers', $redirectResponse->getTargetUrl());
});

test('It calls get by id with store method of the cashier queries class and return proper response', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ])->toArray();

    $cashierQueries = $this->mock(CashierQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getByIdWithLocations')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new Cashier($requestParameter));
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
            ->andReturn(new Collection([]));
    });

    $this->mock(CashierGroupQueries::class, function ($mock): void {
        $mock->shouldReceive('getWithBasicColumns')
            ->once()
            ->with(1)
            ->andReturn(new Collection([]));
    });

    $cashierController = new CashierController($cashierQueries);
    $response = $cashierController->edit(1);
    $response->rootView('store_manager.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has(
            'cashier',
            fn (Assert $cashier): Assert => $cashier
                ->where('username', $requestParameter['username'])
                ->where('employee_id', $requestParameter['employee_id'])
                ->where('cashier_group_id', $requestParameter['cashier_group_id'])
                ->etc()
        )
    );
});

test('It calls update method of the cashier queries class', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $cashierData = cashierDataForStoreManager();

    $cashierRecord = new CashierData(...$cashierData);

    $this->mock(LocationQueries::class, function ($mock) use ($cashierData, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $cashierData['location_ids'])
            ->andReturn(true);
    });

    $cashierQueries = $this->mock(CashierQueries::class, function ($mock) use ($cashierRecord, $companyId): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($cashierRecord, 1, $companyId);
    });

    $cashierController = new CashierController($cashierQueries);
    $redirectResponse = $cashierController->update($cashierRecord, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Cashier updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('store-manager/cashiers', $redirectResponse->getTargetUrl());
});

test('It calls change pin query method of the cashier queries class', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ]);

    $cashierChangePinData = new CashierChangePinData('1111');

    $cashierQueries = $this->mock(CashierQueries::class, function ($mock) use (
        $companyId,
        $cashier,
        $cashierChangePinData
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn($cashier);
        $mock->shouldReceive('changePin')
            ->once()
            ->with($cashier, $cashierChangePinData);
    });

    $cashierController = new CashierController($cashierQueries);
    $redirectResponse = $cashierController->updatePin($cashierChangePinData, 1);
    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Pin updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('store-manager/cashiers', $redirectResponse->getTargetUrl());
});

test('An exception is thrown if location_id does not match the company_id', function (): void {
    $companyId = 2;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $cashierData = cashierDataForStoreManager();
    $cashierData['location_ids'] = [0];

    $cashierRecord = new CashierData(...$cashierData);

    $request = new Request();

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $this->mock(LocationQueries::class, function ($mock) use ($cashierData, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $cashierData['location_ids'])
            ->andReturn(false);
    });

    $cashierQueries = resolve(CashierQueries::class);

    $cashierController = new CashierController($cashierQueries);
    $cashierController->store($cashierRecord, $request);
})->throws(RedirectWithErrorException::class);

test('It calls the exportCashiers method and returns a proper response', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'location_ids' => 'null',
    ];

    $cashierQueries = $this->mock(CashierQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getCashiersExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Cashier()));
    });

    $cashierController = new CashierController($cashierQueries);

    $response = $cashierController->exportCashiers('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the getStoreCashiers method and returns a proper response',
    function (): void {
        $companyId = 1;
        $locationId = 1;

        setStoreManagerStoreCompanyIdInSession($companyId);
        setStoreIdInSession($locationId);

        $employee = Employee::factory()->make([
            'company_id' => $companyId,
            'designation_id' => 1,
        ]);

        $cashiers = Cashier::factory()->make([
            'employee_id' => $employee->id,
            'cashier_group_id' => 1,
        ]);

        $cashiers->employee = $employee;
        $cashierQueries = $this->mock(CashierQueries::class, function ($mock) use (
            $companyId,
            $locationId,
            $cashiers
        ): void {
            $mock->shouldReceive('getCashierListOfSelectedLocation')
                ->once()
                ->with($locationId, $companyId)
                ->andReturn(collect([$cashiers]));
        });

        $cashierController = new CashierController($cashierQueries);

        $response = $cashierController->getStoreCashiers();
        $this->assertEquals(collect([
            [
                'id' => $cashiers->id,
                'name' => $employee->getFullName(),
            ],
        ]), $response['cashiers']);
    }
);

function cashierDataForStoreManager(): array
{
    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ])->toArray();

    $cashier['pin'] = '1223';

    $cashier['location_ids'] = [500];

    return $cashier;
}
