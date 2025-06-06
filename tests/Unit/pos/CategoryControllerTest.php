<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Category\CategoryQueries;
use App\Http\Controllers\Api\Pos\CategoryController;
use App\Models\Cashier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

test('it calls getList method and returns the categories list', function (): void {
    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $request = new Request();
    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(CategoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyIdForPos')
            ->once()
            ->andReturn(new Collection([]));
    });

    $categoryController = new CategoryController();
    $response = $categoryController->getList($request);

    $this->assertArrayHasKey('categories', $response);
}
);
