<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Department\DepartmentQueries;
use App\Http\Controllers\Api\Pos\DepartmentController;
use App\Models\Cashier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

test('it calls getList method and returns the departments list', function (): void {
    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $request = new Request();
    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(DepartmentQueries::class, function ($mock): void {
        $mock->shouldReceive('getWithBasicColumns')
            ->once()
            ->andReturn(new Collection([]));
    });

    $departmentController = new DepartmentController();
    $response = $departmentController->getList($request);

    $this->assertArrayHasKey('departments', $response);
}
);
