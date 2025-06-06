<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Member\MemberQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\Enums\RestrictedByTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\Jobs\GenerateMemberBirthdayVouchersJob;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\Member;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->voucherQueries = new VoucherQueries();
});

test(
    'GenerateMemberBirthdayVouchers job calls respective methods and generate birthday vouchers as expected',
    function (): void {
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::BIRTHDAY_VOUCHER->value,
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'discount_type' => DiscountTypes::PERCENTAGE->value,
            'start_date' => Carbon::yesterday()->format('Y-m-d'),
            'end_date' => Carbon::tomorrow()->format('Y-m-d'),
        ]);

        $memberA = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'date_of_birth' => Carbon::tomorrow(),
            'created_location_id' => 1,
            'birthday_voucher_last_generated_at' => Carbon::now()->subYears(2)->format('Y-m-d'),
        ]);

        $memberB = Member::factory()->make([
            'id' => 2,
            'company_id' => 1,
            'date_of_birth' => Carbon::tomorrow(),
            'created_location_id' => 1,
            'birthday_voucher_last_generated_at' => Carbon::now()->subYears(2)->format('Y-m-d'),
        ]);

        $voucher = Voucher::factory()->make([
            'id' => 2,
            'member_id' => 1,
            'voucher_configuration_id' => 1,
            'generated_by_sale_id' => 1,
        ]);

        $this->mock(VoucherConfigurationQueries::class, function ($mock) use ($voucherConfiguration): void {
            $mock->shouldReceive('getBirthdayVoucherConfiguration')
                ->once()
                ->andReturn(collect([
                    '0' => $voucherConfiguration,
                ]));
        });

        $this->mock(VoucherQueries::class, function ($mock) use ($voucher): void {
            $mock->shouldReceive('addNew')
                ->times(2)
                ->andReturn($voucher);
        });

        $this->mock(VoucherTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->times(2);
        });

        $this->mock(MemberQueries::class, function ($mock) use ($memberA, $memberB): void {
            $mock->shouldReceive('getMembersByBirthDate')
                ->once()
                ->andReturn(collect([
                    '0' => $memberA,
                    '1' => $memberB,
                ]));
            $mock->shouldReceive('updateBirthdayVoucherDetails')
                ->twice();
        });

        GenerateMemberBirthdayVouchersJob::dispatch()->onQueue(config('horizon.default_queue_name'));
    }
);

test(
    'It calls the updateBirthdayVoucherDetails method if member birthday voucher not generated',
    function (): void {
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::BIRTHDAY_VOUCHER->value,
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'discount_type' => DiscountTypes::PERCENTAGE->value,
            'start_date' => Carbon::yesterday()->format('Y-m-d'),
            'end_date' => Carbon::tomorrow()->format('Y-m-d'),
        ]);

        $memberA = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'date_of_birth' => Carbon::now(),
            'created_location_id' => 1,
            'birthday_voucher_last_generated_at' => Carbon::now()->format('Y-m-d'),
        ]);

        $memberB = Member::factory()->make([
            'id' => 2,
            'company_id' => 1,
            'date_of_birth' => Carbon::now(),
            'created_location_id' => 1,
            'birthday_voucher_last_generated_at' => null,
        ]);

        $voucher = Voucher::factory()->make([
            'id' => 1,
            'voucher_configuration_id' => 1,
            'member_id' => $memberA->id,
            'generated_by_sale_id' => 1,
            'discount_type' => DiscountTypes::FLAT->value,
            'flat_amount' => 10,
            'percentage' => 10,
            'minimum_spend_amount' => 10,
            'expiry_date' => '2022-01-10',
            'used_at' => null,
        ]);

        $this->mock(VoucherConfigurationQueries::class, function ($mock) use ($voucherConfiguration): void {
            $mock->shouldReceive('getBirthdayVoucherConfiguration')
                ->once()
                ->andReturn(collect([
                    '0' => $voucherConfiguration,
                ]));
        });

        $this->mock(VoucherQueries::class, function ($mock) use ($voucher): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($voucher);
        });

        $this->mock(VoucherTransactionQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(MemberQueries::class, function ($mock) use ($memberA, $memberB): void {
            $mock->shouldReceive('getMembersByBirthDate')
                ->once()
                ->andReturn(collect([
                    '0' => $memberA,
                    '1' => $memberB,
                ]));
            $mock->shouldReceive('updateBirthdayVoucherDetails')
                ->once();
        });

        GenerateMemberBirthdayVouchersJob::dispatch()->onQueue(config('horizon.default_queue_name'));
    }
);
