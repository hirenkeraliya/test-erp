<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\Member\Enums\Status;
use App\Domains\Member\MemberQueries;
use App\Domains\Order\DataObjects\OrderECommerceData;
use App\Domains\Order\Services\CheckOrderEcommerceDetailsService;
use App\Domains\Order\Services\UseEcommerceLoyaltyPointsService;
use App\Domains\OrderDiscount\OrderDiscountQueries;
use App\Domains\OrderLoyaltyPoint\OrderLoyaltyPointQueries;
use App\Models\Location;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Order;
use App\Models\OrderLoyaltyPoint;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->checkOrderEcommerceDetailsService = new CheckOrderEcommerceDetailsService();
    $this->useEcommerceLoyaltyPointsService = new UseEcommerceLoyaltyPointsService();
    $this->companyId = 1;

    $this->orderDetails = [
        'member_id' => null,
        'notes' => 'Notes goes here',
        'order_items' => [
            [
                'id' => 1,
                'upc' => 'abd123',
                'price' => '10.00',
                'total_amount' => '10.00',
                'quantity' => '1',
                'promoter_ids' => [1],
            ],
        ],
        'payment_type_id' => 1,
        'payment_amount' => 100,
        'shipping_address' => [
            'first_name' => 'test',
            'last_name' => 'test',
            'phone' => 'test',
            'address_line_1' => 'test',
            'address_line_2' => 'test',
            'country_code' => 'test',
            'country_id' => 1,
            'state_id' => 1,
            'city_id' => 1,
            'area_code' => 'test',
        ],
        'billing_address' => [
            'first_name' => 'test',
            'last_name' => 'test',
            'phone' => 'test',
            'address_line_1' => 'test',
            'address_line_2' => 'test',
            'country_code' => 'test',
            'country_id' => 1,
            'state_id' => 1,
            'city_id' => 1,
            'area_code' => 'test',
        ],
        'order_round_off_amount' => 0.0,
        'total_tax_amount' => 0.0,
        'member_details' => [],
    ];

    $this->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
    $this->checkOrderEcommerceDetailsService->orderECommerceData = $this->orderECommerceData;

    $this->orderItems = collect($this->orderECommerceData->order_items);
    $this->checkOrderEcommerceDetailsService->orderItems = $this->orderItems;
});

test(
    'checkLoyaltyPointsCartDiscount method returns null when there are no payments by loyalty points',
    function (): void {
        $response = $this->useEcommerceLoyaltyPointsService->checkLoyaltyPointsCartDiscount(
            $this->checkOrderEcommerceDetailsService
        );
        $this->assertNull($response);
    }
);

test(
    'checkLoyaltyPointsCartDiscount method sets the saleMismatches when the Specified amount does not match with the given loyalty points',
    function (): void {
        $this->orderDetails['cart_loyalty_point_amount'] = 100;
        $this->orderDetails['cart_loyalty_points'] = 10;

        $mock = $this->createPartialMock(UseEcommerceLoyaltyPointsService::class, ['checkUserLoyaltyPoints']);

        $mock->expects($this->once())
            ->method('checkUserLoyaltyPoints')
            ->will($this->returnValue(new Member()));

        $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $this->checkOrderEcommerceDetailsService->orderMismatches = collect([]);

        $mock->checkLoyaltyPointsCartDiscount($this->checkOrderEcommerceDetailsService);
    }
)->throws(
    HttpException::class,
    'The specified amount (100) is more than the calculated amount from the loyalty points as per the membership of the user (0)'
);

test('checkLoyaltyPointsCartDiscount method returns the response as expected', function (): void {
    $this->orderDetails['cart_loyalty_point_amount'] = 100;
    $this->orderDetails['cart_loyalty_points'] = 400;

    $member = new Member([
        'membership_id' => 1,
        'loyalty_points' => 500,
    ]);

    $member->membership = new Membership([
        'loyalty_points_per_currency_unit' => 4,
    ]);

    $mock = $this->createPartialMock(
        UseEcommerceLoyaltyPointsService::class,
        ['checkUserLoyaltyPoints', 'checkLoyaltyPointsIsValidOrNot']
    );

    $mock->expects($this->once())
        ->method('checkUserLoyaltyPoints')
        ->will($this->returnValue($member));

    $mock->expects($this->once())
        ->method('checkLoyaltyPointsIsValidOrNot')
        ->will($this->returnValue(true));

    $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
    $this->checkOrderEcommerceDetailsService->orderMismatches = collect([]);

    $response = $mock->checkLoyaltyPointsCartDiscount($this->checkOrderEcommerceDetailsService);
    $this->assertNull($response);

    $this->assertTrue($this->checkOrderEcommerceDetailsService->orderMismatches->toArray() === []);
});

test(
    'checkUserLoyaltyPoints method throws an exception when member id or employee id not specified',
    function (): void {
        $this->checkOrderEcommerceDetailsService->member = null;
        $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $this->useEcommerceLoyaltyPointsService->checkUserLoyaltyPoints($this->checkOrderEcommerceDetailsService);
    }
)->throws(HttpException::class, 'User is compulsory when used loyalty point');

test('checkUserLoyaltyPoints method throws an exception when user does not have an membership.', function (): void {
    $this->checkOrderEcommerceDetailsService->member = new Member();
    $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
    $this->useEcommerceLoyaltyPointsService->checkUserLoyaltyPoints($this->checkOrderEcommerceDetailsService);
})->throws(HttpException::class, 'Loyalty points can only be used when membership is assigned to the user.');

test(
    'checkUserLoyaltyPoints method sets the saleMismatches when the user does not have loyalty points.',
    function (): void {
        $member = new Member([
            'membership_id' => 1,
            'loyalty_points' => 50,
        ]);

        $member->membership = new Membership([
            'loyalty_points_per_currency_unit' => 4,
        ]);

        $this->orderDetails['cart_loyalty_points'] = 100;

        $this->checkOrderEcommerceDetailsService->orderMismatches = collect([]);
        $this->checkOrderEcommerceDetailsService->member = $member;
        $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
        $this->useEcommerceLoyaltyPointsService->checkUserLoyaltyPoints($this->checkOrderEcommerceDetailsService);
    }
)->throws(
    HttpException::class,
    'Specified loyalty points are more than the current loyalty points balance of the user.'
);

test('it calls the checkLoyaltyPointsIsValidOrNot method return null when member ship not set', function (): void {
    $member = Member::factory()->make([
        'company_id' => $this->companyId,
        'created_location_id' => 1,
        'status' => Status::ACTIVE->value,
        'membership_id' => 1,
        'employee_id' => 1,
    ]);

    $member->membership = null;

    $response = $this->useEcommerceLoyaltyPointsService->checkLoyaltyPointsIsValidOrNot(
        $this->checkOrderEcommerceDetailsService,
        $member,
        201
    );
    $this->assertNull($response);
});

test('it calls the checkLoyaltyPointsIsValidOrNot method check loyalty points is valid', function (): void {
    $member = Member::factory()->make([
        'company_id' => $this->companyId,
        'created_location_id' => 1,
        'status' => Status::ACTIVE->value,
        'membership_id' => 1,
        'employee_id' => 1,
    ]);

    $member->membership = new Membership([
        'min_loyalty_points_for_redemption' => 200,
        'max_loyalty_points_for_redemption' => 1000,
    ]);

    $response = $this->useEcommerceLoyaltyPointsService->checkLoyaltyPointsIsValidOrNot(
        $this->checkOrderEcommerceDetailsService,
        $member,
        201
    );
    $this->assertNull($response);
});

test('it calls the checkLoyaltyPointsIsValidOrNot method check loyalty points is not valid', function (): void {
    $member = Member::factory()->make([
        'company_id' => $this->companyId,
        'created_location_id' => 1,
        'status' => Status::ACTIVE->value,
        'membership_id' => 1,
        'employee_id' => 1,
    ]);

    $member->membership = new Membership([
        'min_loyalty_points_for_redemption' => 200,
        'max_loyalty_points_for_redemption' => 1000,
    ]);

    $this->checkOrderEcommerceDetailsService->orderMismatches = collect([]);

    $this->useEcommerceLoyaltyPointsService->checkLoyaltyPointsIsValidOrNot(
        $this->checkOrderEcommerceDetailsService,
        $member,
        20
    );
})->throws(
    HttpException::class,
    'The specified loyalty points (20) are not valid. Loyalty points must be between 200 and 1000.'
);

test('saveCartWideLoyaltyPointsDiscount method can call addNew method of SaleDiscountQueries', function (): void {
    $this->orderDetails['cart_loyalty_point_amount'] = 10.10;
    $this->orderDetails['cart_loyalty_points'] = 10;
    $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);
    $this->checkOrderEcommerceDetailsService->companyId = $this->companyId;
    $this->checkOrderEcommerceDetailsService->orderMismatches = collect([]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $order = Order::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'location_id' => 1,
        'member_id' => 1,
        'order_return_id' => 1,
        'cancel_order_reason_id' => 1,
    ]);

    $member = Member::factory()->make([
        'company_id' => 1,
        'created_location_id' => 1,
        'spent_till_now' => 10,
        'membership_id' => 1,
    ]);

    $this->checkOrderEcommerceDetailsService->member = $member;

    $this->checkOrderEcommerceDetailsService->location = $location;

    $orderLoyaltyPoint = OrderLoyaltyPoint::factory()->make([
        'id' => 1,
        'order_id' => 1,
    ]);

    $this->mock(OrderDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $this->mock(OrderLoyaltyPointQueries::class, function ($mock) use ($orderLoyaltyPoint): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($orderLoyaltyPoint);
    });

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('refresh')
            ->once()
            ->andReturn($member);
    });

    $this->mock(LoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('decreaseLoyaltyPoints')
            ->once();
    });

    $this->useEcommerceLoyaltyPointsService->saveCartWideLoyaltyPointsDiscount(
        $this->checkOrderEcommerceDetailsService,
        $order
    );
});

test(
    'saveCartWideLoyaltyPointsDiscount method return null when cart loyalty point discount not apply',
    function (): void {
        $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);

        $response = $this->useEcommerceLoyaltyPointsService->saveCartWideLoyaltyPointsDiscount(
            $this->checkOrderEcommerceDetailsService,
            new Order()
        );

        $this->assertNull($response);
    }
);

test(
    'saveCartWideLoyaltyPointsDiscount method return null when member not set',
    function (): void {
        $this->orderDetails['cart_loyalty_point_amount'] = 10.10;
        $this->orderDetails['cart_loyalty_points'] = 10;

        $this->checkOrderEcommerceDetailsService->orderECommerceData = new OrderECommerceData(...$this->orderDetails);

        $this->checkOrderEcommerceDetailsService->member = null;

        $response = $this->useEcommerceLoyaltyPointsService->saveCartWideLoyaltyPointsDiscount(
            $this->checkOrderEcommerceDetailsService,
            new Order()
        );

        $this->assertNull($response);
    }
);
