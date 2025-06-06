<?php

declare(strict_types=1);

use App\Domains\Membership\MembershipQueries;
use App\Http\Controllers\Api\SaleChannel\Membership\MembershipController;

test(
    'it calls the getMembership method and returns membership records',
    function (): void {
        $this->mock(MembershipQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyIdSortByMinimumSpendAmount')
                ->once()
                ->andReturn(collect());
        });

        [$saleChannel, $request] = setRequestUserForSaleChannel();

        $membershipController = new MembershipController();
        $response = $membershipController->getMembership($request);

        $this->assertEquals(collect([]), $response['memberships']->resource);
    }
);
