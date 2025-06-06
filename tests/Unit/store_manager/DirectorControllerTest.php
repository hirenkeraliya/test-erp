<?php

declare(strict_types=1);

use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Company\CompanyQueries;
use App\Domains\Director\DataObjects\ChangePasscodeData;
use App\Domains\Director\DataObjects\DirectorData;
use App\Domains\Director\DirectorQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\StoreManager\DirectorController;
use App\Models\Director;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the director queries class and returns proper response', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'abc',
        'sort_by' => null,
        'sort_direction' => 'desc',
        'per_page' => 1,
        'location_ids' => 'null',
    ];

    $directorQueries = $this->mock(DirectorQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 20, 15));
    });

    $directorController = new DirectorController($directorQueries);

    $response = $directorController->fetchDirectors(new Request($requestParameter));

    $this->assertEquals(20, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls addNew method of the director queries class', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $director = seedDirectorRecordForStoreManager();

    $directorRecord = new DirectorData(...$director);

    $request = new Request();

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $this->mock(LocationQueries::class, function ($mock) use ($director, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $director['location_ids'])
            ->andReturn(true);
    });

    $directorQueries = $this->mock(DirectorQueries::class, function ($mock) use (
        $directorRecord,
        $storeManager
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($directorRecord, $storeManager);
    });

    $directorController = new DirectorController($directorQueries);
    $redirectResponse = $directorController->store($directorRecord, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Director added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('store-manager/directors', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the director queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setStoreManagerStoreCompanyIdInSession($companyId);

        $requestParameter = [
            'employee_id' => 1,
        ];

        $directorData = new Director($requestParameter);
        $directorData->stores = [1, 2];

        $this->mock(CompanyQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getAllowPriceOverrideCartLevel')
                ->once()
                ->with($companyId)
                ->andReturn(true);
        });

        $directorQueries = $this->mock(DirectorQueries::class, function ($mock) use (
            $directorData,
            $companyId
        ): void {
            $mock->shouldReceive('getByIdWithEmployeeAndLocations')
            ->once()
            ->with(1, $companyId)
            ->andReturn($directorData);
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

        $directorController = new DirectorController($directorQueries);
        $response = $directorController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has('director', fn (Assert $director): Assert => $director->where('employee_id', 1)->has('stores', 2))
        );
    }
);

test('It calls update method of the director queries class', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $director = seedDirectorRecordForStoreManager();

    $directorRecord = new DirectorData(...$director);

    $this->mock(LocationQueries::class, function ($mock) use ($director, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $director['location_ids'])
            ->andReturn(true);
    });

    $directorQueries = $this->mock(DirectorQueries::class, function ($mock) use (
        $directorRecord,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($directorRecord, 1, $companyId);
    });

    $directorController = new DirectorController($directorQueries);
    $redirectResponse = $directorController->update($directorRecord, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('The director was successfully updated.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('store-manager/director', $redirectResponse->getTargetUrl());
});

test('It calls change passcode method of the director queries class', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $director = Director::factory()->make([
        'employee_id' => 1,
    ]);

    $changePasscodeData = new ChangePasscodeData('111111');

    $directorQueries = $this->mock(DirectorQueries::class, function ($mock) use (
        $companyId,
        $director,
        $changePasscodeData
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn($director);
        $mock->shouldReceive('changePasscode')
            ->once()
            ->with($director, $changePasscodeData);
    });

    $directorController = new DirectorController($directorQueries);
    $redirectResponse = $directorController->updatePasscode($changePasscodeData, 1);
    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Passcode updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('store-manager/directors', $redirectResponse->getTargetUrl());
});

test('An exception is thrown if store_id does not match the company_id', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $director = seedDirectorRecordForStoreManager();
    $director['location_ids'] = [];

    $request = new Request();

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $directorRecord = new DirectorData(...$director);

    $this->mock(LocationQueries::class, function ($mock) use ($director, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $director['location_ids'])
            ->andReturn(false);
    });

    $directorQueries = resolve(DirectorQueries::class);

    $directorController = new DirectorController($directorQueries);
    $directorController->store($directorRecord, $request);
})->throws(RedirectWithErrorException::class);

test('It calls the exportDirectors method and returns a proper response', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'location_ids' => 'null',
    ];

    $directorQueries = $this->mock(DirectorQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getDirectorsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Director()));
    });

    $directorController = new DirectorController($directorQueries);

    $response = $directorController->exportDirectors('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

function seedDirectorRecordForStoreManager(): array
{
    return [
        'employee_id' => 1,
        'passcode' => '123456',
        'price_override_type' => PriceOverrideTypes::PERCENTAGE->value,
        'price_override_limit_percentage_for_item' => 10.00,
        'price_override_limit_percentage_for_cart' => 10.00,
        'location_ids' => [1],
    ];
}
