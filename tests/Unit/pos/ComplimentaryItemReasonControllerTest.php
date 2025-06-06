<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\ComplimentaryItemReason\ComplimentaryItemReasonQueries;
use App\Http\Controllers\Api\Pos\ComplimentaryItemReasonController;
use App\Models\Cashier;
use App\Models\ComplimentaryItemReason;
use Illuminate\Http\Request;

test(
    'it calls the getList method and returns complimentary item reasons records',
    function (): void {
        $companyId = 1;

        $complimentaryItemReason = ComplimentaryItemReason::factory()->make([
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

        $this->mock(ComplimentaryItemReasonQueries::class, function ($mock) use (
            $companyId,
            $complimentaryItemReason
        ): void {
            $mock->shouldReceive('getList')
                ->once()
                ->with($companyId, null)
                ->andReturn(collect($complimentaryItemReason));
        });

        $complimentaryItemReasonController = new ComplimentaryItemReasonController();
        $response = $complimentaryItemReasonController->getList($request);

        expect($response)->toBeArray();
    }
);
