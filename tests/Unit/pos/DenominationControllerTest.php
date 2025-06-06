<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Denomination\DenominationQueries;
use App\Http\Controllers\Api\Pos\DenominationController;
use App\Models\Cashier;
use App\Models\Denomination;
use Illuminate\Http\Request;

test('it calls the getList method and returns denomination records', function (): void {
    $companyId = 1;

    $denomination = Denomination::factory()->make([
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

    $this->mock(DenominationQueries::class, function ($mock) use ($companyId, $denomination): void {
        $mock->shouldReceive('getList')
            ->once()
            ->with($companyId, null)
            ->andReturn(collect($denomination));
    });

    $denominationController = new DenominationController();
    $response = $denominationController->getList($request);

    expect($response)->toBeArray();
});
