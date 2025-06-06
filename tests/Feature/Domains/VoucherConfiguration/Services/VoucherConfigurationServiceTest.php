<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\VoucherConfiguration\Enums\ExcludeByTypes;
use App\Domains\VoucherConfiguration\Enums\RestrictedByTypes;
use App\Domains\VoucherConfiguration\Enums\VouchersTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\Services\VoucherConfigurationService;
use App\Models\SaleDiscount;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;

test(
    'getVoucherType method returns voucher type for birthday restricted by member flat voucher as expected',
    function (): void {
        $voucherConfiguration = VoucherConfiguration::factory()->create([
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'voucher_type' => VoucherTypes::BIRTHDAY_VOUCHER->value,
            'exclude_by_type' => ExcludeByTypes::CATEGORIES->value,
            'discount_type' => DiscountTypes::FLAT->value,
        ]);

        $response = VoucherConfigurationService::getVoucherType(
            $voucherConfiguration->restricted_by_type,
            $voucherConfiguration->voucher_type,
            $voucherConfiguration->discount_type
        );

        $this->assertEquals(VouchersTypes::BIRTHDAY_RESTRICTED_BY_MEMBER_FLAT_VOUCHER->name, $response);
    }
);

test(
    'getVoucherType method returns voucher type for tier restricted by member percentage voucher as expected',
    function (): void {
        $voucherConfiguration = VoucherConfiguration::factory()->create([
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'voucher_type' => VoucherTypes::TIER_VOUCHER->value,
            'exclude_by_type' => ExcludeByTypes::CATEGORIES->value,
            'discount_type' => DiscountTypes::PERCENTAGE->value,
        ]);

        $response = VoucherConfigurationService::getVoucherType(
            $voucherConfiguration->restricted_by_type,
            $voucherConfiguration->voucher_type,
            $voucherConfiguration->discount_type
        );

        $this->assertEquals(VouchersTypes::TIER_RESTRICTED_BY_MEMBER_PERCENTAGE_VOUCHER->name, $response);
    }
);

test(
    'getVoucherType method returns voucher type for multiple restricted by member percentage voucher as expected',
    function (): void {
        $voucherConfiguration = VoucherConfiguration::factory()->create([
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'voucher_type' => VoucherTypes::MULTIPLE_VOUCHER->value,
            'exclude_by_type' => ExcludeByTypes::CATEGORIES->value,
            'discount_type' => DiscountTypes::PERCENTAGE->value,
        ]);

        $response = VoucherConfigurationService::getVoucherType(
            $voucherConfiguration->restricted_by_type,
            $voucherConfiguration->voucher_type,
            $voucherConfiguration->discount_type
        );

        $this->assertEquals(VouchersTypes::MULTIPLE_RESTRICTED_BY_MEMBER_PERCENTAGE_VOUCHER->name, $response);
    }
);

test(
    'getVoucherType method returns voucher type for loyalty point restricted by member percentage voucher as expected',
    function (): void {
        $voucherConfiguration = VoucherConfiguration::factory()->create([
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'voucher_type' => VoucherTypes::LOYALTY_POINT->value,
            'exclude_by_type' => ExcludeByTypes::CATEGORIES->value,
            'discount_type' => DiscountTypes::PERCENTAGE->value,
        ]);

        $response = VoucherConfigurationService::getVoucherType(
            $voucherConfiguration->restricted_by_type,
            $voucherConfiguration->voucher_type,
            $voucherConfiguration->discount_type
        );

        $this->assertEquals(VouchersTypes::LOYALTY_POINT_RESTRICTED_BY_MEMBER_PERCENTAGE_VOUCHER->name, $response);
    }
);

test(
    'getVoucherType method returns voucher type for loyalty point restricted by member flat voucher as expected',
    function (): void {
        $voucherConfiguration = VoucherConfiguration::factory()->create([
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'voucher_type' => VoucherTypes::LOYALTY_POINT->value,
            'exclude_by_type' => ExcludeByTypes::CATEGORIES->value,
            'discount_type' => DiscountTypes::FLAT->value,
        ]);

        $response = VoucherConfigurationService::getVoucherType(
            $voucherConfiguration->restricted_by_type,
            $voucherConfiguration->voucher_type,
            $voucherConfiguration->discount_type
        );

        $this->assertEquals(VouchersTypes::LOYALTY_POINT_RESTRICTED_BY_MEMBER_FLAT_VOUCHER->name, $response);
    }
);

test(
    'calculateTotalCountsAndAmount method call and return proper response',
    function (): void {
        $voucherConfiguration = VoucherConfiguration::factory()->create();

        $voucher = Voucher::factory()->create([
            'voucher_configuration_id' => $voucherConfiguration->id,
        ]);
        $voucherCollection = collect([$voucher]);

        $saleDiscount = SaleDiscount::factory()->create([
            'discountable_type' => ModelMapping::VOUCHER->name,
            'discountable_id' => $voucher->id,
            'amount' => 50,
        ]);

        $response = VoucherConfigurationService::calculateTotalCountsAndAmount($voucherCollection);

        $this->assertIsArray($response);
        $this->assertEquals(1, $response[0]);
        $this->assertEquals($saleDiscount->amount, $response[1]);
    });
