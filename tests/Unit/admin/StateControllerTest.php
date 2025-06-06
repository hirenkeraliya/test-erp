<?php

declare(strict_types=1);

use App\Domains\State\DataObjects\StateData;
use App\Domains\State\StateQueries;
use App\Http\Controllers\Admin\StateController;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getStatesByCountryId method of the states queries class and returns proper response',
    function (): void {
        setCompanyIdInSession(1);
        $this->mock(StateQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCountryId')
                ->once()
                ->andReturn(collect([]));
        });
        $stateController = resolve(StateController::class);
        $response = $stateController->getStatesByCountryId(1);

        expect($response)
            ->toHaveKey('states');
    }
);

test('It calls the List query method of the state queries class and returns proper response', function (): void {
    $requestParameter = [
        'search_text' => 'abc',
        'sort_by' => 'name',
        'sort_direction' => 'desc',
        'per_page' => 1,
    ];

    $stateQueries = $this->mock(StateQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter)
            ->andReturn(new LengthAwarePaginator([], 20, 15));
    });

    $stateController = new StateController($stateQueries);

    $response = $stateController->fetchStates(new Request($requestParameter));

    $this->assertEquals(20, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls the addNew method of the state queries class and returns proper response', function (): void {
    $stateData = new StateData(1, 'name');

    $stateQueries = $this->mock(StateQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $stateController = new StateController($stateQueries);
    $redirectResponse = $stateController->store($stateData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('State added successfully.', $redirectResponse->getSession()->all()['success']);
});

test('It calls the update method of the state queries class and returns proper response', function (): void {
    $stateData = new StateData(1, 'name');

    $stateQueries = $this->mock(StateQueries::class, function ($mock): void {
        $mock->shouldReceive('update')
            ->once();
    });

    $stateController = new StateController($stateQueries);
    $redirectResponse = $stateController->update($stateData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('State updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/get-states', $redirectResponse->getTargetUrl());
});

test('It calls the exportStates method and returns a proper response', function (): void {
    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $stateQueries = $this->mock(StateQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getStateExport')
            ->once()
            ->with($requestParameter)
            ->andReturn(collect(new State()));
    });

    $stateController = new StateController($stateQueries);

    $response = $stateController->exportStates('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
