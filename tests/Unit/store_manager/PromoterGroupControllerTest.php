<?php

declare(strict_types=1);

use App\Domains\PromoterGroup\DataObjects\PromoterGroupData;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Http\Controllers\StoreManager\PromoterGroupController;
use App\Models\PromoterGroup;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the list query method of the promoter group queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setStoreManagerStoreCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $promoterGroupQueries = $this->mock(PromoterGroupQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $promoterGroupController = new PromoterGroupController($promoterGroupQueries);

        $response = $promoterGroupController->fetchPromoterGroups(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls addNew method of the promoter group queries class', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $promoterGroupData = PromoterGroup::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($promoterGroupData['company_id']);

    $promoterGroupRecords = new PromoterGroupData(...$promoterGroupData);

    $request = new Request();

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $promoterGroupQueries = $this->mock(PromoterGroupQueries::class, function ($mock) use (
        $promoterGroupRecords,
        $companyId,
        $storeManager
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($promoterGroupRecords, $companyId, $storeManager);
    });

    $promoterGroupController = new PromoterGroupController($promoterGroupQueries);
    $redirectResponse = $promoterGroupController->store($promoterGroupRecords, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The promoter group has been added successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('store-manager/promoter-groups', $redirectResponse->getTargetUrl());
});

test('It calls update method of the promoter group queries class', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $promoterGroupData = PromoterGroup::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($promoterGroupData['company_id']);

    $promoterGroupRecords = new PromoterGroupData(...$promoterGroupData);

    $promoterGroupQueries = $this->mock(PromoterGroupQueries::class, function ($mock) use (
        $promoterGroupRecords,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($promoterGroupRecords, 1, $companyId);
    });

    $promoterGroupController = new PromoterGroupController($promoterGroupQueries);
    $redirectResponse = $promoterGroupController->update($promoterGroupRecords, $companyId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The promoter group has been updated successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('store-manager/promoter-groups', $redirectResponse->getTargetUrl());
});

test('It calls the exportPromoterGroups method and returns a proper response', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $promoterGroupQueries = $this->mock(PromoterGroupQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getPromoterGroupsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new PromoterGroup()));
    });

    $promoterGroupController = new PromoterGroupController($promoterGroupQueries);

    $response = $promoterGroupController->exportPromoterGroups('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
