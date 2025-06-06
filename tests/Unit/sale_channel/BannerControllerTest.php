<?php

declare(strict_types=1);

use App\Domains\Banner\BannerQueries;
use App\Domains\Banner\Resources\BannerListResource;
use App\Http\Controllers\Api\SaleChannel\Banner\BannerController;

test(
    'It calls the getListInEcommerce method of the banner queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
        ];

        $bannerQueries = $this->mock(BannerQueries::class, function ($mock): void {
            $mock->shouldReceive('getListInEcommerce')
                ->once()
                ->andReturn(collect([]));
        });

        [$saleChannel, $request] = setRequestUserForSaleChannel($requestParameter);

        $bannerController = new BannerController($bannerQueries);
        $response = $bannerController->getList($request);
        $this->assertEquals(BannerListResource::collection(collect([])), $response['banners']);
    }
);
