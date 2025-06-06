<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\DataObjects\UpdateLoyaltyPointData;
use App\Domains\Member\MemberQueries;
use App\Models\Admin;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointUpdate;
use App\Models\Member;
use App\Models\Sale;

beforeEach(function (): void {
    $this->loyaltyPointService = new LoyaltyPointService();
});

test('decreaseLoyaltyPoints method calls the same class methods as expected', function (): void {
    $sale = new Sale();
    $sale->member = new Member([
        'loyalty_points' => 100,
    ]);

    $mock = $this->createPartialMock(
        LoyaltyPointService::class,
        ['updateUserLoyaltyPoints', 'decreaseLoyaltyPointsByFirstExpiryFirstOut']
    );

    $mock->expects($this->once())
        ->method('updateUserLoyaltyPoints');

    $mock->expects($this->once())
        ->method('decreaseLoyaltyPointsByFirstExpiryFirstOut');

    $mock->decreaseLoyaltyPoints(
        $sale->member,
        50,
        LoyaltyPointUpdateTypes::USED->value,
        1,
        ModelMapping::SALE->name,
        '2022-01-01 10:10:10'
    );
});

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
            $mock->shouldReceive('decreaseLoyaltyPoints')
                ->once();
        });

        $this->loyaltyPointService->updateUserLoyaltyPoints($member, 50);
    }
);

test(
    'decreaseLoyaltyPointsByFirstExpiryFirstOut method calls the getByUserSortByExpiryDate method of LoyaltyPointQueries class',
    function (): void {
        $loyaltyPoints = [];
        $member = Member::factory()->make([
            'id' => 1,
            'created_location_id' => 1,
            'company_id' => 1,
            'loyalty_points' => 100,
        ]);

        $loyaltyPoints[] = new LoyaltyPoint([
            'available_points' => 150,
        ]);

        $loyaltyPoints[] = new LoyaltyPoint([
            'available_points' => 100,
        ]);

        $this->mock(LoyaltyPointQueries::class, function ($mock) use ($loyaltyPoints): void {
            $mock->shouldReceive('getByUserSortByExpiryDate')
                ->once()
                ->andReturn(collect($loyaltyPoints));
            $mock->shouldReceive('decreasePoints')
                ->times(2);
        });

        $this->mock(LoyaltyPointUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->times(2);
        });

        $this->loyaltyPointService->decreaseLoyaltyPointsByFirstExpiryFirstOut(
            $member,
            100,
            200,
            LoyaltyPointUpdateTypes::USED->value,
            1,
            ModelMapping::SALE->name,
            '2022-01-01 10:10:10'
        );
    }
);

test(
    'updateLoyaltyPointsForAdmin method calls the same class methods as expected when member loyalty points is less then update loyalty points',
    function (): void {
        $member = new Member([
            'loyalty_points' => 100,
        ]);

        $admin = new Admin();

        $updateLoyaltyPointData = new UpdateLoyaltyPointData(110, 'Test');

        $mock = $this->createPartialMock(
            LoyaltyPointService::class,
            ['increaseLoyaltyPointsForAdmin', 'decreaseLoyaltyPoints']
        );

        $mock->expects($this->once())
            ->method('increaseLoyaltyPointsForAdmin');

        $mock->expects($this->any())
            ->method('decreaseLoyaltyPoints');

        $mock->updateLoyaltyPointsForAdmin($member, $admin, $updateLoyaltyPointData);
    }
);

test(
    'updateLoyaltyPointsForAdmin method calls the same class methods as expected when member loyalty points is more then update loyalty points',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'loyalty_points' => 100,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 100,
        ]);

        $updateLoyaltyPointData = new UpdateLoyaltyPointData(10, 'Test');

        $mock = $this->createPartialMock(
            LoyaltyPointService::class,
            ['increaseLoyaltyPointsForAdmin', 'decreaseLoyaltyPoints']
        );

        $mock->expects($this->any())
            ->method('increaseLoyaltyPointsForAdmin');

        $mock->expects($this->once())
            ->method('decreaseLoyaltyPoints');

        $mock->updateLoyaltyPointsForAdmin($member, $admin, $updateLoyaltyPointData);
    }
);

test(
    'increaseLoyaltyPointsForAdmin method calls the LoyaltyPointQueries class methods as expected',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'loyalty_points' => 100,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $admin = new Admin();

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

        $this->loyaltyPointService->increaseLoyaltyPointsForAdmin($member, $admin, 100, 'Test', 100);
    }
);

test(
    'the mergeLoyaltyPoints method updates the loyalty points and loyalty points updates',
    function (): void {
        $loyaltyPointUpdates = LoyaltyPointUpdate::factory(2)->make([
            'id' => 1,
            'member_id' => 1,
            'affected_by_id' => 1,
        ]);

        $this->mock(LoyaltyPointQueries::class, function ($mock): void {
            $mock->shouldReceive('updateMember')
                ->once();
        });

        $this->mock(LoyaltyPointUpdateQueries::class, function ($mock) use ($loyaltyPointUpdates): void {
            $mock->shouldReceive('updateMember')
                ->once();
            $mock->shouldReceive('getLoyaltyPointUpdates')
                ->once()
                ->andReturn($loyaltyPointUpdates);
            $mock->shouldReceive('updateClosingBalance')
                ->twice();
        });

        $this->loyaltyPointService->mergeLoyaltyPoints(1, 1);
    }
);
