<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Http\Controllers\Api\Pos\UnitOfMeasureController;
use App\Models\Cashier;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

test('it calls the getList method and returns unit of measures', function (): void {
    $companyId = 1;

    $unitOfMeasure = UnitOfMeasure::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
    ]);

    $cashier = makeCashierAndEmployeeForPosWithoutCounterUpdateId()['cashier'];

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(UnitOfMeasureQueries::class, function ($mock) use ($companyId, $unitOfMeasure): void {
        $mock->shouldReceive('getWithBasicColumnsAndDerivatives')
            ->once()
            ->with($companyId, null)
            ->andReturn(new Collection([$unitOfMeasure]));
    });

    $unitOfMeasureController = new UnitOfMeasureController();
    $response = $unitOfMeasureController->getList($request);

    expect($response['unit_of_measures']->resource)->toBeCollection();
});
