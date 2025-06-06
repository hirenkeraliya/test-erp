<?php

declare(strict_types=1);

use App\Domains\LoyaltyCampaign\LoyaltyCampaignQueries;
use App\Http\Controllers\Api\SaleChannel\LoyaltyCampaign\LoyaltyCampaignController;

test(
    'it calls the getLoyaltyCampaignConfigurations method and returns loyalty campaign records',
    function (): void {
        $this->mock(LoyaltyCampaignQueries::class, function ($mock): void {
            $mock->shouldReceive('getActiveLoyaltyCampaignsByCompanyId')
                ->once()
                ->andReturn(collect());
        });

        [$saleChannel, $request] = setRequestUserForSaleChannel();

        $loyaltyCampaignController = new LoyaltyCampaignController();
        $response = $loyaltyCampaignController->getLoyaltyCampaignConfigurations($request);

        $this->assertEquals(collect([]), $response['loyalty_campaigns']->resource);
    }
);
