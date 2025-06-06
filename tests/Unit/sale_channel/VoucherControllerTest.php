<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\Member\MemberQueries;
use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\Voucher\DataObjects\LoyaltyPointVoucherData;
use App\Domains\Voucher\Services\LoyaltyPointVoucherService;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Domains\VoucherConfigurationChannelReference\VoucherConfigurationChannelReferenceQueries;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Http\Controllers\Api\SaleChannel\Voucher\VoucherController;
use App\Models\Member;
use App\Models\MemberChannelReference;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use App\Models\VoucherConfigurationChannelReference;

test(
    'it calls the getVouchers method and returns voucher records',
    function (): void {
        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('getListForEcommerceWithRelatedData')
                ->once()
                ->andReturn(collect());
        });

        [$saleChannel, $request] = setRequestUserForSaleChannel();

        $voucherController = new VoucherController();
        $response = $voucherController->getVouchers($request);

        $this->assertEquals(collect([]), $response['vouchers']->resource);
    }
);

test('it calls the generateMemberLoyaltyPointVoucher and generate voucher as expected', function (): void {
    $loyaltyPointVoucherData = new LoyaltyPointVoucherData(1, 1, 10);

    [$saleChannel, $request] = setRequestUserForSaleChannel();

    $companyId = 1;

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'date_of_birth' => now()->format('Y-m-d'),
        'created_location_id' => 1,
    ]);

    $memberChannelReference = MemberChannelReference::factory()->make([
        'id' => 1,
        'member_id' => $member->id,
        'sale_channel_id' => $saleChannel->id,
        'external_member_id' => 1,
    ]);

    $voucherConfiguration = VoucherConfiguration::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'voucher_type' => VoucherTypes::LOYALTY_POINT->value,
        'use_minimum_spend_amount' => 20,
        'validity_days' => 10,
        'discount_type' => DiscountTypes::FLAT->value,
        'get_value' => 1,
        'start_date' => now()->yesterday(),
        'end_date' => now()->tomorrow(),
        'status' => true,
    ]);

    $voucherConfigurationChannelReferences = new VoucherConfigurationChannelReference([
        'sale_channel_id' => $saleChannel->id,
        'voucher_configuration_id' => $voucherConfiguration->id,
        'external_voucher_configuration_id' => 1,
    ]);

    $voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => 1,
        'member_id' => $member->id,
        'generated_by_sale_id' => null,
        'flat_amount' => null,
    ]);

    $voucher->mismatches = collect([]);

    $this->mock(MemberChannelReferenceQueries::class, function ($mock) use (
        $saleChannel,
        $memberChannelReference
    ): void {
        $mock->shouldReceive('getByExternalMemberIdAndSaleChannelId')
            ->once()
            ->with(1, $saleChannel->id)
            ->andReturn($memberChannelReference);
    });

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('getByIdAndCompanyIdWithMembership')
            ->once()
            ->with(1, $member->id)
            ->andReturn($member);
    });

    $this->mock(VoucherConfigurationChannelReferenceQueries::class, function ($mock) use (
        $saleChannel,
        $voucherConfigurationChannelReferences
    ): void {
        $mock->shouldReceive('getByExternalVoucherConfigurationIdAndSaleChannelId')
            ->once()
            ->with(1, $saleChannel->id)
            ->andReturn($voucherConfigurationChannelReferences);
    });

    $this->mock(VoucherConfigurationQueries::class, function ($mock) use ($voucherConfiguration): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($voucherConfiguration);
    });

    $this->mock(LoyaltyPointVoucherService::class, function ($mock): void {
        $mock->shouldReceive('getVoucherTierValue')
            ->once()
            ->andReturn(10);
        $mock->shouldReceive('checkRequestDetails')
            ->once();
    });

    $this->mock(LoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('decreaseLoyaltyPoints')
            ->once();
    });

    $this->mock(VoucherQueries::class, function ($mock) use ($voucher): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($voucher);
        $mock->shouldReceive('loadVoucherWithMismatchesRelations')
            ->once()
            ->andReturn($voucher);
    });

    $this->mock(VoucherTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $voucherController = new VoucherController();
    $response = $voucherController->generateMemberLoyaltyPointVoucher($loyaltyPointVoucherData, $request);

    expect($response['voucher']->resource->toArray())
        ->toHaveKeys(
            [
                'id',
                'member_id',
                'discount_type',
                'number',
                'minimum_spend_amount',
                'percentage',
                'flat_amount',
                'expiry_date',
                'mismatches',
            ]
        );
});
