<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\LoyaltyCampaign\DataObjects\LoyaltyCampaignData;
use App\Domains\LoyaltyCampaign\LoyaltyCampaignQueries;
use App\Http\Controllers\Admin\LoyaltyCampaignController;
use App\Models\Admin;
use App\Models\LoyaltyCampaign;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the List query method of the loyalty campaign queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'start_date' => '',
            'end_date' => '',
        ];

        $loyaltyCampaignQueries = $this->mock(LoyaltyCampaignQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $loyaltyCampaignController = new LoyaltyCampaignController($loyaltyCampaignQueries);

        $response = $loyaltyCampaignController->fetchLoyaltyCampaigns(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        expect($response['data'])->toBeInstanceOf(AnonymousResourceCollection::class);
    }
);

test('It calls the addNew method of loyalty campaign queries class', function (): void {
    $brandIds = [
        'id' => 1,
    ];
    $loyaltyCampaignData = new LoyaltyCampaignData('LMNO', 10.10, 10, '2022-09-11', '2022-10-15', 10, $brandIds);
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $this->mock(BrandQueries::class, function ($mock) use ($companyId, $loyaltyCampaignData): void {
        $mock->shouldReceive('doExistsById')
            ->once()
            ->with($companyId, $loyaltyCampaignData->excluded_brand_ids)
            ->andReturn(true);
    });

    $loyaltyCampaignQueries = $this->mock(LoyaltyCampaignQueries::class, function ($mock) use (
        $loyaltyCampaignData,
        $companyId,
        $admin
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($loyaltyCampaignData, $companyId, $admin);
    });

    $loyaltyCampaignController = new LoyaltyCampaignController($loyaltyCampaignQueries);
    $redirectResponse = $loyaltyCampaignController->store($loyaltyCampaignData, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The loyalty campaign was added successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/loyalty-campaigns', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the loyalty campaign queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'company_id' => $companyId,
            'name' => 'STUV',
            'minimum_spend_amount' => 10.10,
            'loyalty_points' => 10,
            'start_date' => '2022-09-11',
            'end_date' => '2022-10-15',
        ];

        $this->mock(BrandQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getCompanyBrands')
                ->once()
                ->with($companyId)
                ->andReturn(new Collection([]));
        });

        $loyaltyCampaignQueries = $this->mock(LoyaltyCampaignQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getById')
                ->once()
                ->with(1, $companyId)
                ->andReturn(new LoyaltyCampaign($requestParameter));
        });

        $loyaltyCampaignController = new LoyaltyCampaignController($loyaltyCampaignQueries);
        $response = $loyaltyCampaignController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
            ->has(
                'loyaltyCampaign',
                fn (Assert $loyaltyCampaign): Assert => $loyaltyCampaign->where('name', 'STUV')->where(
                    'company_id',
                    $companyId
                )
                ->etc()
            )
        );
    }
);

test('It calls the update method of loyalty campaign queries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $brandIds = [
        'id' => 1,
    ];

    $loyaltyCampaignData = new LoyaltyCampaignData('STUV', 10.10, 10, '2022-05-10', '2022-05-12', 10, $brandIds);

    $loyaltyCampaignQueries = $this->mock(LoyaltyCampaignQueries::class, function ($mock) use (
        $loyaltyCampaignData,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($loyaltyCampaignData, 1, $companyId);
    });

    $loyaltyCampaignController = new LoyaltyCampaignController($loyaltyCampaignQueries);
    $redirectResponse = $loyaltyCampaignController->update($loyaltyCampaignData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Loyalty campaign updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/loyalty-campaigns', $redirectResponse->getTargetUrl());
});

test('It calls the exportLoyaltyCampaigns method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'start_date' => '',
        'end_date' => '',
    ];

    $loyaltyCampaignQueries = $this->mock(LoyaltyCampaignQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getLoyaltyCampaignsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new LoyaltyCampaign()));
    });

    $loyaltyCampaignController = new LoyaltyCampaignController($loyaltyCampaignQueries);

    $response = $loyaltyCampaignController->exportLoyaltyCampaigns('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
