<?php

declare(strict_types=1);

use App\Domains\Category\CategoryQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\MemberGroup\DataObjects\MemberGroupData;
use App\Domains\MemberGroup\Enums\GroupTypes;
use App\Domains\MemberGroup\Jobs\MemberGroupSyncMainJob;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Http\Controllers\Admin\MemberGroupController;
use App\Models\Admin;
use App\Models\MemberGroup;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the list query method of the member group queries class and returns proper response',
    function (): void {
        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $memberGroupQueries = $this->mock(MemberGroupQueries::class, function ($mock) use ($requestParameter): void {
            setCompanyIdInSession(1);

            $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $memberGroupController = new MemberGroupController($memberGroupQueries);

        $response = $memberGroupController->fetchMemberGroups(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls addNew method of the member group queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $memberGroupRecord = new MemberGroupData(
        'member group name',
        'member group code',
        GroupTypes::SMART_GROUP->value,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        [1], );

    $admin = Admin::factory()->make([
        'employee_id' => 1,
        'id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $memberGroupQueries = $this->mock(MemberGroupQueries::class, function ($mock) use (
        $memberGroupRecord,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($memberGroupRecord, $companyId)
            ->andReturn(new MemberGroup());
    });

    $memberGroupController = new MemberGroupController($memberGroupQueries);
    $redirectResponse = $memberGroupController->store($memberGroupRecord, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The member group has been added successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/member-groups', $redirectResponse->getTargetUrl());
});

test('It calls get by id method of the member group queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = MemberGroup::factory()->make([
        'company_id' => $companyId,
        'type_id' => GroupTypes::SMART_GROUP->value,
    ])->toArray();

    $memberGroupQueries = $this->mock(MemberGroupQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new MemberGroup($requestParameter));
    });

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getAll')
            ->andReturn(collect([]));
    });
    $this->mock(CategoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getAll')
            ->andReturn(collect([]));
    });

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIdWithAutoIncludeMemberGroup')
            ->andReturn(true);
    });

    $memberGroupController = new MemberGroupController($memberGroupQueries);
    $response = $memberGroupController->edit(1);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has(
            'memberGroup',
            fn (Assert $memberGroup): Assert => $memberGroup
                ->where('name', $requestParameter['name'])
                ->etc()
        )
    );
});

test('It calls update method of the member group queries class', function (): void {
    Cache::spy();

    $companyId = 1;

    setCompanyIdInSession($companyId);

    $memberGroupRecord = new MemberGroupData(
        'name',
        'code',
        GroupTypes::SMART_GROUP->value,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        [1]);

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $memberGroupQueries = $this->mock(MemberGroupQueries::class, function ($mock) use (
        $memberGroupRecord,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($memberGroupRecord, 1, $companyId)
            ->andReturn(new MemberGroup());
    });

    $memberGroupController = new MemberGroupController($memberGroupQueries);
    $redirectResponse = $memberGroupController->update($memberGroupRecord, $request, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The member group has been successfully updated.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/member-groups', $redirectResponse->getTargetUrl());
});

test('It calls the exportMemberGroups method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $memberGroupQueries = $this->mock(MemberGroupQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getMemberGroupsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new MemberGroup()));
    });

    $memberGroupController = new MemberGroupController($memberGroupQueries);

    $response = $memberGroupController->exportMemberGroups('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the removeSelectedMembers method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $memberGroupQueries = $this->mock(MemberGroupQueries::class, function ($mock): void {
        $mock->shouldReceive('removeSelectedMembers')
            ->once();
    });

    $memberGroupController = new MemberGroupController($memberGroupQueries);

    $memberGroupController->removeSelectedMembers(1);

    $this->assertTrue(true);
});

test('It calls the removeSelectedProducts method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $memberGroupQueries = $this->mock(MemberGroupQueries::class, function ($mock): void {
        $mock->shouldReceive('removeSelectedProducts')
            ->once();
    });

    $memberGroupController = new MemberGroupController($memberGroupQueries);

    $memberGroupController->removeSelectedProducts(1);

    $this->assertTrue(true);
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
                ->with(1, SyncTypes::MEMBER_GROUP->value, $admin, 1);
        });

        $memberGroupController = new MemberGroupController(new MemberGroupQueries());
        $memberGroupController->syncData(1, $request);

        Queue::assertPushed(MemberGroupSyncMainJob::class);
    }
);
