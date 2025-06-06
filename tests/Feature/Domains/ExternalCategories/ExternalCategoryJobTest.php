<?php

use App\Domains\ExternalCategories\Jobs\ExternalCategoryJob;
use App\Domains\ExternalCategories\Services\CategoryWebspertService;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Models\SaleChannel;
use Illuminate\Support\Facades\Queue;

it('calls the CategoryWebspertService when the job is handled', function (): void {
    Queue::fake();

    $saleChannel = SaleChannel::factory()->make();
    $saleChannelQueriesMock = Mockery::mock(SaleChannelQueries::class);
    $saleChannelQueriesMock->shouldReceive('getWebspertSaleChannel')->andReturn($saleChannel);
    $this->app->instance(SaleChannelQueries::class, $saleChannelQueriesMock);

    $categoryWebspertServiceMock = Mockery::mock(CategoryWebspertService::class);
    $categoryWebspertServiceMock->shouldReceive('fetchCategories')->once()->with($saleChannel);
    $this->app->instance(CategoryWebspertService::class, $categoryWebspertServiceMock);

    $job = new ExternalCategoryJob();
    $job->handle();
});
