<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Promoter\DataObjects\ChangePasswordData;
use App\Domains\Promoter\DataObjects\PromoterData;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\StoreManager\PromoterController;
use App\Models\Company;
use App\Models\Promoter;
use App\Models\StoreManager;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the promoter queries class and returns proper response', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'abc',
        'sort_by' => null,
        'sort_direction' => 'desc',
        'per_page' => 1,
        'location_ids' => 'null',
        'group_ids' => null,
        'status' => null,
    ];

    $promoterQueries = $this->mock(PromoterQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 20, 15));
    });

    $promoterController = new PromoterController($promoterQueries);

    $response = $promoterController->fetchPromoters(new Request($requestParameter));

    $this->assertEquals(20, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls addNew method of the promoter queries class', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $promoterRecords = [
        'employee_id' => 1,
        'username' => 'ABC',
        'password' => '123456',
        'monthly_sales_target' => 100,
        'code' => 'test',
        'location_ids' => [1, 2],
        'default_commission_amount_percentage' => 0.0,
        'monthly_target_commission_percentage' => 0.0,
        'group_id' => null,
    ];

    $promoterData = new PromoterData(...$promoterRecords);

    $request = new Request();

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $this->mock(LocationQueries::class, function ($mock) use ($promoterData, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $promoterData->location_ids)
            ->andReturn(true);
    });

    $promoterQueries = $this->mock(PromoterQueries::class, function ($mock) use (
        $promoterData,
        $storeManager
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($promoterData, $storeManager);
        $mock->shouldReceive('doesCodeExist')
            ->once();
    });

    $promoterController = new PromoterController($promoterQueries);
    $redirectResponse = $promoterController->store($promoterData, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Promoter added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('store-manager/promoters', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the promoter queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $requestParameter = [
            'employee_id' => 1,
            'monthly_sales_target' => 1,
        ];

        $promoterData = new Promoter($requestParameter);
        $promoterData->stores = [1, 2];

        $promoterQueries = $this->mock(PromoterQueries::class, function ($mock) use (
            $promoterData,
            $companyId
        ): void {
            $mock->shouldReceive('getByIdWithEmployeeAndLocations')
                ->once()
                ->with(1, $companyId)
                ->andReturn($promoterData);
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

        $this->mock(PromoterGroupQueries::class, function ($mock): void {
            $mock->shouldReceive('getPromoterGroupByCompanyId')
                ->once()
                ->andReturn(new EloquentCollection([]));
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithPromoterCommissionDetails')
                ->once()
                ->with(1)
                ->andReturn(new Company());
        });

        $promoterController = new PromoterController($promoterQueries);
        $response = $promoterController->edit(1);
        $response->rootView('store_manager.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has('promoter', fn (Assert $promoter): Assert => $promoter
            ->where('employee_id', 1)
            ->where('monthly_sales_target', 1)
            ->has('stores', 2))
        );
    }
);

test('It calls the update method of promoter queries class', function (): void {
    $companyId = 1;
    setStoreManagerStoreCompanyIdInSession($companyId);

    $promoterData = new PromoterData(1, 'ABC', '12345', 100, 'test', [1, 2], 0.0, 0.0);

    $this->mock(LocationQueries::class, function ($mock) use ($promoterData, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $promoterData->location_ids)
            ->andReturn(true);
    });

    $promoterQueries = $this->mock(PromoterQueries::class, function ($mock) use ($promoterData, $companyId): void {
        $mock->shouldReceive('doesCodeExist')
            ->once();
        $mock->shouldReceive('update')
            ->once()
            ->with($promoterData, 1, $companyId);
    });

    $promoterController = new PromoterController($promoterQueries);
    $redirectResponse = $promoterController->update($promoterData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Promoter updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('store-manager/promoters', $redirectResponse->getTargetUrl());
});

test('An exception is thrown if store_id does not match the company_id', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $promoterRecords = [
        'employee_id' => 1,
        'username' => 'ABCD',
        'password' => '123456',
        'monthly_sales_target' => 100,
        'code' => 'test',
        'location_ids' => [],
        'default_commission_amount_percentage' => 0.0,
        'monthly_target_commission_percentage' => 0.0,
    ];

    $promoterData = new PromoterData(...$promoterRecords);

    $request = new Request();

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $this->mock(LocationQueries::class, function ($mock) use ($promoterData, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $promoterData->location_ids)
            ->andReturn(false);
    });

    $promoterQueries = resolve(PromoterQueries::class);

    $promoterController = new PromoterController($promoterQueries);
    $promoterController->store($promoterData, $request);
})->throws(RedirectWithErrorException::class);

test('It calls the exportPromoters method and returns a proper response', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'location_ids' => 'null',
        'group_ids' => null,
        'status' => null,
    ];

    $promoterQueries = $this->mock(PromoterQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getPromotersExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Promoter()));
    });

    $promoterController = new PromoterController($promoterQueries);

    $response = $promoterController->exportPromoters('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls change password method of the promoter queries class', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $promoter = Promoter::factory()->make([
        'employee_id' => 1,
    ]);

    $changePasswordData = new ChangePasswordData('111111');

    $promoterQueries = $this->mock(PromoterQueries::class, function ($mock) use (
        $companyId,
        $promoter,
        $changePasswordData
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn($promoter);
        $mock->shouldReceive('changePassword')
            ->once()
            ->with($promoter, $changePasswordData);
    });

    $promoterController = new PromoterController($promoterQueries);

    $redirectResponse = $promoterController->updatePassword($changePasswordData, 1);
    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Password updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('store-manager/promoters', $redirectResponse->getTargetUrl());
});
