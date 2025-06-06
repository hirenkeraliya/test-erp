<?php

declare(strict_types=1);

use App\Domains\Promotion\PromotionQueries;
use App\Http\Controllers\Api\SaleChannel\Promotion\PromotionController;

it('returns a list of promotions', function (): void {
    $promotionQueries = $this->mock(PromotionQueries::class, function ($mock): void {
        $mock->shouldReceive('getListForEcommerceAsPerTimeFrameWithRelatedData')
            ->once()
            ->andReturn(collect());
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel();

    $promotionController = new PromotionController($promotionQueries);
    $response = $promotionController->getPromotions($request);

    $this->assertEquals(collect([]), $response['promotions']->resource);
});
