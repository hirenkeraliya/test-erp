<?php

declare(strict_types=1);

use App\Domains\Courier\CourierQueries;
use App\Domains\Courier\DataObjects\CourierData;
use App\Http\Controllers\SuperAdmin\CourierController;
use App\Models\Courier;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('It calls the courier queries class and returns proper response', function (): void {
    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $courierQueries = $this->mock(CourierQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $courierController = new CourierController($courierQueries);

    $response = $courierController->fetchCourier(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls the Add New method of courier queries class', function (): void {
    $courierData = new CourierData('ABC', 'XYZ', 1, '1', '1', '1', [1], [
        'webhook_url_type_id' => 1,
        'url' => 'test',
    ]);

    $courierQueries = $this->mock(CourierQueries::class, function ($mock) use ($courierData): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($courierData);
    });

    $courierController = new CourierController($courierQueries);

    $redirectResponse = $courierController->store($courierData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Courier added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/courier', $redirectResponse->getTargetUrl());
});

test('It calls the update courier method of the courier  queries class', function (): void {
    $courierData = new CourierData('ABC', 'XYZ', 1, '1', '1', '1', [1], [
        'webhook_url_type_id' => 1,
        'url' => 'test',
    ]);

    $courier = Courier::factory()->make([
        'name' => 'test',
    ]);

    $courierQueries = $this->mock(CourierQueries::class, function ($mock) use ($courier): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1)
            ->andReturn($courier);
        $mock->shouldReceive('update')
            ->once();
    });

    $courierController = new CourierController($courierQueries);

    $redirectResponse = $courierController->update($courierData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Courier updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/courier', $redirectResponse->getTargetUrl());
});
