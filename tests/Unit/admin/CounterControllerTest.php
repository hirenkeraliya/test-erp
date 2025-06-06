<?php

declare(strict_types=1);

use App\Domains\Counter\CounterQueries;
use App\Domains\Counter\DataObjects\CounterData;
use App\Domains\Counter\Resources\CounterListResource;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Http\Controllers\Admin\CounterController;
use App\Models\Counter;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the counter queries class and returns proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'abc',
        'sort_by' => 'name',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'location_ids' => null,
    ];

    $counterQueries = $this->mock(CounterQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));

        $mock->shouldReceive('getAppVersionCounts')
        ->once()
        ->with($companyId)
        ->andReturn(collect());
    });

    $counterController = new CounterController($counterQueries);
    $response = $counterController->fetchCounters(new Request($requestParameter));
    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(CounterListResource::collection(collect([])), $response['data']);
});

test(
    'create counter method calls the get with basic columns method of the store queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $locations = [[
            'id' => '1',
            'name' => 'ABC',
            'type_id' => LocationTypes::STORE->value,
        ]];

        $locationQueries = $this->mock(LocationQueries::class, function ($mock) use (
            $locations,
            $companyId
        ): void {
            $mock->shouldReceive('getStoreWithBasicColumns')
                ->once()
                ->with($companyId)
                ->andReturn(new Collection($locations));
        });

        $counterController = new CounterController(new CounterQueries());
        $response = $counterController->create($locationQueries);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
                ->has(
                    'locations',
                    fn (Assert $locations): Assert => $locations
                    ->has(
                        '0',
                        fn (Assert $location): Assert => $location->where('id', '1')->where(
                            'name',
                            'ABC'
                        )->where('type_id', LocationTypes::STORE->value)
                    )
                )
        );
    }
);

test('It calls the addNew method of the counter queries class and returns proper response', function (): void {
    $counterRecord = [
        'location_id' => 1,
        'name' => 'Test Counter',
        'is_locked' => true,
    ];

    $counterData = new CounterData(...$counterRecord);

    $counterQueries = $this->mock(CounterQueries::class, function ($mock) use ($counterData): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($counterData);
    });

    $counterController = new CounterController($counterQueries);
    $redirectResponse = $counterController->store($counterData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('The counter has been added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/counters', $redirectResponse->getTargetUrl());
});

test('It calls the get by id method of the counter queries class and returns proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession();

    $counterRecord = [
        'name' => 'Test Counter',
    ];

    $counterQueries = $this->mock(CounterQueries::class, function ($mock) use ($counterRecord, $companyId): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new Counter($counterRecord));
    });

    $locations = [[
        'id' => '1',
        'name' => 'ABC',
        'type_id' => LocationTypes::STORE->value,
    ]];

    $locationQueries = $this->mock(LocationQueries::class, function ($mock) use ($locations): void {
        $mock->shouldReceive('getStoreWithBasicColumns')
            ->once()
            ->andReturn(new Collection($locations));
    });

    $counterController = new CounterController($counterQueries);
    $response = $counterController->edit(1, $locationQueries);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has('counter', fn (Assert $counter): Assert => $counter->where('name', 'Test Counter'))
        ->has(
            'locations',
            fn (Assert $locations): Assert => $locations
            ->has(
                '0',
                fn (Assert $location): Assert => $location->where('id', '1')->where(
                    'name',
                    'ABC'
                )->where('type_id', LocationTypes::STORE->value)
            )
        )
    );
});

test('It calls the update method of the counter queries class and returns proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession();

    $counterRecord = [
        'location_id' => 1,
        'name' => 'Test Counter',
        'is_locked' => true,
    ];

    $counterData = new CounterData(...$counterRecord);

    $counterQueries = $this->mock(CounterQueries::class, function ($mock) use ($counterData, $companyId): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($counterData, 1, $companyId);
    });

    $counterController = new CounterController($counterQueries);
    $redirectResponse = $counterController->update($counterData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Counter updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/counters', $redirectResponse->getTargetUrl());
});

test(
    'It calls the getCounterListOfSelectedLocation queries method of counter queries and get store counters list',
    function (): void {
        $companyId = 1;
        $locationId = 1;
        setCompanyIdInSession();
        $counters = Counter::factory(2)->make([
            'location_id' => $locationId,
        ]);
        $counterQueries = $this->mock(CounterQueries::class, function ($mock) use (
            $companyId,
            $locationId,
            $counters
        ): void {
            $mock->shouldReceive('getCounterListOfSelectedLocation')
                ->once()
                ->with($locationId, $companyId)
                ->andReturn(collect([$counters]));
        });
        $counterController = new CounterController($counterQueries);
        $response = $counterController->getLocationCounters($locationId);
        $this->assertEquals(collect([$counters]), $response['counters']);
    }
);

test(
    'It calls the getCountersOfLocations queries method of counter queries and get counters list',
    function (): void {
        $locationId = 1;
        setCompanyIdInSession();

        $request = new Request([
            'locations_ids' => [$locationId],
        ]);

        $counter = Counter::factory()->make([
            'location_id' => $locationId,
        ]);

        $counterQueries = $this->mock(CounterQueries::class, function ($mock) use ($counter): void {
            $mock->shouldReceive('getCountersOfLocations')
                ->once()
                ->andReturn(collect([$counter]));
        });

        $counterController = new CounterController($counterQueries);
        $response = $counterController->getCountersOfLocations($request);
        $this->assertEquals(collect([$counter]), $response['counters']);
    }
);

test('It calls the exportCounters method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'location_ids' => null,
    ];

    $counterQueries = $this->mock(CounterQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getCountersExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Counter()));
    });

    $counterController = new CounterController($counterQueries);

    $response = $counterController->exportCounters('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
