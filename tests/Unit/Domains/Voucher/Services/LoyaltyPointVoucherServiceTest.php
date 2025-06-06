<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Voucher\DataObjects\LoyaltyPointVoucherData;
use App\Domains\Voucher\Services\LoyaltyPointVoucherService;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Models\Member;
use App\Models\Membership;
use App\Models\VoucherConfiguration;
use App\Models\VoucherConfigurationTier;
use Symfony\Component\HttpKernel\Exception\HttpException;

test(
    'checkRequestDetails method throws an exception when voucher configuration not loyalty point.',
    function (): void {
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::BIRTHDAY_VOUCHER->value,
            'use_minimum_spend_amount' => 20,
            'validity_days' => 10,
            'discount_type' => DiscountTypes::FLAT->value,
            'get_value' => 1,
            'start_date' => now()->yesterday(),
            'end_date' => now()->tomorrow(),
            'status' => true,
        ]);
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
        ]);
        $loyaltyPointVoucherData = new LoyaltyPointVoucherData($voucherConfiguration->id, $member->id, 10);
        $loyaltyPointVoucherService = resolve(LoyaltyPointVoucherService::class);
        $loyaltyPointVoucherService->checkRequestDetails($member, $voucherConfiguration, $loyaltyPointVoucherData);
    }
)->throws(HttpException::class, 'The specified voucher configuration is not Loyalty Point voucher configuration.');

test('checkRequestDetails method throws an exception when voucher configuration expired.', function (): void {
    $voucherConfiguration = VoucherConfiguration::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'voucher_type' => VoucherTypes::LOYALTY_POINT->value,
        'use_minimum_spend_amount' => 20,
        'validity_days' => 10,
        'discount_type' => DiscountTypes::FLAT->value,
        'get_value' => 1,
        'start_date' => now()->addDay()->format('Y-m-d'),
        'end_date' => now()->addDay()->format('Y-m-d'),
        'status' => true,
    ]);
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => 1,
    ]);
    $loyaltyPointVoucherData = new LoyaltyPointVoucherData($voucherConfiguration->id, $member->id, 10);
    $loyaltyPointVoucherService = resolve(LoyaltyPointVoucherService::class);
    $loyaltyPointVoucherService->checkRequestDetails($member, $voucherConfiguration, $loyaltyPointVoucherData);
})->throws(
    HttpException::class,
    'The specified voucher configuration is available only between ' . now()->addDay()->format(
        'Y-m-d'
    ) . ' and ' . now()->addDay()->format(
        'Y-m-d'
    ) . ' However, the requested date is ' . now()->format('Y-m-d') . '.'
);

test(
    'checkRequestDetails method throws an exception when voucher configuration is not active.',
    function (): void {
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::LOYALTY_POINT->value,
            'use_minimum_spend_amount' => 20,
            'validity_days' => 10,
            'discount_type' => DiscountTypes::FLAT->value,
            'get_value' => 1,
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'status' => false,
        ]);
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
        ]);
        $loyaltyPointVoucherData = new LoyaltyPointVoucherData($voucherConfiguration->id, $member->id, 10);
        $loyaltyPointVoucherService = resolve(LoyaltyPointVoucherService::class);
        $loyaltyPointVoucherService->checkRequestDetails($member, $voucherConfiguration, $loyaltyPointVoucherData);
    }
)->throws(HttpException::class, 'The specified voucher configuration is not active.');

test('checkRequestDetails method throws an exception when member membership not active.', function (): void {
    $voucherConfiguration = VoucherConfiguration::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'voucher_type' => VoucherTypes::LOYALTY_POINT->value,
        'use_minimum_spend_amount' => 20,
        'validity_days' => 10,
        'discount_type' => DiscountTypes::FLAT->value,
        'get_value' => 1,
        'start_date' => now()->subDay()->format('Y-m-d'),
        'end_date' => now()->addDay()->format('Y-m-d'),
        'status' => true,
    ]);
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => null,
    ]);
    $loyaltyPointVoucherData = new LoyaltyPointVoucherData($voucherConfiguration->id, $member->id, 10);
    $loyaltyPointVoucherService = resolve(LoyaltyPointVoucherService::class);
    $loyaltyPointVoucherService->checkRequestDetails($member, $voucherConfiguration, $loyaltyPointVoucherData);
})->throws(
    HttpException::class,
    'The specified voucher configuration can only be used when membership is assigned to the member.'
);

test(
    'checkRequestDetails method throws an exception when member membership not match with voucher configuration membership.',
    function (): void {
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::LOYALTY_POINT->value,
            'use_minimum_spend_amount' => 20,
            'validity_days' => 10,
            'discount_type' => DiscountTypes::FLAT->value,
            'get_value' => 1,
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'status' => true,
        ]);
        $voucherConfiguration->memberships = collect([
            Membership::factory()->make([
                'id' => 1,
                'company_id' => 1,
            ]),
        ]);
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 2,
        ]);
        $loyaltyPointVoucherData = new LoyaltyPointVoucherData($voucherConfiguration->id, $member->id, 10);
        $loyaltyPointVoucherService = resolve(LoyaltyPointVoucherService::class);
        $loyaltyPointVoucherService->checkRequestDetails($member, $voucherConfiguration, $loyaltyPointVoucherData);
    }
)->throws(HttpException::class, 'The member membership is not in over voucher configuration.');

test(
    'checkRequestDetails method throws an exception when The specified loyalty point is more then the member loyalty point.',
    function (): void {
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::LOYALTY_POINT->value,
            'use_minimum_spend_amount' => 20,
            'validity_days' => 10,
            'discount_type' => DiscountTypes::FLAT->value,
            'get_value' => 1,
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'status' => true,
        ]);
        $voucherConfiguration->memberships = collect([
            Membership::factory()->make([
                'id' => 1,
                'company_id' => 1,
            ]),
        ]);
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
            'loyalty_points' => 5,
        ]);
        $loyaltyPointVoucherData = new LoyaltyPointVoucherData($voucherConfiguration->id, $member->id, 10);
        $loyaltyPointVoucherService = resolve(LoyaltyPointVoucherService::class);
        $loyaltyPointVoucherService->checkRequestDetails($member, $voucherConfiguration, $loyaltyPointVoucherData);
    }
)->throws(HttpException::class, 'The specified loyalty point is more then the member loyalty point.');

test(
    'checkRequestDetails method throws an exception when The voucher configuration is not valid.',
    function (): void {
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::LOYALTY_POINT->value,
            'use_minimum_spend_amount' => 20,
            'validity_days' => 10,
            'discount_type' => DiscountTypes::FLAT->value,
            'get_value' => 1,
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'status' => true,
        ]);
        $voucherConfiguration->memberships = collect([
            Membership::factory()->make([
                'id' => 1,
                'company_id' => 1,
            ]),
        ]);
        $voucherConfiguration->voucherConfigurationTiers = collect([
            VoucherConfigurationTier::factory()->make([
                'voucher_configuration_id' => 1,
                'minimum_spend_amount' => 10,
                'get_value' => 10,
            ]),
        ]);
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'membership_id' => 1,
            'loyalty_points' => 5,
        ]);
        $loyaltyPointVoucherData = new LoyaltyPointVoucherData($voucherConfiguration->id, $member->id, 0);
        $loyaltyPointVoucherService = resolve(LoyaltyPointVoucherService::class);
        $loyaltyPointVoucherService->checkRequestDetails($member, $voucherConfiguration, $loyaltyPointVoucherData);
    }
)->throws(HttpException::class, 'The specified voucher configuration is not valid.');

test('checkRequestDetails method return null.', function (): void {
    $voucherConfiguration = VoucherConfiguration::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'voucher_type' => VoucherTypes::LOYALTY_POINT->value,
        'use_minimum_spend_amount' => 20,
        'validity_days' => 10,
        'discount_type' => DiscountTypes::FLAT->value,
        'get_value' => 1,
        'start_date' => now()->subDay()->format('Y-m-d'),
        'end_date' => now()->addDay()->format('Y-m-d'),
        'status' => true,
    ]);

    $voucherConfiguration->memberships = collect([
        Membership::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]),
    ]);
    $voucherConfiguration->voucherConfigurationTiers = collect([
        VoucherConfigurationTier::factory()->make([
            'voucher_configuration_id' => 1,
            'minimum_spend_amount' => 10,
            'get_value' => 10,
        ]),
    ]);

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'membership_id' => 1,
        'loyalty_points' => 10,
    ]);

    $loyaltyPointVoucherData = new LoyaltyPointVoucherData($voucherConfiguration->id, $member->id, 10);

    $loyaltyPointVoucherService = resolve(LoyaltyPointVoucherService::class);

    $response = $loyaltyPointVoucherService->checkRequestDetails(
        $member,
        $voucherConfiguration,
        $loyaltyPointVoucherData
    );
    $this->assertNull($response);
});

test('getVoucherTierValue method returns response as expected', function (): void {
    $voucherConfiguration = VoucherConfiguration::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'voucher_type' => VoucherTypes::LOYALTY_POINT->value,
        'use_minimum_spend_amount' => 20,
        'validity_days' => 10,
        'discount_type' => DiscountTypes::FLAT->value,
        'get_value' => 1,
        'start_date' => now()->subDay()->format('Y-m-d'),
        'end_date' => now()->addDay()->format('Y-m-d'),
        'status' => true,
    ]);

    $voucherConfiguration->voucherConfigurationTiers = collect([
        VoucherConfigurationTier::factory()->make([
            'voucher_configuration_id' => 1,
            'minimum_spend_amount' => 10,
            'get_value' => 10,
        ]),
        VoucherConfigurationTier::factory()->make([
            'voucher_configuration_id' => 1,
            'minimum_spend_amount' => 20,
            'get_value' => 20,
        ]),
        VoucherConfigurationTier::factory()->make([
            'voucher_configuration_id' => 1,
            'minimum_spend_amount' => 30,
            'get_value' => 30,
        ]),
        VoucherConfigurationTier::factory()->make([
            'voucher_configuration_id' => 1,
            'minimum_spend_amount' => 40,
            'get_value' => 40,
        ]),
    ]);

    $loyaltyPointVoucherService = resolve(LoyaltyPointVoucherService::class);
    $response = $loyaltyPointVoucherService->getVoucherTierValue(20, $voucherConfiguration);
    $this->assertTrue(20.0 === $response);

    $response = $loyaltyPointVoucherService->getVoucherTierValue(15, $voucherConfiguration);
    $this->assertTrue(10.0 === $response);

    $response = $loyaltyPointVoucherService->getVoucherTierValue(35, $voucherConfiguration);
    $this->assertTrue(30.0 === $response);

    $response = $loyaltyPointVoucherService->getVoucherTierValue(50, $voucherConfiguration);
    $this->assertTrue(40.0 === $response);
});
