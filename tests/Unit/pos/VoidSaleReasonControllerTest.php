<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\VoidSaleReason\VoidSaleReasonQueries;
use App\Http\Controllers\Api\Pos\VoidSaleReasonController;
use App\Models\Cashier;
use App\Models\VoidSaleReason;
use Illuminate\Http\Request;

test('it calls the getListForPOS method and returns Void Codes records', function (): void {
    $companyId = 1;

    $voidSaleReason = VoidSaleReason::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => SaleReturnOrVoidSaleReasonTypes::POS->value,
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

    $typeId = SaleReturnOrVoidSaleReasonTypes::POS->value;
    $this->mock(VoidSaleReasonQueries::class, function ($mock) use ($companyId, $voidSaleReason, $typeId): void {
        $mock->shouldReceive('getListForPOSOrOrders')
            ->once()
            ->with($companyId, $typeId, null)
            ->andReturn(collect($voidSaleReason));
    });

    $voidSaleReasonController = new VoidSaleReasonController();
    $response = $voidSaleReasonController->getList($request);

    expect($response)->toBeArray();
});
