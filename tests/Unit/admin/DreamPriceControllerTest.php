<?php

declare(strict_types=1);

use App\Domains\DreamPrice\DataObjects\DreamPriceData;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\DreamPriceProduct\DreamPriceProductQueries;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Admin\DreamPriceController;
use App\Models\Admin;
use App\Models\DreamPrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the list query method of the dream price queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'status' => true,
    ];

    $dreamPriceQueries = $this->mock(DreamPriceQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $dreamPriceController = new DreamPriceController($dreamPriceQueries);

    $response = $dreamPriceController->fetchDreamPrices(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    expect($response['data'])->toBeInstanceOf(AnonymousResourceCollection::class);
});

test('It calls addNew method of the dream price queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $dreamPrice = seedDreamPriceRecord();

    $dreamPriceRecord = new DreamPriceData(...$dreamPrice);

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $this->mock(LocationQueries::class, function ($mock) use ($dreamPrice, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $dreamPrice['location_ids'])
            ->andReturn(true);
    });

    $this->mock(SaleChannelQueries::class, function ($mock) use ($dreamPrice, $companyId): void {
        $mock->shouldReceive('doAllSaleChannelExist')
            ->once()
            ->with($companyId, $dreamPrice['sale_channel_ids'])
            ->andReturn(true);
    });

    $dreamPriceQueries = $this->mock(DreamPriceQueries::class, function ($mock) use (
        $dreamPriceRecord,
        $companyId,
        $admin
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($dreamPriceRecord, $companyId, $admin);
    });

    $dreamPriceController = new DreamPriceController($dreamPriceQueries);
    $redirectResponse = $dreamPriceController->store($dreamPriceRecord, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Dream price added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/dream-prices', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the dream price queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'name' => 'XYZW',
        ];

        $dreamPriceData = new DreamPrice($requestParameter);
        $dreamPriceData->locations = [1, 2];

        $dreamPriceQueries = $this->mock(DreamPriceQueries::class, function ($mock) use (
            $dreamPriceData,
            $companyId
        ): void {
            $mock->shouldReceive('getByIdWithLocations')
            ->once()
            ->with(1, $companyId)
            ->andReturn($dreamPriceData);
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getStoreWithBasicColumns')
            ->once()
            ->with(1)
            ->andReturn(new SupportCollection([]));
        });

        $this->mock(MemberGroupQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(collect([]));
        });

        $this->mock(EmployeeGroupQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(collect([]));
        });

        $this->mock(SaleChannelQueries::class, function ($mock): void {
            $mock->shouldReceive('getAllByCompanyId')
            ->once()
            ->andReturn(collect([]));
        });

        $dreamPriceController = new DreamPriceController($dreamPriceQueries);
        $response = $dreamPriceController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has(
            'dreamPrice',
            fn (Assert $dreamPrice): Assert => $dreamPrice->where('name', 'XYZW')->has('locations', 2)
        )
        );
    }
);

test('It calls update method of the dream price queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $dreamPrice = seedDreamPriceRecord();

    $dreamPriceRecord = new DreamPriceData(...$dreamPrice);

    $this->mock(LocationQueries::class, function ($mock) use ($dreamPrice, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $dreamPrice['location_ids'])
            ->andReturn(true);
    });

    $this->mock(SaleChannelQueries::class, function ($mock) use ($dreamPrice, $companyId): void {
        $mock->shouldReceive('doAllSaleChannelExist')
            ->once()
            ->with($companyId, $dreamPrice['sale_channel_ids'])
            ->andReturn(true);
    });

    $dreamPriceQueries = $this->mock(DreamPriceQueries::class, function ($mock) use (
        $dreamPriceRecord,
        $companyId
    ): void {
        $mock->shouldReceive('getById')
          ->once()
          ->andReturn(new DreamPrice([]));
        $mock->shouldReceive('update')
            ->once()
            ->with($dreamPriceRecord, 1, $companyId);
    });

    $dreamPriceController = new DreamPriceController($dreamPriceQueries);
    $redirectResponse = $dreamPriceController->update($dreamPriceRecord, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Dream price updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/dream-price', $redirectResponse->getTargetUrl());
});

test('An exception is thrown if store_id does not match the company_id', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $dreamPrice = seedDreamPriceRecord();
    $dreamPrice['location_ids'] = [];

    $dreamPriceRecord = new DreamPriceData(...$dreamPrice);

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $this->mock(LocationQueries::class, function ($mock) use ($dreamPrice, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $dreamPrice['location_ids'])
            ->andReturn(false);
    });

    $dreamPriceQueries = resolve(DreamPriceQueries::class);

    $dreamPriceController = new DreamPriceController($dreamPriceQueries);
    $dreamPriceController->store($dreamPriceRecord, $request);
})->throws(RedirectWithErrorException::class);

test(
    'It calls the getDreamPriceProduct method of the  queries class and returns proper response',
    function (): void {
        setCompanyIdInSession();

        $this->mock(DreamPriceProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithProduct')
                ->once()
                ->with(1)
                ->andReturn(collect([]));
        });

        $dreamPriceQueries = resolve(DreamPriceQueries::class);

        $dreamPriceController = new DreamPriceController($dreamPriceQueries);

        $response = $dreamPriceController->getDreamPriceProduct(1);
        expect($response['dream_price_products'])->toBeInstanceOf(AnonymousResourceCollection::class);
    }
);

test('It calls the exportDreamPriceProducts method and returns a proper response', function (): void {
    setCompanyIdInSession();

    $this->mock(DreamPriceProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIdWithProduct')
            ->once()
            ->with(1)
            ->andReturn(collect([]));
    });

    $dreamPriceQueries = resolve(DreamPriceQueries::class);

    $dreamPriceController = new DreamPriceController($dreamPriceQueries);

    $response = $dreamPriceController->exportDreamPriceProducts(1, 'filename.csv');

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the exportDreamPrices method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'status' => true,
    ];

    $dreamPriceQueries = $this->mock(DreamPriceQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getDreamPricesExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new DreamPrice()));
    });

    $dreamPriceController = new DreamPriceController($dreamPriceQueries);

    $response = $dreamPriceController->exportDreamPrices('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the updateStatus method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $dreamPriceQueries = $this->mock(DreamPriceQueries::class, function ($mock): void {
        $mock->shouldReceive('updateStatus')
            ->once();
    });

    $dreamPriceController = new DreamPriceController($dreamPriceQueries);

    $redirectResponse = $dreamPriceController->updateStatus(1, true);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Status changed successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/dream-prices', $redirectResponse->getTargetUrl());
});

function seedDreamPriceRecord(): array
{
    return [
        'name' => 'ABCD',
        'allow_registered_member' => false,
        'allow_employee' => false,
        'allow_walk_in_member' => false,
        'is_available_in_ecommerce' => false,
        'is_available_in_pos' => true,
        'start_date' => '2022-05-10',
        'end_date' => '2022-05-11',
        'location_ids' => [1],
        'member_group_ids' => [1],
        'employee_group_ids' => [1],
        'sale_channel_ids' => [1],
    ];
}
