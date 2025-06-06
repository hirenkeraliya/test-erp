<?php

declare(strict_types=1);

use App\Domains\Season\DataObjects\SeasonData;
use App\Domains\Season\SeasonQueries;
use App\Http\Controllers\Admin\SeasonController;
use App\Models\Season;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the list query method of the season queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $seasonQueries = $this->mock(SeasonQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $seasonController = new SeasonController($seasonQueries);

    $response = $seasonController->fetchSeasons(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls addNew method of the season queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);
    $seasonDetails = Season::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($seasonDetails['company_id']);

    $seasonRecord = new SeasonData(...$seasonDetails);

    $seasonQueries = $this->mock(SeasonQueries::class, function ($mock) use ($seasonRecord, $companyId): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($seasonRecord, $companyId);
    });

    $seasonController = new SeasonController($seasonQueries);
    $redirectResponse = $seasonController->store($seasonRecord);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Season added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/seasons', $redirectResponse->getTargetUrl());
});

test('It calls the addNew method of SeasonQueries with valid data and returns a response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);
    $seasonDetails = Season::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($seasonDetails['company_id']);

    $seasonRecord = new SeasonData(...$seasonDetails);

    $seasonQueries = $this->mock(SeasonQueries::class, function ($mock) use ($seasonRecord, $companyId): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($seasonRecord, $companyId);
    });

    $seasonController = new SeasonController($seasonQueries);
    $response = $seasonController->storeAndReturn($seasonRecord);
    $this->assertArrayHasKey('season', $response);
});

test('It calls get by id method of the season queries class and return proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = Season::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    $seasonQueries = $this->mock(SeasonQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new Season($requestParameter));
    });

    $seasonController = new SeasonController($seasonQueries);
    $response = $seasonController->edit(1);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has(
            'season',
            fn (Assert $season): Assert => $season
                ->where('name', $requestParameter['name'])
                ->where('code', $requestParameter['code'])
                ->etc()
        )
    );
});

test('It calls update method of the season queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $seasonDetails = Season::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($seasonDetails['company_id']);

    $seasonRecord = new SeasonData(...$seasonDetails);

    $seasonQueries = $this->mock(SeasonQueries::class, function ($mock) use ($seasonRecord, $companyId): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($seasonRecord, 1, $companyId);
    });

    $seasonController = new SeasonController($seasonQueries);
    $redirectResponse = $seasonController->update($seasonRecord, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Season updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/seasons', $redirectResponse->getTargetUrl());
});

test('It calls the exportSeasons method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $seasonQueries = $this->mock(SeasonQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getSeasonsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Season()));
    });

    $seasonController = new SeasonController($seasonQueries);

    $response = $seasonController->exportSeasons('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
