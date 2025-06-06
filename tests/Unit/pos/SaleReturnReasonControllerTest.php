<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use App\Http\Controllers\Api\Pos\SaleReturnReasonController;
use App\Models\Cashier;
use App\Models\SaleReturnReason;
use Illuminate\Http\Request;

test('it calls the getListForPos method and returns sale return reasons records', function (): void {
    $companyId = 1;

    $saleReturnReason = SaleReturnReason::factory()->make([
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
    $this->mock(SaleReturnReasonQueries::class, function ($mock) use ($companyId, $saleReturnReason, $typeId): void {
        $mock->shouldReceive('getListForPOSOrOrders')
            ->once()
            ->with($companyId, $typeId, null)
            ->andReturn(collect($saleReturnReason));
    });

    $saleReturnReasonController = new SaleReturnReasonController();
    $response = $saleReturnReasonController->getList($request);

    expect($response)->toBeArray();
});
