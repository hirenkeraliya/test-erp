<?php

declare(strict_types=1);

use App\Domains\Reward\DataObjects\RewardData;
use App\Domains\Reward\Enums\RewardTargetTypes;
use App\Domains\Reward\Enums\RewardTypes;
use App\Domains\Reward\RewardQueries;
use App\Http\Controllers\Admin\RewardController;
use App\Models\Admin;
use App\Models\Reward;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the List query method of the reward queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $loyaltyCampaignQueries = $this->mock(RewardQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $rewardController = new RewardController($loyaltyCampaignQueries);

        $response = $rewardController->fetchRewards(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        expect($response['data'])->toBeInstanceOf(AnonymousResourceCollection::class);
    }
);

test('It calls the addNew method of reward queries class', function (): void {
    $brandIds = [
        'id' => 1,
    ];

    $newRewardRecord['title'] = 'EFGH';
    $newRewardRecord['loyalty_point'] = 11.11;
    $newRewardRecord['type'] = RewardTypes::FREE_ITEM->value;
    $newRewardRecord['target_type'] = RewardTargetTypes::BRANDS->value;
    $newRewardRecord['status'] = true;
    $newRewardRecord['brand_ids'] = $brandIds;

    $rewardData = new RewardData(...$newRewardRecord);
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $rewardQueries = $this->mock(RewardQueries::class, function ($mock) use (
        $rewardData,
        $companyId,
        $admin
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($rewardData, $companyId, $admin);
    });

    $rewardController = new RewardController($rewardQueries);
    $redirectResponse = $rewardController->store($rewardData, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('The Reward was added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/rewards', $redirectResponse->getTargetUrl());
});

test('It calls the update method of loyalty campaign queries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $brandIds = [
        'id' => 1,
    ];

    $newRewardRecord['title'] = 'EFGH';
    $newRewardRecord['loyalty_point'] = 11.11;
    $newRewardRecord['type'] = RewardTypes::FREE_ITEM->value;
    $newRewardRecord['target_type'] = RewardTargetTypes::BRANDS->value;
    $newRewardRecord['status'] = true;
    $newRewardRecord['brand_ids'] = $brandIds;

    $rewardData = new RewardData(...$newRewardRecord);

    $loyaltyCampaignQueries = $this->mock(RewardQueries::class, function ($mock) use (
        $rewardData,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($rewardData, 1, $companyId);
    });

    $rewardController = new RewardController($loyaltyCampaignQueries);
    $redirectResponse = $rewardController->update($rewardData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Reward updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/rewards', $redirectResponse->getTargetUrl());
});

test('It calls the exportLoyaltyCampaigns method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $rewardQueries = $this->mock(RewardQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Reward()));
    });

    $rewardController = new RewardController($rewardQueries);

    $response = $rewardController->exportRewards('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the setStatus method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $rewardQueries = $this->mock(RewardQueries::class, function ($mock): void {
        $mock->shouldReceive('setStatus')
            ->once();
    });

    $rewardController = new RewardController($rewardQueries);

    $response = $rewardController->setStatus(1, true);

    $this->assertEquals(302, $response->getStatusCode());
});
