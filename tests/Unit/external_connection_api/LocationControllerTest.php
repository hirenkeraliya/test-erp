<?php

declare(strict_types=1);

use App\Domains\Location\LocationQueries;
use App\Http\Controllers\Api\ExternalConnection\LocationController;
use Illuminate\Http\Request;

test('getLocations method calls the getByCompanyId method of LocationQueries calls', function (): void {
    $filterData = [
        'token' => '1234',
        'company_id' => 1,
    ];

    $request = new Request($filterData);

    $return = collect([]);
    $this->mock(LocationQueries::class, function ($mock) use ($return): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn($return);
    });

    $locationController = new LocationController();
    $response = $locationController->getLocations($request);
    $this->assertEquals($response, $return);
});
