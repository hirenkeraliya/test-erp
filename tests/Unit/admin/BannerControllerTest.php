<?php

declare(strict_types=1);

use App\Domains\Banner\BannerQueries;
use App\Domains\Banner\DataObjects\BannerData;
use App\Domains\Banner\Jobs\BannerSyncMainJob;
use App\Domains\SaleChannel\Services\SaleChannelService;
use App\Domains\SyncTransaction\Enums\SyncTypes;
use App\Http\Controllers\Admin\BannerController;
use App\Models\Admin;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Queue;

test('It calls the list query method of the banner queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $bannerQueries = $this->mock(BannerQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $bannerController = new BannerController($bannerQueries);
    $response = $bannerController->fetchBanners(new Request($requestParameter));
    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']->resource);
});

test('It calls addNew method of the banner queries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);
    $bannerData = Banner::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    unset($bannerData['company_id']);
    $bannerData['image'] = UploadedFile::fake()->image('avatar.jpg');
    $bannerRecords = new BannerData(...$bannerData);
    $bannerQueries = $this->mock(BannerQueries::class, function ($mock) use ($bannerRecords, $companyId): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($bannerRecords, $companyId);
    });

    $bannerController = new BannerController($bannerQueries);
    $redirectResponse = $bannerController->store($bannerRecords);
    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('The banner has been added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/banners', $redirectResponse->getTargetUrl());
});

test('It calls update method of the banner queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $bannerData = Banner::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($bannerData['company_id']);
    $bannerData['image'] = UploadedFile::fake()->image('avatar.jpg');
    $bannerRecords = new BannerData(...$bannerData);

    $bannerQueries = $this->mock(BannerQueries::class, function ($mock) use ($bannerRecords, $companyId): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($bannerRecords, 1, $companyId);
    });

    $bannerController = new BannerController($bannerQueries);
    $redirectResponse = $bannerController->update($bannerRecords, $companyId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('The banner has been updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/banners', $redirectResponse->getTargetUrl());
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
                ->with(1, SyncTypes::BANNER->value, $admin, 1);
        });

        $bannerController = new BannerController(new BannerQueries());
        $bannerController->syncData(1, $request);

        Queue::assertPushed(BannerSyncMainJob::class);
    }
);
