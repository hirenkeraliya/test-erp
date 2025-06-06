<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\CompanyQueries;
use App\Domains\ExportRecord\ExportRecordQueries;
use App\Domains\ExportRecord\Jobs\ExportToExcelJob;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\Enums\MemberChannelEnum;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Services\MemberService;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherConfiguration\Jobs\GenerateWelcomeMemberVouchersJob;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\Admin;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\ExportRecord;
use App\Models\Location;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->companyId = 1;
});

test(
    'checkRequiredMemberColumns method throws an exception when the mobile number does not specify.',
    function (): void {
        $memberDetails = [
            'first_name' => 'abc',
            'mobile_number' => '',
        ];

        $memberService = resolve(MemberService::class);
        $memberService->checkRequiredMemberColumns($memberDetails, $this->companyId);
    }
)->throws(HttpException::class, 'mobile number is required');

test(
    'checkRequiredMemberColumns method throws an exception when the email does not specify.',
    function (): void {
        $memberDetails = [
            'first_name' => 'abc',
            'email' => '',
        ];

        $memberService = resolve(MemberService::class);
        $memberService->checkRequiredMemberColumns($memberDetails, $this->companyId);
    }
)->throws(HttpException::class);

test(
    'checkRequiredMemberColumns method throws an exception when the card_number does not specify.',
    function (): void {
        $memberDetails = [
            'first_name' => 'abc',
            'card_number' => '',
        ];

        $memberService = resolve(MemberService::class);
        $memberService->checkRequiredMemberColumns($memberDetails, $this->companyId);
    }
)->throws(HttpException::class);

test(
    'checkRequiredMemberColumns method throws an exception when the first_name does not specify.',
    function (): void {
        $memberDetails = [
            'first_name' => '',
        ];

        $memberService = resolve(MemberService::class);
        $memberService->checkRequiredMemberColumns($memberDetails, $this->companyId);
    }
)->throws(HttpException::class, 'First name is required');

test(
    'getMemberIdFromDetails method returns the member id if the member card number is exists',
    function (): void {
        $memberDetails = [
            'card_number' => '1234567890',
        ];

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => $this->location->id,
            'first_name' => 'ABC',
            'mobile_number' => '123456789',
            'card_number' => 'ABC1234',
        ]);

        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('getMemberByCardNumber')
                ->once()
                ->andReturn($member);
        });

        $memberService = resolve(MemberService::class);
        $memberService->getMemberIdFromDetails($memberDetails, $this->companyId);
    }
);

test(
    'getMemberIdFromDetails method returns the member id if the member mobile number is exists',
    function (): void {
        $memberDetails = [
            'mobile_number' => '1234567890',
        ];

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => $this->location->id,
            'first_name' => 'ABC',
            'mobile_number' => '123456789',
        ]);

        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('getMemberByMobileNumber')
                ->once()
                ->andReturn($member);
        });

        $memberService = resolve(MemberService::class);
        $memberService->getMemberIdFromDetails($memberDetails, $this->companyId);
    }
);

test(
    'getMemberIdFromDetails method returns the member id if the member email is exists',
    function (): void {
        $memberDetails = [
            'email' => 'test@gmail.com',
        ];

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => $this->location->id,
            'first_name' => 'ABC',
        ]);

        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('getMemberByEmails')
                ->once()
                ->andReturn($member);
        });

        $memberService = resolve(MemberService::class);
        $memberService->getMemberIdFromDetails($memberDetails, $this->companyId);
    }
);

test(
    'addNewMember method generates the member',
    function (): void {
        $memberDetails = [
            'email' => 'test@gmail.com',
            'mobile_number' => '1234567890',
            'first_name' => 'ABC',
        ];

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => $this->location->id,
            'first_name' => 'ABC',
        ]);

        $cashier = Cashier::factory()->make([
            'id' => 1,
            'employee_id' => 1,
            'cashier_group_id' => 1,
        ]);

        $voucherConfiguration = VoucherConfiguration::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $voucher = Voucher::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'voucher_configuration_id' => $voucherConfiguration->id,
            'member_id' => $member->id,
            'generated_by_sale_id' => 1,
        ]);

        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('generateUniqueCardNumber')
                ->once();
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($member);
            $mock->shouldReceive('updateWelcomeMemberVoucherDetails')
                ->once();
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

        $this->mock(VoucherConfigurationQueries::class, function ($mock) use ($voucherConfiguration): void {
            $mock->shouldReceive('getWelcomeMemberVoucherConfigurationByCompanyId')
                ->once()
                ->andReturn($voucherConfiguration);
        });

        $memberService = resolve(MemberService::class);
        $memberService->addNewMember(
            $cashier,
            $memberDetails,
            $this->companyId,
            $this->location->id,
            MemberChannelEnum::POS->value
        );
    }
);

test(
    'addNewMemberMembershipLoyaltyPointsAndWelcomeVouchers method return null when created_location_id not set',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => null,
            'first_name' => 'ABC',
        ]);

        $memberService = resolve(MemberService::class);
        $response = $memberService->addNewMemberMembershipLoyaltyPointsAndWelcomeVouchers($member);

        $this->assertNull($response);
    }
);

test(
    'addNewMemberMembershipLoyaltyPointsAndWelcomeVouchers method call same class method',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => 1,
            'first_name' => 'ABC',
        ]);

        $mock = $this->createPartialMock(
            MemberService::class,
            ['addNewMemberFreeMembership', 'addNewMemberFreeLoyaltyPoints', 'addNewMemberWelcomeVouchers']
        );

        $mock->expects($this->once())
            ->method('addNewMemberFreeMembership');

        $mock->expects($this->once())
            ->method('addNewMemberFreeLoyaltyPoints');

        $mock->expects($this->once())
            ->method('addNewMemberWelcomeVouchers');

        $mock->addNewMemberMembershipLoyaltyPointsAndWelcomeVouchers($member);
    }
);

test(
    'addNewMemberMembershipAndLoyaltyPoints method return null when created_location_id not set',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => null,
            'first_name' => 'ABC',
        ]);

        $memberService = resolve(MemberService::class);
        $response = $memberService->addNewMemberMembershipAndLoyaltyPoints($member);

        $this->assertNull($response);
    }
);

test(
    'addNewMemberMembershipAndLoyaltyPoints method call same class method',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => 1,
            'first_name' => 'ABC',
        ]);

        $mock = $this->createPartialMock(
            MemberService::class,
            ['addNewMemberFreeMembership', 'addNewMemberFreeLoyaltyPoints', 'addNewMemberWelcomeVouchers']
        );

        $mock->expects($this->once())
            ->method('addNewMemberFreeMembership');

        $mock->expects($this->once())
            ->method('addNewMemberFreeLoyaltyPoints');

        $mock->addNewMemberMembershipAndLoyaltyPoints($member);
    }
);

test(
    'addNewMemberWelcomeVouchers method call GenerateWelcomeMemberVouchersJob class',
    function (): void {
        Bus::fake();

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => null,
            'first_name' => 'ABC',
            'welcome_member_voucher_generated_at' => null,
        ]);

        $memberService = resolve(MemberService::class);
        $memberService->addNewMemberWelcomeVouchers($member);
        Bus::assertDispatched(GenerateWelcomeMemberVouchersJob::class);
    }
);

test(
    'addNewMemberWelcomeVouchers method return null when voucher all ready generated',
    function (): void {
        Queue::fake();

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => null,
            'first_name' => 'ABC',
            'welcome_member_voucher_generated_at' => '2020-01-01',
        ]);

        $memberService = resolve(MemberService::class);
        $response = $memberService->addNewMemberWelcomeVouchers($member);

        $this->assertNull($response);

        Queue::assertNotPushed(GenerateWelcomeMemberVouchersJob::class);
    }
);

test(
    'addNewMemberFreeMembership method return null when membership id not null',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => null,
            'first_name' => 'ABC',
            'welcome_member_voucher_generated_at' => '2020-01-01',
            'membership_id' => 1,
        ]);

        $memberService = resolve(MemberService::class);
        $response = $memberService->addNewMemberFreeMembership($member);

        $this->assertNull($response);
    }
);

test(
    'addNewMemberFreeMembership method call assignMembershipToMember method of MemberQueries class',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => null,
            'first_name' => 'ABC',
            'welcome_member_voucher_generated_at' => '2020-01-01',
            'membership_id' => null,
        ]);

        $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('assignMembershipToMember')
                ->once();
        });

        $memberService = resolve(MemberService::class);
        $memberService->addNewMemberFreeMembership($member);
    }
);

test(
    'addNewMemberFreeLoyaltyPoints method return null when created_location_id null',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => null,
            'first_name' => 'ABC',
            'welcome_member_voucher_generated_at' => '2020-01-01',
            'membership_id' => 1,
        ]);

        $memberService = resolve(MemberService::class);
        $response = $memberService->addNewMemberFreeLoyaltyPoints($member);

        $this->assertNull($response);
    }
);

test(
    'addNewMemberFreeLoyaltyPoints method return null when loyalty_points all ready added',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => 1,
            'first_name' => 'ABC',
            'welcome_member_voucher_generated_at' => '2020-01-01',
            'membership_id' => 1,
            'loyalty_points' => 1,
        ]);

        $memberService = resolve(MemberService::class);
        $response = $memberService->addNewMemberFreeLoyaltyPoints($member);

        $this->assertNull($response);
    }
);

test(
    'addNewMemberFreeLoyaltyPoints method call getNewMemberFreeLoyaltyPointsById method of CompanyQueries class',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => 1,
            'first_name' => 'ABC',
            'welcome_member_voucher_generated_at' => '2020-01-01',
            'membership_id' => 1,
            'loyalty_points' => 0,
        ]);

        $company = Company::factory()->make([
            'new_member_free_loyalty_points' => 0,
            'default_country_id' => 1,
        ]);

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getNewMemberFreeLoyaltyPointsById')
                ->once()
                ->andReturn($company);
        });

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
            'loyalty_point_expiration_days' => 100,
        ]);

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getLoyaltyPointExpirationDaysById')
                ->never()
                ->andReturn($location);
        });

        $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('addLoyaltyPoints')
                ->never();
        });

        $memberService = resolve(MemberService::class);
        $response = $memberService->addNewMemberFreeLoyaltyPoints($member);

        $this->assertNull($response);
    }
);

test(
    'addNewMemberFreeLoyaltyPoints method call addLoyaltyPoints method of MemberQueries class',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => 1,
            'first_name' => 'ABC',
            'welcome_member_voucher_generated_at' => '2020-01-01',
            'membership_id' => 1,
            'loyalty_points' => 0,
        ]);

        $mock = $this->createPartialMock(MemberService::class, ['addNewLoyaltyPointsAndLoyaltyPointsUpdate']);

        $mock->expects($this->once())
            ->method('addNewLoyaltyPointsAndLoyaltyPointsUpdate');

        $company = Company::factory()->make([
            'new_member_free_loyalty_points' => 100,
            'default_country_id' => 1,
        ]);

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getNewMemberFreeLoyaltyPointsById')
                ->once()
                ->andReturn($company);
        });

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
            'loyalty_point_expiration_days' => 100,
        ]);

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getLoyaltyPointExpirationDaysById')
                ->once()
                ->andReturn($location);
        });

        $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('addLoyaltyPoints')
                ->once();
        });

        $mock->addNewMemberFreeLoyaltyPoints($member);
    }
);

test(
    'addNewLoyaltyPointsAndLoyaltyPointsUpdate method call addNew method of LoyaltyPointQueries class',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => 1,
            'first_name' => 'ABC',
            'welcome_member_voucher_generated_at' => '2020-01-01',
            'membership_id' => 1,
            'loyalty_points' => 0,
        ]);

        $loyaltyPoint = LoyaltyPoint::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'sale_id' => 1,
            'loyalty_campaign_id' => 1,
            'points' => 100,
            'available_points' => 100,
            'minimum_spend_amount' => 0,
            'expiry_date' => now()->addDays(30)->format('Y-m-d H:i:s'),
        ]);

        $this->mock(LoyaltyPointQueries::class, function ($mock) use ($loyaltyPoint): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($loyaltyPoint);
        });

        $this->mock(LoyaltyPointUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $memberService = resolve(MemberService::class);
        $memberService->addNewLoyaltyPointsAndLoyaltyPointsUpdate($member, 100, 10);
    }
);

test(
    'checkRequiredMemberColumnsForEcommerce method throws an exception when the mobile number does not specify.',
    function (): void {
        $memberDetails = [
            'first_name' => 'abc',
            'mobile_number' => '',
        ];

        $memberService = resolve(MemberService::class);
        $memberService->checkRequiredMemberColumnsForEcommerce($memberDetails, $this->companyId);
    }
)->throws(HttpException::class, 'mobile number is required');

test(
    'checkRequiredMemberColumnsForEcommerce method throws an exception when the email does not specify.',
    function (): void {
        $memberDetails = [
            'first_name' => 'abc',
            'email' => '',
        ];

        $memberService = resolve(MemberService::class);
        $memberService->checkRequiredMemberColumnsForEcommerce($memberDetails, $this->companyId);
    }
)->throws(HttpException::class);

test(
    'checkRequiredMemberColumnsForEcommerce method throws an exception when the card_number does not specify.',
    function (): void {
        $memberDetails = [
            'first_name' => 'abc',
            'card_number' => '',
        ];

        $memberService = resolve(MemberService::class);
        $memberService->checkRequiredMemberColumnsForEcommerce($memberDetails, $this->companyId);
    }
)->throws(HttpException::class);

test(
    'checkRequiredMemberColumnsForEcommerce method throws an exception when the first_name does not specify.',
    function (): void {
        $memberDetails = [
            'first_name' => '',
        ];

        $memberService = resolve(MemberService::class);
        $memberService->checkRequiredMemberColumnsForEcommerce($memberDetails, $this->companyId);
    }
)->throws(HttpException::class, 'First name is required');

test(
    'addNewMemberFromEcommerceOrder method generates the member',
    function (): void {
        $memberDetails = [
            'email' => 'test@gmail.com',
            'mobile_number' => '1234567890',
            'first_name' => 'ABC',
        ];

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => $this->location->id,
            'first_name' => 'ABC',
        ]);

        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('generateUniqueCardNumber')
                ->once();
            $mock->shouldReceive('addNewFromEcommerceOrder')
                ->once()
                ->andReturn($member);
        });

        $memberService = resolve(MemberService::class);
        $memberService->addNewMemberFromEcommerceOrder($memberDetails, $this->companyId, $this->location->id);
    }
);

test(
    'exportMemberWithJob method call and return exceeds_limit to false',
    function (): void {
        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        Config::set('app.excel.export.job_limit', 1000);

        $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('getMembersExportCount')
                ->once()
                ->andReturn(100);
        });

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => null,
            'store_ids' => null,
            'membership_ids' => null,
            'member_group_ids' => null,
            'date_range' => null,
            'status' => null,
        ];

        $memberService = resolve(MemberService::class);
        $response = $memberService->exportMemberWithJob($admin, $filterData, $this->companyId);

        expect($response)->toHaveKey('exceeds_limit', false);
    }
);

test(
    'exportMemberWithJob method call and return exceeds_limit to true',
    function (): void {
        Queue::fake();

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        Config::set('app.excel.export.job_limit', 1);

        $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('getMembersExportCount')
                ->once()
                ->andReturn(100);
        });

        $exportRecord = ExportRecord::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_by_type' => ModelMapping::ADMIN->name,
            'created_by_id' => 1,
        ]);

        $this->mock(ExportRecordQueries::class, function ($mock) use ($exportRecord): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($exportRecord);
        });

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => null,
            'store_ids' => null,
            'membership_ids' => null,
            'member_group_ids' => null,
            'date_range' => null,
            'status' => null,
        ];

        $memberService = resolve(MemberService::class);
        $memberService->exportMemberWithJob($admin, $filterData, $this->companyId);

        Queue::assertPushed(ExportToExcelJob::class);
    }
);

test(
    'getMemberPreferencesRecords method call and return proper response',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => $this->location->id,
            'first_name' => 'ABC',
        ]);

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('getPreferredItems')
                ->once()
                ->andReturn(collect());
        });

        $memberService = resolve(MemberService::class);
        $memberService->getMemberPreferencesRecords($member->id, $this->companyId, $this->location->id);
    }
);

test(
    'getMemberPreferencesRecordsForApp method call and return proper response',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => $this->location->id,
            'first_name' => 'ABC',
        ]);

        $this->mock(SaleItemQueries::class, function ($mock): void {
            $mock->shouldReceive('getPreferredItems')
                ->once()
                ->andReturn(collect());
        });

        $memberService = resolve(MemberService::class);
        $memberService->getMemberPreferencesRecordsForApp($member->id, $this->companyId, $this->location->id);
    }
);
