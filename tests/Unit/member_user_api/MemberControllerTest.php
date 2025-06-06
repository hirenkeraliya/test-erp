<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\DataObjects\AppMemberData;
use App\Domains\Member\DataObjects\RegisterMemberData;
use App\Domains\Member\MemberQueries;
use App\Domains\Voucher\Enums\VoucherStatusTypes;
use App\Domains\Voucher\VoucherQueries;
use App\Http\Controllers\Api\Member\MemberController;
use App\Models\Location;
use App\Models\LoyaltyPointUpdate;
use App\Models\Member;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

test(
    'it calls the getPaginatedVoucherList method and returns the paginated list of member vouchers',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'created_location_id' => 1,
            'company_id' => 1,
        ]);

        $filterData = [
            'per_page' => 1,
            'status' => VoucherStatusTypes::ACTIVE->value,
        ];

        $request = new Request($filterData);

        $request->setUserResolver(fn (): Member => $member);

        $this->mock(VoucherQueries::class, function ($mock): void {
            $mock->shouldReceive('getPaginatedListForMemberApi')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $memberController = new MemberController();
        $response = $memberController->getPaginatedVoucherList($request);
        expect($response['vouchers'])->toBeInstanceOf(AnonymousResourceCollection::class);
    }
);

test('It returns the Genders list', function (): void {
    $memberController = new MemberController();
    $response = $memberController->getGenders();
    expect($response['genders'][0])
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'Male')
        ->toHaveKey('key', 'MALE');
});

test('It returns the getVoucherStatuses List', function (): void {
    $memberController = new MemberController();
    $response = $memberController->getVoucherStatuses();
    expect($response['statuses'][0])
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'Active')
        ->toHaveKey('key', 'ACTIVE');
});

test('It returns the races list', function (): void {
    $memberController = new MemberController();
    $response = $memberController->getRaces();
    expect($response['races'][0])
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'Malay')
        ->toHaveKey('key', 'MALAY');
});

test('It returns the titles list', function (): void {
    $memberController = new MemberController();
    $response = $memberController->getTitles();
    expect($response['titles'][0])
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'Datin')
        ->toHaveKey('key', 'DATIN');
});

test('It calls the updateProfile method of the MemberQueries class', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Member => $member);

    $memberRecords = new AppMemberData(
        title_id: 1,
        race_id: 1,
        gender_id: 1,
        first_name: 'test',
        last_name: 'test',
        email: 'test',
        address_line_1: 'test',
        address_line_2: 'test',
        city: 'test',
        area_code: 'test',
    );

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('updateMemberProfile')
            ->once();
    });

    $memberController = new MemberController();
    $memberController->updateProfile($memberRecords, $request);
});

test(
    'it calls the getPaginatedTransactionListForMemberApi method and returns the paginated list of member loyalty points',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'created_location_id' => 1,
            'company_id' => 1,
        ]);

        $member->loyaltyPointUpdates = LoyaltyPointUpdate::factory(3)->make([
            'member_id' => $member->id,
            'affected_by_id' => $member->id,
            'affected_by_type' => ModelMapping::MEMBER->name,
        ]);

        $requestParameter = [
            'sort_by' => 'id',
            'sort_direction' => 'asc',
            'per_page' => 1,
        ];

        $request = new Request($requestParameter);

        $request->setUserResolver(fn (): Member => $member);

        $this->mock(LoyaltyPointUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getPaginatedTransactionListForMemberApi')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 50, 15));
            $mock->shouldReceive('getTotalPointRewarded')
                ->once()
                ->andReturn(100);
            $mock->shouldReceive('getTotalPointsRedeemed')
                ->once()
                ->andReturn(50);
        });

        $memberController = new MemberController();
        $response = $memberController->getPaginatedTransactionList($request);

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['loyalty_points']->resource);
    }
);

test('It calls the loadRelations of the member queries class and returns data with relations', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $member = Member::factory()->make([
        'id' => 1,
        'created_location_id' => $location->id,
        'company_id' => 1,
    ]);

    $voucher = Voucher::factory()->make([
        'voucher_configuration_id' => 1,
        'member_id' => $member->id,
        'generated_by_sale_id' => 1,
    ]);

    $loyaltyPointsUpdate = LoyaltyPointUpdate::factory()->make([
        'member_id' => $member->id,
        'affected_by_id' => $member->id,
        'affected_by_type' => ModelMapping::MEMBER->name,
    ]);

    $member->vouchers = collect([$voucher]);
    $member->latestFiveLoyaltyPointUpdates = collect([$loyaltyPointsUpdate]);
    $member->createdInLocation = $location;

    $request = new Request();

    $request->setUserResolver(fn (): Member => $member);

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('loadRelations')
            ->once()
            ->andReturn($member);
    });

    $this->mock(VoucherQueries::class, function ($mock): void {
        $mock->shouldReceive('getActiveVoucherCountFor')
            ->once()
            ->andReturn(10);
    });

    $memberController = new MemberController();
    $response = $memberController->memberDetails($request);

    expect($response)
        ->toHaveKeys(
            [
                'member_details',
                'last_transactions_details',
                'currently_available_loyalty_points',
                'active_voucher_count',
            ]
        );
});

test('It calls the deleteMember of the member queries class', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $member = Member::factory()->make([
        'id' => 1,
        'created_location_id' => $location->id,
        'company_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Member => $member);

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('deleteMember')
            ->once();
    });

    $memberController = new MemberController();
    $memberController->deleteMember($request);
});

test(
    'Calls the registerMember method and successfully registering member',
    function (): void {
        $registerMemberData = new RegisterMemberData(
            first_name: 'test',
            mobile_number : '1234567890',
            email: 'test@gmail.com',
            date_of_birth : '2014-02-05'
        );

        $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewOpenMemberRegistration')
            ->once();
        });

        $memberController = new MemberController();
        $memberController->registerMember($registerMemberData);
    }
);
