<?php

declare(strict_types=1);

use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Http\Controllers\Api\SaleChannel\VoucherConfiguration\VoucherConfigurationController;

test(
    'it calls the getVoucherConfigurations method and returns vouchers list records with related data',
    function (): void {
        $this->mock(VoucherConfigurationQueries::class, function ($mock): void {
            $mock->shouldReceive('getListForEcommerceWithRelatedData')
                ->once()
                ->andReturn(collect());
        });

        [$saleChannel, $request] = setRequestUserForSaleChannel();

        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherConfigurationController = new VoucherConfigurationController($voucherConfigurationQueries);
        $response = $voucherConfigurationController->getVoucherConfigurations($request);

        $this->assertEquals(collect([]), $response['vouchers']->resource);
    }
);
