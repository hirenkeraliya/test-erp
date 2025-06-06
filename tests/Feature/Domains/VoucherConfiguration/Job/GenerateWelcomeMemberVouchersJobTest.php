<?php

declare(strict_types=1);

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Member\MemberQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\Enums\RestrictedByTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\Jobs\GenerateWelcomeMemberVouchersJob;
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
    'GenerateWelcomeMemberVouchers job calls respective methods and generate welcome member vouchers as expected',
    function (): void {
        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_type' => VoucherTypes::WELCOME_MEMBER->value,
            'restricted_by_type' => RestrictedByTypes::MEMBER_ONLY->value,
            'discount_type' => DiscountTypes::PERCENTAGE->value,
            'start_date' => Carbon::yesterday()->format('Y-m-d'),
            'end_date' => Carbon::tomorrow()->format('Y-m-d'),
        ]);

        $member = Member::factory()->create([
            'date_of_birth' => Carbon::tomorrow(),
            'welcome_member_voucher_generated_at' => null,
            'welcome_member_voucher_id' => null,
        ]);

        $voucher = Voucher::factory()->make([
            'id' => 2,
            'member_id' => 1,
            'voucher_configuration_id' => 1,
            'generated_by_sale_id' => 1,
        ]);

        $this->mock(VoucherConfigurationQueries::class, function ($mock) use ($voucherConfiguration): void {
            $mock->shouldReceive('getWelcomeMemberVoucherConfigurationByCompanyId')
                ->once()
                ->andReturn($voucherConfiguration);
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

        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('updateWelcomeMemberVoucherDetails')
                ->once();

            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($member);
        });

        GenerateWelcomeMemberVouchersJob::dispatch($member->id, $member->company_id)->onQueue(
            config('horizon.default_queue_name')
        );
    }
);
