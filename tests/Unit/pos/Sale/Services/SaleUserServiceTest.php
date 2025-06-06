<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Services\MemberService;
use App\Domains\Sale\DataObjects\SaleData;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleUserService;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;

beforeEach(function (): void {
    $this->saleDetails = [
        'offline_sale_id' => '1',
        'items' => [
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '10',
                'promotion_id' => 1,
            ],
        ],
        'payments' => [
            [
                'type_id' => 1,
                'amount' => '100',
            ],
        ],
        'sale_notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
        'is_layaway' => true,
        'cart_promotion_id' => null,
        'sale_round_off_amount' => 0.01,
    ];

    $this->saleData = new SaleData(...$this->saleDetails);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->companyId = 1;

    $this->cashier = makeCashierForPosWithCounterUpdateId();

    $this->saleMismatches = collect([]);
    $this->checkSaleDetailsService = new CheckSaleDetailsService();
    $this->saleUserService = new SaleUserService();
});

test('setDetails method works as expected', function (): void {
    $this->checkSaleDetailsService->saleMismatches = $this->saleMismatches;
    $this->saleUserService->setDetails($this->checkSaleDetailsService, $this->cashier);

    $this->assertTrue($this->checkSaleDetailsService->saleMismatches->toArray() === []);
});

test('getMemberId method returns member id as expected', function (): void {
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->companyId = 1;
    $this->checkSaleDetailsService->location = $this->location;

    $this->saleUserService->setDetails($this->checkSaleDetailsService, $this->cashier);

    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ]);

    $response = $this->saleUserService->getMemberId($this->checkSaleDetailsService, $cashier);
    $this->assertEquals($this->checkSaleDetailsService->saleData->member_id, $response);
});

test('getMemberId method returns null if member id and details are not available in request', function (): void {
    $this->saleData->member_id = null;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->companyId = 1;
    $this->checkSaleDetailsService->location = $this->location;

    $this->saleUserService->setDetails($this->checkSaleDetailsService, $this->cashier);

    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ]);

    $response = $this->saleUserService->getMemberId($this->checkSaleDetailsService, $cashier);
    $this->assertEquals(null, $response);
});

test(
    'it calls getMemberByMobileNumber method of MemberQueries class and returns the member when its available in our records.',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => $this->location->id,
            'first_name' => 'ABC',
            'mobile_number' => '123456789',
            'card_number' => 'ABC1234',
        ]);

        $this->saleData->member_id = null;
        $this->saleData->member['first_name'] = $member->first_name;
        $this->saleData->member['mobile_number'] = $member->mobile_number;
        $this->saleData->member['card_number'] = $member->card_number;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;

        $this->saleUserService->setDetails($this->checkSaleDetailsService, $this->cashier);

        $cashier = Cashier::factory()->make([
            'employee_id' => 1,
            'cashier_group_id' => 1,
        ]);

        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldNotReceive('getMemberByMobileNumber');
            $mock->shouldReceive('getMemberByCardNumber')
                ->once()
                ->andReturn($member);
        });

        $this->saleUserService->getMemberId($this->checkSaleDetailsService, $cashier);
    }
);

test(
    'getMemberId method calls respective queries and create new member if member is not exists',
    function (): void {
        $this->saleData->member_id = null;
        $this->saleData->member['type_id'] = 1;
        $this->saleData->member['title_id'] = 1;
        $this->saleData->member['race_id'] = 1;
        $this->saleData->member['gender_id'] = 1;
        $this->saleData->member['first_name'] = 'ABC';
        $this->saleData->member['last_name'] = 'XYZ';
        $this->saleData->member['date_of_birth'] = '1999-01-01';
        $this->saleData->member['email'] = 'test@gmail.com';
        $this->saleData->member['address_line_1'] = 'Address line 1';
        $this->saleData->member['address_line_2'] = 'Address line 2';
        $this->saleData->member['city'] = 'City';
        $this->saleData->member['area_code'] = '1234';
        $this->saleData->member['company_name'] = 'PQL';
        $this->saleData->member['company_registration_number'] = '123456789';
        $this->saleData->member['company_tax_number'] = '1234';
        $this->saleData->member['company_phone'] = '123456789';
        $this->saleData->member['created_location_id'] = 1;
        $this->saleData->member['notes'] = 'Notes goes here';
        $this->saleData->member['mobile_number'] = '123456789';
        $this->saleData->member['card_number'] = 'ABCD4567EFGH';

        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;
        $this->saleUserService->setDetails($this->checkSaleDetailsService, $this->cashier);

        $cashier = Cashier::factory()->make([
            'employee_id' => 1,
            'cashier_group_id' => 1,
        ]);

        $this->mock(MemberService::class, function ($mock): void {
            $mock->shouldReceive('getMemberIdFromDetails')
                ->once()
                ->andReturn(null);

            $mock->shouldReceive('checkRequiredMemberColumns')
                ->once()
                ->with($this->saleData->member, 1);

            $mock->shouldReceive('addNewMember')
                ->once();
        });

        $this->saleUserService->getMemberId($this->checkSaleDetailsService, $cashier);
    }
);

test('getUser method returns null when the user is not specified in request.', function (): void {
    $this->saleData->member_id = null;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->saleUserService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $response = $this->saleUserService->getUser();
    $this->assertEquals(null, $response);
});

test(
    'getUser method calls getByIdWithMembership method of EmployeeQueries class and returns the employee when its available in our records',
    function (): void {
        $this->saleData->employee_id = 1;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->saleUserService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $employee = new Employee();

        $this->mock(EmployeeQueries::class, function ($mock) use ($employee): void {
            $mock->shouldReceive('getByIdWithMembership')
                ->once()
                ->andReturn($employee);
        });

        $response = $this->saleUserService->getUser();
        $this->assertEquals($employee, $response);
    }
);

test(
    'getExistingMemberId method returns the member id as expected.',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => $this->location->id,
            'first_name' => 'ABC',
            'mobile_number' => '123456789',
        ]);

        $this->saleData->member_id = $member->id;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;

        $this->saleUserService->setDetails($this->checkSaleDetailsService, $this->cashier);

        $response = $this->saleUserService->getExistingMemberId();

        $this->assertEquals($member->id, $response);
    }
);

test(
    'getUser method calls getByIdWithMembership method of MemberQueries class and returns the Member when its available in our records',
    function (): void {
        $this->saleData->member_id = 1;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->saleUserService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $member = new Member();

        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('getByIdWithMembership')
                ->once()
                ->andReturn($member);
        });

        $response = $this->saleUserService->getUser();
        $this->assertEquals($member, $response);
    }
);

test(
    'getExistingMemberId method returns the member id if member with given details is already available in our records.',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'created_location_id' => $this->location->id,
            'first_name' => 'ABC',
            'mobile_number' => '123456789',
            'card_number' => '123456789',
        ]);

        $this->saleData->member_id = null;
        $this->saleData->member['first_name'] = $member->first_name;
        $this->saleData->member['mobile_number'] = $member->mobile_number;
        $this->saleData->member['card_number'] = $member->card_number;
        $this->checkSaleDetailsService->saleData = $this->saleData;
        $this->checkSaleDetailsService->companyId = 1;
        $this->checkSaleDetailsService->location = $this->location;

        $this->saleUserService->setDetails($this->checkSaleDetailsService, $this->cashier);

        $this->mock(MemberQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('getMemberByCardNumber')
                ->once()
                ->andReturn($member);
        });

        $this->saleUserService->getExistingMemberId();
    }
);

test('getMember method returns null when the employee id and member id is null.', function (): void {
    $this->saleData->member_id = null;
    $this->saleData->employee_id = null;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->saleUserService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $response = $this->saleUserService->getMember();
    $this->assertEquals(null, $response);
});

test('getMember method returns member when the employee id is not null', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'created_location_id' => 1,
        'first_name' => 'ABC',
        'mobile_number' => '123456789',
        'employee_id' => 1,
        'card_number' => '123456789',
    ]);

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('getByEmployeeIdAndCompanyIdWithMembership')
            ->once()
            ->andReturn($member);
    });

    $this->saleData->member_id = null;
    $this->saleData->employee_id = 1;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->companyId = $this->companyId;
    $this->saleUserService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $response = $this->saleUserService->getMember();
    expect($response)->toBeInstanceOf(Member::class);
});

test('getMember method returns member when the member id is not null', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'created_location_id' => 1,
        'first_name' => 'ABC',
        'mobile_number' => '123456789',
        'employee_id' => null,
        'card_number' => '123456789',
    ]);

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('getByIdCompanyIdWithMembership')
            ->once()
            ->andReturn($member);
    });

    $this->saleData->member_id = 1;
    $this->saleData->employee_id = null;
    $this->checkSaleDetailsService->saleData = $this->saleData;
    $this->checkSaleDetailsService->companyId = $this->companyId;
    $this->saleUserService->checkSaleDetailsService = $this->checkSaleDetailsService;
    $response = $this->saleUserService->getMember();
    expect($response)->toBeInstanceOf(Member::class);
});
