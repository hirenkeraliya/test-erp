<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\Member\MemberQueries;
use App\Domains\Voucher\DataObjects\LoyaltyPointVoucherData;
use App\Domains\Voucher\Services\LoyaltyPointVoucherService;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Http\Controllers\Api\Member\VoucherController;
use App\Models\Member;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Illuminate\Http\Request;

test('it calls the generateMemberLoyaltyPointVoucher and generate birthday voucher as expected', function (): void {
    $loyaltyPointVoucherData = new LoyaltyPointVoucherData(1, 1, 10);

    $request = new Request();

    $companyId = 1;

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'date_of_birth' => now()->format('Y-m-d'),
        'created_location_id' => 1,
    ]);

    $request->setUserResolver(fn (): Member => $member);

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

    $voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => 1,
        'member_id' => $member->id,
        'generated_by_sale_id' => null,
        'flat_amount' => null,
    ]);

    $voucher->mismatches = collect([]);

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('getByIdAndCompanyIdWithMembership')
            ->once()
            ->with(1, $member->id)
            ->andReturn($member);
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
