<?php

declare(strict_types=1);

use App\Domains\Company\Enums\LocationAssignmentTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Member\Jobs\NewMemberBenefitsJob;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Services\MemberService;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use Illuminate\Support\Facades\Queue;

test(
    'NewMemberBenefitsJob method cell getByIdForNewMemberBenefitsJob of MemberQueries',
    function (): void {
        Queue::fake()->except([NewMemberBenefitsJob::class]);

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('getByIdForNewMemberBenefitsJob')
                ->once()
                ->andReturn($member);
        });

        NewMemberBenefitsJob::dispatch($member->id, 1)->onQueue(config('horizon.default_queue_name'));
    }
);

test(
    'NewMemberBenefitsJob return null when location_assignment_type is not Based On First Purchase',
    function (): void {
        Queue::fake()->except([NewMemberBenefitsJob::class]);

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => null,
        ]);

        $member->company = Company::factory()->make([
            'location_assignment_type' => LocationAssignmentTypes::MANUAL_ASSIGNMENT->value,
            'default_country_id' => 1,
        ]);

        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('getByIdForNewMemberBenefitsJob')
                ->once()
                ->andReturn($member);
        });

        NewMemberBenefitsJob::dispatch($member->id, 1)->onQueue(config('horizon.default_queue_name'));
    }
);

test(
    'NewMemberBenefitsJob call respective method of class',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'loyalty_point_expiration_days' => 10,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => null,
            'membership_id' => null,
            'welcome_member_voucher_generated_at' => null,
            'loyalty_points' => null,
        ]);

        $member->company = Company::factory()->make([
            'location_assignment_type' => LocationAssignmentTypes::BASED_ON_FIRST_PURCHASE->value,
            'new_member_free_loyalty_points' => 10,
            'default_country_id' => 1,
        ]);

        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('getByIdForNewMemberBenefitsJob')
                ->once()
                ->andReturn($member);
            $mock->shouldReceive('storeUpdate')
                ->once();
        });

        $this->mock(MemberService::class, function ($mock) use ($member): void {
            $mock->shouldReceive('addNewMemberMembershipLoyaltyPointsAndWelcomeVouchers')
                ->once()
                ->andReturn($member);
        });

        NewMemberBenefitsJob::dispatch($member->id, 1)->onQueue(config('horizon.default_queue_name'));
    }
);
