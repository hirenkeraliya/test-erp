<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\Cashier\CashierQueries;
use App\Http\Controllers\Api\Pos\BrandController;
use App\Models\Cashier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

test('it calls getList method and returns the brands list', function (): void {
    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $request = new Request();
    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(BrandQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyBrands')
            ->once()
            ->andReturn(new Collection([]));
    });

    $brandController = new BrandController();
    $response = $brandController->getList($request);

    $this->assertArrayHasKey('brands', $response);
}
);
