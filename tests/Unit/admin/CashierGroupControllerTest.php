<?php

declare(strict_types=1);

use App\Domains\CashierGroup\CashierGroupQueries;
use App\Domains\CashierGroup\DataObjects\CashierGroupData;
use App\Domains\Company\CompanyQueries;
use App\Http\Controllers\Admin\CashierGroupController;
use App\Models\Admin;
use App\Models\CashierGroup;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the list query method of the cashier group queries class and returns proper response',
    function (): void {
        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $cashierGroupQueries = $this->mock(CashierGroupQueries::class, function ($mock) use ($requestParameter): void {
            setCompanyIdInSession(1);

            $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $cashierGroupController = new CashierGroupController($cashierGroupQueries);

        $response = $cashierGroupController->fetchCashierGroups(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']);
    }
);

test('It calls addNew method of the cashier group queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $cashierGroupDetails = CashierGroup::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    $cashierGroupDetails['permission_ids'] = [1, 2];
    unset($cashierGroupDetails['company_id']);

    $cashierGroupRecord = new CashierGroupData(...$cashierGroupDetails);

    $request = new Request();

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): Admin => $admin);

    $cashierGroupQueries = $this->mock(CashierGroupQueries::class, function ($mock) use (
        $cashierGroupRecord,
        $companyId,
        $admin
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($cashierGroupRecord, $companyId, $admin);
    });

    $cashierGroupController = new CashierGroupController($cashierGroupQueries);
    $redirectResponse = $cashierGroupController->store($cashierGroupRecord, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Cashier group added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/cashier-groups', $redirectResponse->getTargetUrl());
});

test('It calls get by id method of the cashier group queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = CashierGroup::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    $this->mock(CompanyQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('getAllowPriceOverrideCartLevel')
            ->once()
            ->with($companyId)
            ->andReturn(true);
    });

    $cashierGroupQueries = $this->mock(CashierGroupQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getByIdWithPermissions')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new CashierGroup($requestParameter));
    });

    $cashierGroupController = new CashierGroupController($cashierGroupQueries);
    $response = $cashierGroupController->edit(1);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has(
            'cashierGroup',
            fn (Assert $cashierGroup): Assert => $cashierGroup
                ->where('name', $requestParameter['name'])
                ->etc()
        )
    );
});

test('It calls update method of the cashier group queries class', function (): void {
    Cache::spy();

    $companyId = 1;

    setCompanyIdInSession($companyId);

    $cashierGroupDetails = CashierGroup::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    $cashierGroupDetails['permission_ids'] = [1, 2];
    unset($cashierGroupDetails['company_id']);

    $cashierGroupRecord = new CashierGroupData(...$cashierGroupDetails);

    $cashierGroupQueries = $this->mock(CashierGroupQueries::class, function ($mock) use (
        $cashierGroupRecord,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($cashierGroupRecord, 1, $companyId);
    });

    $cashierGroupController = new CashierGroupController($cashierGroupQueries);
    $redirectResponse = $cashierGroupController->update($cashierGroupRecord, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Cashier group updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/cashier-groups', $redirectResponse->getTargetUrl());
    Cache::shouldHaveReceived('forget')
            ->once()
            ->with('cashier_group_permission_1');
});

test('It calls the exportCashierGroups method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $cashierGroupQueries = $this->mock(CashierGroupQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getCashierGroupsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new CashierGroup()));
    });

    $cashierGroupController = new CashierGroupController($cashierGroupQueries);

    $response = $cashierGroupController->exportCashierGroups('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
