<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Http\Controllers\Api\Pos\PaymentTypeController;
use App\Models\Cashier;
use App\Models\PaymentType;
use Illuminate\Http\Request;

test(
    'it calls the getActiveOnlyAndAvailableInPosWithSubPaymentTypes method and returns the payment types',
    function (): void {
        $companyId = 1;

        $paymentType = PaymentType::factory()->make([
            'company_id' => $companyId,
            'is_available_in_pos' => true,
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

        $this->mock(PaymentTypeQueries::class, function ($mock) use ($companyId, $paymentType): void {
            $mock->shouldReceive('getActiveOnlyAndAvailableInPosWithSubPaymentTypes')
                ->once()
                ->with($companyId, null)
                ->andReturn(collect($paymentType));
        });

        $paymentTypeController = new PaymentTypeController();
        $response = $paymentTypeController->getList($request);

        expect($response['static_payment_types'])->toBeArray();
        expect($response['payment_types']->resource)->toBeCollection();
    }
);
