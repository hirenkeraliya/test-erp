<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LoyaltyPointUpdate;
use App\Models\Member;
use App\Models\Sale;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->member = Member::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'member_one',
        'created_location_id' => $this->location->id,
        'loyalty_points' => 157,
    ]);

    $this->loyaltyPointUpdateQueries = new LoyaltyPointUpdateQueries();
});

test('New loyalty point updates can be added', function (): void {
    $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
    $sale = Sale::factory()->create([
        'member_id' => $this->member->id,
    ]);

    $record = [
        'member_id' => $this->member->id,
        'affected_by_id' => $sale->id,
        'affected_by_type' => ModelMapping::SALE->name,
        'type_id' => LoyaltyPointUpdateTypes::SALE->value,
        'points' => 10,
        'closing_loyalty_points_balance' => 10,
        'happened_at' => Carbon::now()->format('Y-m-d H:i:s'),
    ];

    $loyaltyPointUpdateQueries->addNew($record);

    $this->assertDatabaseHas('loyalty_point_updates', $record);
});

test(
    'the getPaginatedTransactionListForMemberApi method returns the paginated loyalty points update list',
    function (): void {
        $member = Member::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $loyaltyPointsUpdate = LoyaltyPointUpdate::factory()->create([
            'member_id' => $member->id,
            'affected_by_id' => $member->id,
            'affected_by_type' => ModelMapping::MEMBER->name,
        ]);

        $filterData = [
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => 15,
        ];

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $response = $loyaltyPointUpdateQueries->getPaginatedTransactionListForMemberApi($filterData, $member->id);

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $loyaltyPointsUpdate->id)
            ->toHaveKey('points', $loyaltyPointsUpdate->points);
    }
);

test(
    'The getTotalPointsRewarded method should correctly calculate the sum of rewarded points',
    function (): void {
        $memberId = Member::factory()->create()->id;
        LoyaltyPointUpdate::factory(2)->create([
            'member_id' => $memberId,
            'points' => 100,
        ]);
        LoyaltyPointUpdate::factory(2)->create([
            'member_id' => $memberId,
            'points' => -100,
        ]);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $response = $loyaltyPointUpdateQueries->getTotalPointRewarded($memberId);
        expect($response)->toBe(200);
    }
);

test(
    'The getTotalPointsRedeemed method should accurately calculate the total redeemed points',
    function (): void {
        $memberId = Member::factory()->create()->id;
        LoyaltyPointUpdate::factory(2)->create([
            'member_id' => $memberId,
            'points' => 100,
        ]);
        LoyaltyPointUpdate::factory(2)->create([
            'member_id' => $memberId,
            'points' => -100,
        ]);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $response = $loyaltyPointUpdateQueries->getTotalPointsRedeemed($memberId);
        expect($response)->toBe(-200);
    }
);

test(
    'the getUsedLoyaltyPoint method returns the loyalty points update list',
    function (): void {
        $member = Member::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $loyaltyPointsUpdate = LoyaltyPointUpdate::factory()->create([
            'member_id' => $member->id,
            'affected_by_id' => $member->id,
            'affected_by_type' => ModelMapping::MEMBER->name,
        ]);

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $response = $loyaltyPointUpdateQueries->getUsedLoyaltyPoint(
            $member->id,
            ModelMapping::MEMBER->name,
            $loyaltyPointsUpdate->type_id
        );

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $loyaltyPointsUpdate->id)
            ->toHaveKey('points', $loyaltyPointsUpdate->points);
    }
);

test(
    'The getTotalPointRewardedForEmployee method should correctly calculate the sum of rewarded points',
    function (): void {
        $employeeId = Employee::factory()->create()->id;
        $member = Member::factory()->create([
            'employee_id' => $employeeId,
        ]);
        LoyaltyPointUpdate::factory(2)->create([
            'member_id' => $member->id,
            'points' => 100,
        ]);
        LoyaltyPointUpdate::factory(2)->create([
            'member_id' => $member->id,
            'points' => -100,
        ]);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $response = $loyaltyPointUpdateQueries->getTotalPointRewardedForEmployee($employeeId);
        expect($response)->toBe(200);
    }
);

test(
    'The getTotalPointsRedeemedForEmployee method should accurately calculate the total redeemed points',
    function (): void {
        $employeeId = Employee::factory()->create()->id;
        $member = Member::factory()->create([
            'employee_id' => $employeeId,
        ]);
        LoyaltyPointUpdate::factory(2)->create([
            'member_id' => $member->id,
            'points' => 100,
        ]);
        LoyaltyPointUpdate::factory(2)->create([
            'member_id' => $member->id,
            'points' => -100,
        ]);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $response = $loyaltyPointUpdateQueries->getTotalPointsRedeemedForEmployee($employeeId);
        expect($response)->toBe(-200);
    }
);

test(
    'the getMemberLoyaltyPointDetails method returns the loyalty points details',
    function (): void {
        $member = Member::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $loyaltyPointsUpdate = LoyaltyPointUpdate::factory()->create([
            'member_id' => $member->id,
            'affected_by_id' => $member->id,
            'affected_by_type' => ModelMapping::MEMBER->name,
        ]);

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $response = $loyaltyPointUpdateQueries->getMemberLoyaltyPointDetails($member->id);

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $loyaltyPointsUpdate->id)
            ->toHaveKey('points', $loyaltyPointsUpdate->points);
    }
);

test('the getLoyaltyPointDetailsForEcommerceSyncById method returns the loyalty points details',
    function (): void {
        $member = Member::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $loyaltyPointsUpdate = LoyaltyPointUpdate::factory()->create([
            'id' => 1,
            'member_id' => $member->id,
            'affected_by_id' => $member->id,
            'affected_by_type' => ModelMapping::MEMBER->name,
        ]);

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $response = $loyaltyPointUpdateQueries->getLoyaltyPointDetailsForEcommerceSyncByIdAndCompanyId(
            $loyaltyPointsUpdate->id,
            $this->company->id
        );

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('id', $loyaltyPointsUpdate->id)
            ->toHaveKey('points', $loyaltyPointsUpdate->points);
    }
);

test(
    'The getTotalPointsRedeemedForJob method should accurately calculate the total redeemed points',
    function (): void {
        $member = Member::factory()->create([
            'company_id' => $this->company->id,
        ]);

        LoyaltyPointUpdate::factory(2)->create([
            'member_id' => $member->id,
            'points' => -100,
            'type_id' => LoyaltyPointUpdateTypes::USED->value,
        ]);

        LoyaltyPointUpdate::factory(2)->create([
            'member_id' => $member->id,
            'type_id' => LoyaltyPointUpdateTypes::EXPIRED->value,
            'points' => -100,
        ]);

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $response = $loyaltyPointUpdateQueries->getTotalPointsRedeemedForJob($member->id);

        expect($response)->toBe(-200);
    }
);

test(
    'The getTotalPointsExpired method should accurately calculate the total redeemed points',
    function (): void {
        $member = Member::factory()->create([
            'company_id' => $this->company->id,
        ]);

        LoyaltyPointUpdate::factory(2)->create([
            'member_id' => $member->id,
            'points' => -100,
            'type_id' => LoyaltyPointUpdateTypes::USED->value,
        ]);

        LoyaltyPointUpdate::factory(2)->create([
            'member_id' => $member->id,
            'type_id' => LoyaltyPointUpdateTypes::EXPIRED->value,
            'points' => -100,
        ]);

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $response = $loyaltyPointUpdateQueries->getTotalPointsExpired($member->id, ModelMapping::MEMBER->name);

        expect($response)->toBe(-200);
    }
);

test(
    'The getTotalPointsRedeemedForEmployeeForJob method should accurately calculate the total redeemed points',
    function (): void {
        $employeeId = Employee::factory()->create()->id;

        $member = Member::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employeeId,
        ]);

        LoyaltyPointUpdate::factory(2)->create([
            'member_id' => $member->id,
            'points' => -100,
            'type_id' => LoyaltyPointUpdateTypes::USED->value,
        ]);

        LoyaltyPointUpdate::factory(2)->create([
            'member_id' => $member->id,
            'type_id' => LoyaltyPointUpdateTypes::EXPIRED->value,
            'points' => -100,
        ]);

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $response = $loyaltyPointUpdateQueries->getTotalPointsRedeemedForEmployeeForJob($employeeId);

        expect($response)->toBe(-200);
    }
);

test(
    'the updateMember method update the loyalty point update queries member id to new member id',
    function (): void {
        $member = Member::factory()->create();

        $loyaltyPointUpdate = LoyaltyPointUpdate::factory()->create();

        $this->assertDatabaseHas(LoyaltyPointUpdate::class, [
            'id' => $loyaltyPointUpdate->getKey(),
            'member_id' => $loyaltyPointUpdate->member_id,
        ]);

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $loyaltyPointUpdateQueries->updateMember($loyaltyPointUpdate->member_id, $member->getKey());

        $this->assertDatabaseHas(LoyaltyPointUpdate::class, [
            'id' => $loyaltyPointUpdate->getKey(),
            'member_id' => $member->getKey(),
        ]);
    }
);

test(
    'the getLoyaltyPointUpdates method returns the loyalty point update of member id',
    function (): void {
        $member = Member::factory()->create();
        $loyaltyPointUpdate = LoyaltyPointUpdate::factory()->create([
            'member_id' => $member->getKey(),
        ]);
        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $response = $loyaltyPointUpdateQueries->getLoyaltyPointUpdates($loyaltyPointUpdate->member_id);
        expect($response->first()->toArray())->toHaveKeys(
            ['id', 'member_id', 'points', 'closing_loyalty_points_balance']
        );
    }
);

test(
    'the updateClosingBalance method updates the closing balance',
    function (): void {
        $loyaltyPointUpdate = LoyaltyPointUpdate::factory()->create([
            'closing_loyalty_points_balance' => 0,
        ]);

        $this->assertDatabaseHas(LoyaltyPointUpdate::class, [
            'id' => $loyaltyPointUpdate->getKey(),
            'closing_loyalty_points_balance' => 0,
        ]);

        $loyaltyPointUpdateQueries = resolve(LoyaltyPointUpdateQueries::class);
        $loyaltyPointUpdateQueries->updateClosingBalance($loyaltyPointUpdate, 12);

        $this->assertDatabaseHas(LoyaltyPointUpdate::class, [
            'id' => $loyaltyPointUpdate->getKey(),
            'closing_loyalty_points_balance' => 12,
        ]);
    }
);
