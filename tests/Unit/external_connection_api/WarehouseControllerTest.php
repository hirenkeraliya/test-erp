<?php

declare(strict_types=1);

use App\Domains\Location\LocationQueries;
use App\Http\Controllers\Api\ExternalConnection\WarehouseController;
use Illuminate\Http\Request;

test('getWarehouses method calls the getByCompanyIdAndTypeId method of LocationQueries calls', function (): void {
    $filterData = [
        'token' => '1234',
        'company_id' => 1,
    ];

    $request = new Request($filterData);

    $return = collect([]);
    $this->mock(LocationQueries::class, function ($mock) use ($return): void {
        $mock->shouldReceive('getByCompanyIdAndTypeId')
            ->once()
            ->andReturn($return);
    });

    $warehouseController = new WarehouseController();
    $response = $warehouseController->getWarehouses($request);
    $this->assertEquals($response, $return);
});
