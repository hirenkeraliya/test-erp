<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\LoyaltyCampaign\DataObjects\StoreManagerApiLoyaltyCampaignData;
use App\Domains\LoyaltyCampaign\LoyaltyCampaignQueries;
use App\Http\Controllers\Api\StoreManager\LoyaltyCampaignController;
use App\Models\LoyaltyCampaign;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('calls the getLoyaltyCampaigns method and returns loyaltyCampaigns record', function (): void {
    $loyaltyCampaign = LoyaltyCampaign::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $filterData = [
        'page' => 1,
        'per_page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'selected_date' => now()->subMonth()->format('Y-m-d'),
    ];

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $storeManagerApiLoyaltyCampaignData = new StoreManagerApiLoyaltyCampaignData(...$filterData);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(LoyaltyCampaignQueries::class, function ($mock) use ($loyaltyCampaign): void {
        $mock->shouldReceive('getLoyaltyCampaignsForApplication')
            ->once()
            ->andReturn(new LengthAwarePaginator($loyaltyCampaign, 1, 15));
    });

    $loyaltyCampaignController = new LoyaltyCampaignController();
    $response = $loyaltyCampaignController->getLoyaltyCampaigns($request, $storeManagerApiLoyaltyCampaignData);

    expect($response['data']->resource)->toBeCollection();
    expect($response['total_records'])->toBe(1);
});
