<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\LoyaltyCampaign\LoyaltyCampaignQueries;
use App\Http\Controllers\Api\Pos\LoyaltyCampaignController;
use App\Models\Cashier;
use App\Models\LoyaltyCampaign;
use Illuminate\Http\Request;

test('it calls the getList method and returns loyalty campaign records', function (): void {
    $companyId = 1;

    $loyaltyCampaign = LoyaltyCampaign::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'loyalty_point_expiration_days' => 10,
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

    $this->mock(LoyaltyCampaignQueries::class, function ($mock) use ($companyId, $loyaltyCampaign): void {
        $mock->shouldReceive('getActiveLoyaltyCampaignsByCompanyId')
            ->once()
            ->with($companyId, null)
            ->andReturn(collect($loyaltyCampaign));
    });

    $loyaltyCampaignController = new LoyaltyCampaignController();
    $response = $loyaltyCampaignController->getList($request);

    expect($response)->toBeArray();
});
