<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Member\DataObjects\MemberData;
use App\Domains\Member\DataObjects\UpdateLoyaltyPointData;
use App\Domains\Member\DataObjects\UpdateMemberAddressData;
use App\Domains\Member\Jobs\MemberSyncMainJob;
use App\Domains\Member\Jobs\MemberUpdatePointsAndTotalSalesJob;
use App\Domains\Member\Jobs\SendEmailsJob;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Services\MemberService;
use App\Domains\MemberAddress\MemberAddressQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Http\Controllers\Admin\MemberController;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\MemberAddress;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the member queries class and returns proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'abc',
        'sort_by' => 'name',
        'sort_direction' => 'desc',
        'per_page' => 1,
        'location_ids' => null,
        'membership_ids' => null,
        'member_group_ids' => null,
        'date_range' => null,
        'status' => null,
        'product_id' => null,
        'preference_id' => null,
        'color_id' => null,
        'size_id' => null,
        'category_id' => null,
        'preferred_date' => null,
        'preferred_day' => null,
        'purchase_filter_type_id' => null,
        'condition_operator_type_id' => null,
        'purchase_value' => null,
    ];

    $memberQueries = $this->mock(MemberQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('listQueryForMembers')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $memberController = new MemberController($memberQueries);
    $response = $memberController->fetchMembers(new Request($requestParameter));
    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']->resource);
});

test(
    'It calls the addNewForAdminAndStoreManager method of the member queries class and returns proper response',
    function (): void {
        $companyId = 1;

        $memberRecord = Member::factory()->make([
            'company_id' => $companyId,
            'created_location_id' => 1,
            'card_number' => 'ABCD1234DEFG',
        ])->toArray();
        $memberRecord['photo'] = null;
        unset(
            $memberRecord['company_id'],
            $memberRecord['created_by_id'],
            $memberRecord['created_by_type'],
            $memberRecord['last_purchase_date'],
        );

        $memberData = new MemberData(...$memberRecord);

        setCompanyIdInSession($companyId);

        $request = new Request();

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        $request->setUserResolver(fn (): Admin => $admin);

        $memberQueries = $this->mock(MemberQueries::class, function ($mock) use ($memberData, $admin): void {
            $mock->shouldReceive('addNewForAdminAndStoreManager')
                ->once()
                ->with($memberData, 1, $admin, 1);
        });

        $memberController = new MemberController($memberQueries);
        $redirectResponse = $memberController->store($memberData, $request);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Member added successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString('admin/members', $redirectResponse->getTargetUrl());
    }
);

test(
    'It calls the get by id with media method of the member queries class and returns proper response',
    function (): void {
        $companyId = 1;

        $newMemberRecord = Member::factory()->make([
            'company_id' => $companyId,
            'created_location_id' => 1,
        ])->toArray();

        $returnData = [
            'id' => '1',
            'name' => 'ABC',
        ];

        setCompanyIdInSession($companyId);

        $memberQueries = $this->mock(MemberQueries::class, function ($mock) use (
            $newMemberRecord,
            $companyId
        ): void {
            $mock->shouldReceive('getByIdWithMedia')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new Member($newMemberRecord));
        });

        $this->mock(LocationQueries::class, function ($mock) use ($returnData): void {
            $mock->shouldReceive('getStoreWithBasicColumns')
            ->once()
            ->with(1)
            ->andReturn(new Collection([$returnData]));
        });

        $memberController = new MemberController($memberQueries);
        $response = $memberController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has(
            'member',
            fn (Assert $employee): Assert => $employee
            ->where('company_id', $newMemberRecord['company_id'])
            ->where('first_name', $newMemberRecord['first_name'])
            ->where('last_name', $newMemberRecord['last_name'])
            ->where('email', $newMemberRecord['email'])
            ->where('mobile_number', $newMemberRecord['mobile_number'])
            ->etc()
        )
        ->has(
            'genders',
            fn (Assert $genders): Assert => $genders
            ->has('0', fn (Assert $gender): Assert => $gender->where('name', 'Male')->etc())
            ->etc()
        )
        ->has(
            'titles',
            fn (Assert $titles): Assert => $titles
            ->has('0', fn (Assert $title): Assert => $title->where('name', 'Datin')->etc())
            ->etc()
        )
        ->has(
            'races',
            fn (Assert $races): Assert => $races
            ->has('0', fn (Assert $race): Assert => $race->where('name', 'Malay')->etc())
            ->etc()
        )
        ->has(
            'types',
            fn (Assert $types): Assert => $types
            ->has('0', fn (Assert $type): Assert => $type->where('name', 'Vip')->etc())
            ->etc()
        )
        ->has(
            'locations',
            fn (Assert $locations): Assert => $locations
            ->has('0', fn (Assert $type): Assert => $type->where('name', 'ABC')->etc())
            ->etc()
        )
        );
    }
);

test('It calls the update method of the member queries class and returns proper response', function (): void {
    $companyId = 1;

    $memberRecord = Member::factory()->make([
        'company_id' => $companyId,
        'created_location_id' => 1,
        'card_number' => 'ABCD1234DEFG',
    ])->toArray();
    $memberRecord['photo'] = null;

    unset(
        $memberRecord['company_id'],
        $memberRecord['created_by_id'],
        $memberRecord['created_by_type'],
        $memberRecord['last_purchase_date'],
    );

    $memberData = new MemberData(...$memberRecord);

    setCompanyIdInSession($companyId);

    $memberQueries = $this->mock(MemberQueries::class, function ($mock) use ($memberData): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($memberData, 1, 1);
    });

    $memberController = new MemberController($memberQueries);
    $redirectResponse = $memberController->update($memberData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Member updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/members', $redirectResponse->getTargetUrl());
});

test('exportExistingMembers method and returns a binary file response', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $memberRecord = Member::factory()->make([
        'company_id' => $companyId,
        'created_location_id' => $location->id,
    ]);

    $memberRecord->createdInLocation = $location;

    $memberQueries = $this->mock(MemberQueries::class, function ($mock) use ($companyId, $memberRecord): void {
        $mock->shouldReceive('getMemberWithStore')
            ->once()
            ->with($companyId)
            ->andReturn(collect([$memberRecord]));
    });

    $memberController = new MemberController($memberQueries);
    $response = $memberController->exportExistingMembers();

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the search members for filter  method of the member queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'abc',
            'number_of_records' => 5,
        ];

        $memberQueries = $this->mock(MemberQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('searchMembersForFilter')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect());
        });

        $memberController = new MemberController($memberQueries);
        $response = $memberController->getFilteredMembers(new Request($requestParameter));
        $this->assertEquals(collect([]), $response['members']->resource);
    }
);

test(
    'It calls the updateLoyaltyPointsForAdmin method of the LoyaltyPointService class and returns proper response',
    function (): void {
        Queue::fake();

        $companyId = 1;

        $memberRecord = Member::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'created_location_id' => 1,
            'card_number' => 'ABCD1234DEFG',
        ]);

        $updateLoyaltyPointData = new UpdateLoyaltyPointData(10, 'Test');

        setCompanyIdInSession($companyId);

        $request = new Request();

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        $admin->employee = new Employee([
            'company_id' => 1,
        ]);

        loginAdmin($admin);

        $request->setUserResolver(fn (): Admin => $admin);

        $memberQueries = $this->mock(MemberQueries::class, function ($mock) use ($memberRecord): void {
            $mock->shouldReceive('getById')
                ->once()
                ->andReturn($memberRecord);
        });

        $this->mock(LoyaltyPointService::class, function ($mock): void {
            $mock->shouldReceive('updateLoyaltyPointsForAdmin')
                ->once();
        });

        $memberController = new MemberController($memberQueries);
        $memberController->updateLoyaltyPoints($updateLoyaltyPointData, 1);

        Queue::assertPushed(MemberUpdatePointsAndTotalSalesJob::class);
    }
);

test(
    'It calls the loyaltyPointsHistory method of the loyaltyPointsUpdateQueries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $this->mock(LoyaltyPointUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('getMemberLoyaltyPointDetails')
                ->once()
                ->with(1)
                ->andReturn(collect());
        });

        $memberQueries = new MemberQueries();

        $memberController = new MemberController($memberQueries);
        $response = $memberController->loyaltyPointsHistory(1);

        expect($response)
            ->toHaveKey('loyalty_points_history');
    }
);

test(
    'It calls the getPaginatedMemberSaleDetails method of the sale queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'created_location_id' => $location->id,
        ]);

        $member->createdInLocation = $location;

        $requestParameter = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => 'desc',
            'per_page' => 1,
            'member_id' => $member->id,
            'location_id' => null,
        ];

        $this->mock(SaleQueries::class, function ($mock) use ($requestParameter): void {
            $mock->shouldReceive('getPaginatedMemberSaleDetails')
                ->once()
                ->with($requestParameter, $requestParameter['member_id'])
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $memberQueries = resolve(MemberQueries::class);
        $memberController = new MemberController($memberQueries);
        $response = $memberController->fetchMemberSaleDetails(new Request($requestParameter));
        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'It calls the fetchMemberSaleReturnDetails method of the sale Return queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'created_location_id' => $location->id,
        ]);

        $member->createdInLocation = $location;

        $requestParameter = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => 'desc',
            'per_page' => 1,
            'member_id' => $member->id,
            'location_id' => null,
        ];

        $this->mock(SaleReturnQueries::class, function ($mock) use ($requestParameter, $companyId): void {
            $mock->shouldReceive('getPaginatedMemberSaleReturnDetails')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $memberQueries = resolve(MemberQueries::class);
        $memberController = new MemberController($memberQueries);
        $response = $memberController->fetchMemberSaleReturnDetails(new Request($requestParameter));
        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'It calls the fetchMemberAddresses method of the memberAddressQueries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $this->mock(MemberAddressQueries::class, function ($mock): void {
            $mock->shouldReceive('getMemberAddressDetails')
                ->once()
                ->with(1)
                ->andReturn(collect());
        });

        $memberQueries = new MemberQueries();

        $memberController = new MemberController($memberQueries);
        $response = $memberController->fetchMemberAddresses(1);

        expect($response)
            ->toHaveKey('member_addresses');
    }
);

test(
    'It calls the updateMemberAddresses method of the memberAddressQueries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'created_location_id' => $location->id,
        ]);

        $memberAddressData = MemberAddress::factory()->make([
            'id' => 1,
            'member_id' => $member->id,
            'name' => 'abcde',
            'contact_mobile_number' => '789567894',
            'contact_email' => 'test@gmail.com',
            'address_line_1' => 'address line1',
            'address_line_2' => 'address line2',
            'city' => 'rajkot',
            'area_code' => '45678903',
            'is_primary' => true,
        ])->toArray();

        unset($memberAddressData['member_id']);
        $updateMemberAddressData = new UpdateMemberAddressData(...[$memberAddressData]);

        $member->createdInLocation = $location;

        $this->mock(MemberQueries::class, function ($mock) use ($member, $companyId): void {
            $mock->shouldReceive('getById')
                ->once()
                ->with($member->id, $companyId)
                ->andReturn(new Member());

            $mock->shouldReceive('updateMemberAddresses')
                ->once();
        });

        $memberQueries = new MemberQueries();

        $memberController = new MemberController($memberQueries);
        $memberController->updateMemberAddresses($updateMemberAddressData, $member->id);
    }
);

test(
    'It calls the delete method of the memberAddressQueries class and returns proper response',
    function (): void {
        $companyId = 1;

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'created_location_id' => 1,
        ]);

        $request = new Request();

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $request->setUserResolver(fn (): Admin => $admin);

        setCompanyIdInSession($companyId);
        $memberQueries = $this->mock(MemberQueries::class, function ($mock) use ($member, $companyId): void {
            $mock->shouldReceive('getById')
                ->once()
                ->with($member->id, $companyId)
                ->andReturn($member);
            $mock->shouldReceive('deleteMemberByAdmin')
                ->once();
        });

        $memberController = new MemberController($memberQueries);
        $redirectResponse = $memberController->delete($member->id, $request);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Member deleted successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString('admin/members', $redirectResponse->getTargetUrl());
    }
);

test(
    'It calls the change status method of the member queries class and returns proper response',
    function (): void {
        $memberId = [
            'memberId' => 1,
        ];

        $memberQueries = $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('changeStatus')
                ->once();
        });

        $memberController = new MemberController($memberQueries);
        $memberController->changeStatus(new Request($memberId));
    }
);

test('exportMembers method returns a binary file response', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $memberRecord = Member::factory()->make([
        'company_id' => $companyId,
        'created_location_id' => $location->id,
        'id' => 1,
        'created_at' => now()->format('Y-m-d H:i:s'),
        'updated_at' => now()->format('Y-m-d H:i:s'),
    ]);

    $memberRecord->createdInLocation = $location;

    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
        'location_ids' => null,
        'membership_ids' => null,
        'member_group_ids' => null,
        'date_range' => null,
        'status' => null,
        'product_id' => null,
        'preference_id' => null,
        'color_id' => null,
        'size_id' => null,
        'category_id' => null,
        'preferred_date' => null,
        'preferred_day' => null,
        'purchase_filter_type_id' => null,
        'condition_operator_type_id' => null,
        'purchase_value' => null,
    ];

    $memberQueries = $this->mock(MemberQueries::class, function ($mock) use (
        $filterData,
        $companyId,
        $memberRecord
    ): void {
        $mock->shouldReceive('getMembersForExport')
            ->once()
            ->with($filterData, $companyId)
            ->andReturn(collect([$memberRecord]));
    });

    $memberController = new MemberController($memberQueries);
    $response = $memberController->exportMembers('test.csv', new Request($filterData));

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('checkMemberExportLimit method returns array', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $memberRecord = Member::factory()->make([
        'company_id' => $companyId,
        'created_location_id' => $location->id,
        'id' => 1,
        'created_at' => now()->format('Y-m-d H:i:s'),
        'updated_at' => now()->format('Y-m-d H:i:s'),
    ]);

    $memberRecord->createdInLocation = $location;

    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
        'location_ids' => null,
        'membership_ids' => null,
        'member_group_ids' => null,
        'date_range' => null,
        'status' => null,
        'product_id' => null,
        'preference_id' => null,
        'color_id' => null,
        'size_id' => null,
        'category_id' => null,
        'preferred_date' => null,
        'preferred_day' => null,
        'purchase_filter_type_id' => null,
        'condition_operator_type_id' => null,
        'purchase_value' => null,
    ];

    $this->mock(MemberService::class, function ($mock) use ($filterData, $companyId, $admin): void {
        $mock->shouldReceive('exportMemberWithJob')
            ->once()
            ->with($admin, $filterData, $companyId)
            ->andReturn([
                'exceeds_limit' => true,
                'message' => 'test.',
            ]);
    });

    $request = new Request($filterData);

    $request->setUserResolver(fn (): Admin => $admin);

    $memberController = new MemberController(new MemberQueries());
    $memberController->checkMemberExportLimit($request);
});

test('sendEmails method call and dispatch the email jobs', function (): void {
    Queue::fake();

    $companyId = 1;
    setCompanyIdInSession($companyId);

    $request = new Request([
        'member_group_id' => 1,
        'email_template_id' => 1,
    ]);

    $memberController = new MemberController(new MemberQueries());
    $memberController->sendEmails($request);

    Queue::assertPushed(SendEmailsJob::class);
});

test(
    'It calls the syncData method and returns proper response',
    function (): void {
        Queue::fake();
        setCompanyIdInSession();

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $this->mock(SaleChannelService::class, function ($mock) use ($admin): void {
            $mock->shouldReceive('updateSyncData')
                ->once()
                ->with(1, SyncTypes::MEMBER->value, $admin, 1);
        });

        $memberController = new MemberController(new MemberQueries());
        $memberController->syncData(1, $request);

        Queue::assertPushed(MemberSyncMainJob::class);
    }
);
