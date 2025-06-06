<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LoyaltyCampaign;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\Order;
use App\Models\Sale;
use App\Models\StoreManager;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->memberA = Member::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'member_one',
        'created_location_id' => $this->location->id,
        'loyalty_points' => 157,
    ]);

    $this->loyaltyPointA = LoyaltyPoint::factory()->create([
        'points' => 200,
        'available_points' => 200,
        'minimum_spend_amount' => 100,
        'expiry_date' => now()->format('Y-m-d'),
        'member_id' => $this->memberA->id,
    ]);

    $this->loyaltyPointB = LoyaltyPoint::factory()->create([
        'points' => 100,
        'available_points' => 100,
        'minimum_spend_amount' => 100,
        'expiry_date' => now()->addDay()->format('Y-m-d'),
        'member_id' => $this->memberA->id,
    ]);

    $this->loyaltyPointQueries = new LoyaltyPointQueries();
});

test('A loyalty point can be fetched', function (): void {
    $response = $this->loyaltyPointQueries->getByUserSortByExpiryDate($this->memberA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->loyaltyPointA->id)
        ->toHaveKey('member_id', $this->loyaltyPointA->member_id)
        ->toHaveKey('points', $this->loyaltyPointA->points)
        ->toHaveKey('available_points', $this->loyaltyPointA->available_points)
        ->toHaveKey('minimum_spend_amount', $this->loyaltyPointA->minimum_spend_amount)
        ->toHaveKey('expiry_date', $this->loyaltyPointA->expiry_date);
});

test('decreasePoints method decrease points', function (): void {
    $this->loyaltyPointQueries->decreasePoints($this->loyaltyPointA, 50);
    $this->assertDatabaseHas('loyalty_points', [
        'id' => $this->loyaltyPointA->id,
        'available_points' => 150,
    ]);
});

test('getLoyaltyPointsDueForExpiry method returns expiry loyalty points list as expected.', function (): void {
    $this->loyaltyPointA->expiry_date = now()->subDay()->format('Y-m-d');
    $this->loyaltyPointA->save();
    $response = $this->loyaltyPointQueries->getLoyaltyPointsDueForExpiry(Carbon::now()->format('Y-m-d'));

    expect($response->first()->toArray())
        ->toHaveKey('member_id', $this->loyaltyPointA->member_id)
        ->toHaveKey('expiry_date', $this->loyaltyPointA->expiry_date)
        ->toHaveKey('points', $this->loyaltyPointA->points)
        ->toHaveKey('available_points', $this->loyaltyPointA->available_points)
        ->toHaveKey('minimum_spend_amount', $this->loyaltyPointA->minimum_spend_amount)
        ->toHaveKey('member');
});

test('decreaseLoyaltyPointsToZero method decrease loyalty points value by 0 as expected.', function (): void {
    $this->loyaltyPointQueries->decreaseLoyaltyPointsToZero($this->loyaltyPointA);

    $this->assertDatabaseHas('loyalty_points', [
        'member_id' => $this->loyaltyPointA->member_id,
        'expiry_date' => $this->loyaltyPointA->expiry_date,
        'available_points' => 0,
    ]);
});

test('New loyalty point can be added', function (): void {
    $loyaltyCampaign = LoyaltyCampaign::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Loyalty Campaign',
    ]);

    $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);
    $member = Member::factory()->create();
    $sale = Sale::factory()->create([
        'member_id' => $member->id,
    ]);

    $record = [
        'member_id' => $member->id,
        'sale_id' => $sale->id,
        'expiry_date' => null,
        'loyalty_campaign_id' => $loyaltyCampaign->id,
        'points' => 10,
        'available_points' => 10,
        'minimum_spend_amount' => 100.00,
    ];

    $loyaltyPointQueries->addNew($record);

    $this->assertDatabaseHas('loyalty_points', $record);
});

it('getLoyaltyPointForGivenSale method returns the loyalty points details as expected.', function (): void {
    $counterId = Counter::factory()->create([
        'location_id' => $this->location->id,
    ])->id;

    $counterUpdateId = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
    ])->id;

    $saleId = Sale::factory()->create([
        'counter_update_id' => $counterUpdateId,
    ])->id;

    $loyaltyPoint = LoyaltyPoint::factory()->create([
        'points' => 200,
        'available_points' => 200,
        'minimum_spend_amount' => 100,
        'expiry_date' => now()->format('Y-m-d'),
        'member_id' => $this->memberA->id,
        'sale_id' => $saleId,
    ]);

    $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);
    $response = $loyaltyPointQueries->getLoyaltyPointForGivenSale($saleId);

    expect($response->first()->toArray())
            ->toHaveKey('member_id', $loyaltyPoint->member_id)
            ->toHaveKey('sale_id', $loyaltyPoint->sale_id)
            ->toHaveKey('points', $loyaltyPoint->points)
            ->toHaveKey('available_points', $loyaltyPoint->available_points);
});

test('setNewAvailablePointsAndPoints method decrease points and available points', function (): void {
    $this->loyaltyPointQueries->setNewAvailablePointsAndPoints($this->loyaltyPointA, -100, 100);
    $this->assertDatabaseHas('loyalty_points', [
        'id' => $this->loyaltyPointA->id,
        'points' => '-100',
        'available_points' => 100,
    ]);
});

test(
    'the updateMember method update the loyalty point queries member id to new member id',
    function (): void {
        $member = Member::factory()->create();

        $loyaltyPoint = LoyaltyPoint::factory()->create();

        $this->assertDatabaseHas(LoyaltyPoint::class, [
            'id' => $loyaltyPoint->getKey(),
            'member_id' => $loyaltyPoint->member_id,
        ]);

        $this->loyaltyPointQueries->updateMember($loyaltyPoint->member_id, $member->getKey());

        $this->assertDatabaseHas(LoyaltyPoint::class, [
            'id' => $loyaltyPoint->getKey(),
            'member_id' => $member->getKey(),
        ]);
    }
);

it('getLoyaltyPointForGivenOrder method returns the loyalty points details as expected.', function (): void {
    $company = Company::factory()->create([
        'name' => 'Order Test',
    ]);

    $employee = Employee::factory()->create([
        'company_id' => $company->getKey(),
    ]);

    $storeManager = StoreManager::factory()->create([
        'employee_id' => $employee->getKey(),
    ]);

    $orderId = Order::factory()->create([
        'store_manager_id' => $storeManager->getKey(),
        'location_id' => $this->location->id,
        'member_id' => $this->memberA->id,
        'order_return_id' => null,
        'cancel_order_reason_id' => null,
    ])->id;

    $loyaltyPoint = LoyaltyPoint::factory()->create([
        'points' => 200,
        'available_points' => 200,
        'minimum_spend_amount' => 100,
        'expiry_date' => now()->format('Y-m-d'),
        'member_id' => $this->memberA->id,
        'order_id' => $orderId,
    ]);

    $loyaltyPointQueries = resolve(LoyaltyPointQueries::class);
    $response = $loyaltyPointQueries->getLoyaltyPointForGivenOrder($orderId);

    expect($response->first()->toArray())
        ->toHaveKey('member_id', $loyaltyPoint->member_id)
        ->toHaveKey('order_id', $loyaltyPoint->order_id)
        ->toHaveKey('points', $loyaltyPoint->points)
        ->toHaveKey('available_points', $loyaltyPoint->available_points);
});
