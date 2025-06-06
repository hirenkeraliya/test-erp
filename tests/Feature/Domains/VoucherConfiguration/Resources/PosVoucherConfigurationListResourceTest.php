<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\VoucherConfiguration\Resources\PosVoucherConfigurationListResource;
use App\Models\VoucherConfiguration;

test(
    'getKeyNameAsPerSelectedVoucher method returns get value key as per selected voucher as expected',
    function (): void {
        $voucherConfiguration = VoucherConfiguration::factory()->create([
            'discount_type' => DiscountTypes::FLAT->value,
        ]);

        $response = PosVoucherConfigurationListResource::getKeyNameAsPerSelectedVoucher(
            $voucherConfiguration->discount_type
        );

        $this->assertEquals('flat_amount', $response);
    }
);
