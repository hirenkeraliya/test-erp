<?php

declare(strict_types=1);

use App\Domains\Location\LocationQueries;
use App\Http\Controllers\Api\ExternalConnection\StoreController;
use Illuminate\Http\Request;

test('getStores method calls the getByCompanyIdAndTypeId method of LocationQueries calls', function (): void {
    $filterData = [
        'token' => '1234',
    ];

    $request = new Request($filterData);

    $return = collect([]);
    $this->mock(LocationQueries::class, function ($mock) use ($return): void {
        $mock->shouldReceive('getByCompanyIdAndTypeId')
            ->once()
            ->andReturn($return);
    });

    $storeController = new StoreController();
    $response = $storeController->getStores($request);
    $this->assertEquals($response, $return);
});
