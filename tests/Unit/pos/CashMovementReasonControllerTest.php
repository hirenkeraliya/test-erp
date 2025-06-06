<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\CashMovementReason\CashMovementReasonQueries;
use App\Http\Controllers\Api\Pos\CashMovementReasonController;
use App\Models\Cashier;
use App\Models\CashMovementReason;
use Illuminate\Http\Request;

test('it calls the getList method and returns Cash Flow Codes records', function (): void {
    $companyId = 1;

    $cashMovementReason = CashMovementReason::factory()->make([
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

    $this->mock(CashMovementReasonQueries::class, function ($mock) use ($companyId, $cashMovementReason): void {
        $mock->shouldReceive('getList')
            ->once()
            ->with($companyId, null)
            ->andReturn(collect($cashMovementReason));
    });

    $cashMovementReasonController = new CashMovementReasonController();
    $response = $cashMovementReasonController->getList($request);

    expect($response)->toBeArray();
});
