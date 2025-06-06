<?php

declare(strict_types=1);

use App\Domains\Member\DataObjects\FirstOrCreateMemberData;
use App\Domains\Member\DataObjects\PaginatedMemberListDataForEcommerce;
use App\Domains\Member\DataObjects\SaleChannelMemberData;
use App\Domains\Member\DataObjects\SaleChannelRegisterMemberData;
use App\Domains\Member\DataObjects\UpdateMemberEcommerceData;
use App\Domains\Member\Enums\MemberChannelEnum;
use App\Domains\Member\MemberQueries;
use App\Domains\MemberChannelReference\MemberChannelReferenceQueries;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Http\Controllers\Api\SaleChannel\Member\MemberController;
use App\Models\Location;
use App\Models\Member;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

test(
    'Calls the registerMember method and successfully registering member',
    function (): void {
        $registerMemberData = new SaleChannelRegisterMemberData(
            id: 1,
            first_name: 'test',
            last_name: 'test',
            mobile_number : '1234567890',
            email: 'test@gmail.com',
            date_of_birth : '2014-02-05',
            image_url : null,
            gender: 'Male',
        );

        $memberQueries = $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewMemberRegistrationForEcommerce', MemberChannelEnum::E_COMMERCE->value)
            ->once();
        });

        [$saleChannel, $request] = setRequestUserForSaleChannel();

        $memberController = new MemberController($memberQueries);
        $memberController->registerMember($registerMemberData, $request);
    }
);

test(
    'FirstOrCreateMemberData method first or create record  ',
    function (): void {
        $registerMemberData = new FirstOrCreateMemberData(
            first_name: 'test',
            mobile_number : '1234567890',
            email: 'test@gmail.com',
            date_of_birth : '2014-02-05'
        );

        $memberQueries = $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewMemberRegistrationForEcommerce')
            ->once();
            $mock->shouldReceive('findMemberByMobileNumber')
            ->once();
            $mock->shouldReceive('findMemberByEmail')
            ->once();
        });

        [$saleChannel, $request] = setRequestUserForSaleChannel();

        $memberController = new MemberController($memberQueries);
        $memberController->firstOrCreateMember($registerMemberData, $request);
    }
);

test(
    'update method update a member first name and email',
    function (): void {
        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $newMemberData = new UpdateMemberEcommerceData(
            first_name: 'test',
            last_name: 'test',
            mobile_number: '1234567890',
            email: 'test@gmail.com',
            date_of_birth: '2014-02-05',
            image_url: null,
            gender: 'Male',
        );

        $this->mock(MemberChannelReferenceQueries::class, function ($mock) use ($member): void {
            $mock->shouldReceive('getByMemberId')
                ->once()
                ->andReturn($member->id);
        });

        $memberQueries = $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('checkUniqueEmailAndMobileNumber')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('updateMemberForEcommerce')
                ->once();
        });

        [$saleChannel, $request] = setRequestUserForSaleChannel(
            [],
            [
                'id' => 1,
                'company_id' => 1,
                'default_location_id' => 1,
                'inventory_deduct_order_status' => OrderStatus::PLACED,
                'type_id' => SaleChannelTypes::ECOMMERCE->value,
            ],
        );

        $memberController = new MemberController($memberQueries);
        $memberController->update($member->id, $newMemberData, $request);
    }
);

test('it calls the getPaginatedListForEcommerce method and returns paginated members', function (): void {
    $companyId = 1;

    Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
    ]);

    $paginatedMemberListData = [
        'per_page' => 1,
        'page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'search_text' => '',
        'after_updated_at' => null,
        'mobile_number' => null,
        'email' => null,
    ];

    $paginatedMemberListDataForEcommerce = new PaginatedMemberListDataForEcommerce(...$paginatedMemberListData);

    $filterData = [
        'per_page' => $paginatedMemberListDataForEcommerce->per_page,
        'sort_by' => $paginatedMemberListDataForEcommerce->sort_by,
        'search_text' => $paginatedMemberListDataForEcommerce->search_text,
        'sort_direction' => $paginatedMemberListDataForEcommerce->sort_direction,
        'after_updated_at' => $paginatedMemberListDataForEcommerce->after_updated_at,
        'mobile_number' => $paginatedMemberListDataForEcommerce->mobile_number,
        'email' => $paginatedMemberListDataForEcommerce->email,
    ];

    $memberQueries = $this->mock(MemberQueries::class, function ($mock) use ($filterData, $companyId): void {
        $mock->shouldReceive('getPaginatedListForEcommerce')
            ->once()
            ->with($filterData, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel();

    $memberController = new MemberController($memberQueries);
    $response = $memberController->getPaginatedList($paginatedMemberListDataForEcommerce, $request);

    expect($response['members'])->toBeInstanceOf(AnonymousResourceCollection::class);
});

test('it calls the memberExists method and returns member is exists', function (): void {
    $companyId = 1;
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
        'mobile_number' => '6012345678',
    ]);

    [$saleChannel, $request] = setRequestUserForSaleChannel([
        'mobile_number' => $member->mobile_number,
        'email' => null,
    ]);

    $memberRecords = new SaleChannelMemberData(mobile_number : '1234567890', email: 'test@gmail.com');

    $memberQueries = $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByMobileOrEmail')
            ->once()
            ->andReturn(true);
    });

    $memberController = new MemberController($memberQueries);
    $memberController->memberExists($request, $memberRecords);
});

test('It calls the deleteMember of the member queries class', function (): void {
    $location = Location::factory()->make([
        'company_id' => 1,
    ]);

    $member = Member::factory()->make([
        'id' => 1,
        'created_location_id' => $location->id,
        'company_id' => 1,
        'channel_id' => MemberChannelEnum::E_COMMERCE->value,
    ]);

    $memberQueries = $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('deleteMember')
            ->once();
        $mock->shouldReceive('getByIdForEcommerce')
            ->once();
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel();

    $memberController = new MemberController($memberQueries);
    $memberController->deleteMember($request, $member->id);
});

test('it calls the getMemberByMobileNumber method and returns member', function (): void {
    $companyId = 1;
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
        'mobile_number' => '6012345678',
    ]);

    $memberQueries = $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('getMembersByMobileNumberForEcommerce')
            ->once()
            ->andReturn(collect());
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel([
        'mobile_number' => $member->mobile_number,
    ]);

    $memberController = new MemberController($memberQueries);
    $response = $memberController->getMemberByMobileNumber($request);

    expect($response['members'])->toBeInstanceOf(AnonymousResourceCollection::class);
});

test('it calls the memberIsExists method and returns member is exists', function (): void {
    $companyId = 1;
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
        'mobile_number' => '6012345678',
    ]);

    [$saleChannel, $request] = setRequestUserForSaleChannel([
        'mobile_number' => $member->mobile_number,
        'email' => null,
    ]);

    $memberRecords = new SaleChannelMemberData(mobile_number : '1234567890', email: 'test@gmail.com');

    $memberQueries = $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByMobileOrEmail')
            ->once()
            ->andReturn(true);
    });

    $memberController = new MemberController($memberQueries);
    $memberController->memberIsExists($request, $memberRecords);
});

test('it calls the fetchMemberByMobile method and returns member', function (): void {
    $companyId = 1;
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
        'mobile_number' => '6012345678',
    ]);

    $memberQueries = $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('getMembersByMobileNumberForEcommerce')
            ->once()
            ->andReturn(collect());
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel([
        'mobile_number' => $member->mobile_number,
    ]);

    $memberController = new MemberController($memberQueries);
    $response = $memberController->fetchMemberByMobile($request);

    expect($response['members'])->toBeInstanceOf(AnonymousResourceCollection::class);
});
