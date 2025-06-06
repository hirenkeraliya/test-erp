<?php

declare(strict_types=1);

use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Models\LoyaltyPointUpdate;
use App\Models\Member;
use App\Models\SaleItem;

test('getUserDetails method call same class method', function (): void {
    $mock = $this->createPartialMock(
        UserDataPreparer::class,
        ['getEarnedPoints', 'getRedeemPoints', 'getRedeemPointsToProduct']
    );

    $mock->expects($this->once())
        ->method('getEarnedPoints')
        ->will($this->returnValue(500));

    $mock->expects($this->once())
        ->method('getRedeemPoints')
        ->will($this->returnValue(400));

    $mock->expects($this->once())
        ->method('getRedeemPointsToProduct')
        ->will($this->returnValue(50));

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'first_name' => 'Test',
        'last_name' => 'Test',
        'email' => 'test@gmial.com',
        'mobile_number' => '123456',
        'loyalty_points' => 200,
    ]);

    $loyaltyPointUpdates = collect([]);
    $saleItems = collect([]);

    $response = $mock->getUserDetails($member, $loyaltyPointUpdates, $saleItems);

    expect($response)
        ->toHaveKey('first_name', 'Test')
        ->toHaveKey('last_name', 'Test')
        ->toHaveKey('email', 'test@gmial.com')
        ->toHaveKey('mobile_number', '123456')
        ->toHaveKey('previous_points', 150)
        ->toHaveKey('earned_points', 500)
        ->toHaveKey('redeem_points_to_product', 50)
        ->toHaveKey('redeem_points', 400)
        ->toHaveKey('current_sale_points', 50)
        ->toHaveKey('accumulated_points', 200);
});

test('getRedeemPointsToProduct method return product redeem points', function (): void {
    $saleItem = SaleItem::factory()->make([
        'id' => 1,
        'sale_id' => 1,
        'product_id' => 1,
        'derivative_id' => 1,
        'loyalty_points' => 200,
    ]);

    $loyaltyPointUpdates = [];

    $loyaltyPointUpdates[] = LoyaltyPointUpdate::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'affected_by_id' => 1,
        'points' => 200,
    ]);

    $loyaltyPointUpdates[] = LoyaltyPointUpdate::factory()->make([
        'id' => 2,
        'member_id' => 1,
        'affected_by_id' => 1,
        'points' => -45,
    ]);

    $saleItem->loyaltyPointUpdates = collect($loyaltyPointUpdates);

    $saleItems = collect([$saleItem]);

    $userDataPreparer = resolve(UserDataPreparer::class);
    $response = $userDataPreparer->getRedeemPointsToProduct($saleItems);
    $this->assertEquals($response, 155);
});

test('getEarnedPoints method return product redeem points', function (): void {
    $loyaltyPointUpdates = [];

    $loyaltyPointUpdates[] = LoyaltyPointUpdate::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'affected_by_id' => 1,
        'points' => 200,
    ]);

    $loyaltyPointUpdates[] = LoyaltyPointUpdate::factory()->make([
        'id' => 2,
        'member_id' => 1,
        'affected_by_id' => 1,
        'points' => -45,
    ]);

    $userDataPreparer = resolve(UserDataPreparer::class);
    $response = $userDataPreparer->getEarnedPoints(collect($loyaltyPointUpdates));
    $this->assertEquals($response, 200);
});

test('getRedeemPoints method return product redeem points', function (): void {
    $loyaltyPointUpdates = [];

    $loyaltyPointUpdates[] = LoyaltyPointUpdate::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'affected_by_id' => 1,
        'points' => 200,
    ]);

    $loyaltyPointUpdates[] = LoyaltyPointUpdate::factory()->make([
        'id' => 2,
        'member_id' => 1,
        'affected_by_id' => 1,
        'points' => -45,
    ]);

    $userDataPreparer = resolve(UserDataPreparer::class);
    $response = $userDataPreparer->getRedeemPoints(collect($loyaltyPointUpdates));
    $this->assertEquals($response, 45);
});

test('getBasicUserDetails method return member details', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
        'first_name' => 'Test',
        'last_name' => 'Test',
        'email' => 'test@gmial.com',
        'mobile_number' => '123456',
        'employee_id' => 1,
        'loyalty_points' => 200,
        'card_number' => '123456789',
    ]);
    $userDataPreparer = resolve(UserDataPreparer::class);
    $response = $userDataPreparer->getBasicUserDetails($member);
    expect($response)
        ->toHaveKey('first_name', 'Test')
        ->toHaveKey('last_name', 'Test')
        ->toHaveKey('email', 'test@gmial.com')
        ->toHaveKey('mobile_number', '123456')
        ->toHaveKey('card_number', '123456789')
        ->toHaveKey('employee_id', 1);
});
