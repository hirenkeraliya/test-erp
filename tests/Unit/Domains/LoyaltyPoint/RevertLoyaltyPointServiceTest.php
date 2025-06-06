<?php

declare(strict_types=1);

use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPoint\Services\RevertLoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\MemberQueries;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\SaleItem;

beforeEach(function (): void {
    $this->revertLoyaltyPointService = new RevertLoyaltyPointService();
});

test(
    'increaseLoyaltyPoints method calls the LoyaltyPointQueries class methods as expected',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'loyalty_points' => 100,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $saleItem = SaleItem::factory()->make([
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => 1,
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

        $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('increaseLoyaltyPoints')
                ->once();
        });
        $this->revertLoyaltyPointService->increaseLoyaltyPoints(
            $member,
            $saleItem,
            100,
            now()->format('Y-m-d H:i:s'),
            null
        );
    }
);

test(
    'updateUserLoyaltyPoints method calls the decreaseLoyaltyPoints method of MemberQueries class when sale user is member',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'loyalty_points' => 100,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('increaseLoyaltyPoints')
                ->once();
        });

        $this->revertLoyaltyPointService->updateUserLoyaltyPoints($member, 50);
    }
);
