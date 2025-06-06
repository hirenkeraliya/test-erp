<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Style\StyleQueries;
use App\Http\Controllers\Api\Pos\StyleController;
use App\Models\Cashier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

test('it calls getList method and returns the styles list', function (): void {
    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $request = new Request();
    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(StyleQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Collection([]));
    });

    $styleController = new StyleController();
    $response = $styleController->getList($request);

    $this->assertArrayHasKey('styles', $response);
}
);
