<?php

declare(strict_types=1);

use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPointUpdate\Jobs\LoyaltyPointExpirationJob;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\MemberQueries;
use App\Models\Employee;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use Carbon\Carbon;

test(
    'LoyaltyPointExpirationJob job calls respective queries class as expected',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
            'loyalty_points' => 100,
        ]);

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'designation_id' => 1,
            'loyalty_points' => 200,
        ]);

        $loyaltyPointA = LoyaltyPoint::factory()->make([
            'id' => 1,
            'member_id' => $member->id,
            'sale_id' => 1,
            'loyalty_campaign_id' => 1,
            'expiry_date' => Carbon::yesterday()->format('Y-m-d'),
            'available_points' => 50,
        ]);

        $loyaltyPointA->user = $member;

        $loyaltyPointB = LoyaltyPoint::factory()->make([
            'id' => 1,
            'member_id' => $employee->id,
            'sale_id' => 1,
            'loyalty_campaign_id' => 1,
            'expiry_date' => Carbon::yesterday()->format('Y-m-d'),
            'available_points' => 100,
        ]);

        $loyaltyPointB->user = $employee;

        $loyaltyPoints = collect([$loyaltyPointA, $loyaltyPointB]);

        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('getLoyaltyPointsById')
                ->times(2)
                ->andReturn($member);
            $mock->shouldReceive('decreaseExpiredLoyaltyPoints')
                ->times(2);
        });

        $this->mock(LoyaltyPointUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->times(2);
        });

        $this->mock(LoyaltyPointQueries::class, function ($mock) use ($loyaltyPoints): void {
            $mock->shouldReceive('getLoyaltyPointsDueForExpiry')
                ->once()
                ->andReturn($loyaltyPoints);
            $mock->shouldReceive('decreaseLoyaltyPointsToZero')
                ->times(2);
        });

        LoyaltyPointExpirationJob::dispatch()->onQueue(config('horizon.default_queue_name'));
    }
);
