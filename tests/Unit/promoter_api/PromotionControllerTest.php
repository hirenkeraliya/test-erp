<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Promotion\PromotionQueries;
use App\Http\Controllers\Api\Promoter\PromotionController;
use App\Models\Location;
use App\Models\Promoter;
use App\Models\Promotion;
use Illuminate\Http\Request;

test('calls the getStoreWisePromotion method and returns promotions record', function (): void {
    $promotion = Promotion::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);
    $request = new Request();
    $request->setUserResolver(fn (): Promoter => $promoter);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(PromotionQueries::class, function ($mock) use ($promotion): void {
        $mock->shouldReceive('getPromotionsStoreWiseForApplication')
            ->once()
            ->andReturn(collect($promotion));
    });

    $promotionController = new PromotionController();
    $response = $promotionController->getStoreWisePromotion($request, 1);

    expect($response['data']->resource)->toBeCollection();
});

test('calls the getStoreWiseManualPromotion method and returns promotions record', function (): void {
    $promotion = Promotion::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);
    $request = new Request();
    $request->setUserResolver(fn (): Promoter => $promoter);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(PromotionQueries::class, function ($mock) use ($promotion): void {
        $mock->shouldReceive('getManualPromotionsStoreWiseForApplication')
            ->once()
            ->andReturn(collect($promotion));
    });

    $promotionController = new PromotionController();
    $response = $promotionController->getStoreWiseManualPromotion($request, 1);

    expect($response['data']->resource)->toBeCollection();
});

test('calls the getPromotionWithPromoCode method and returns promotion record', function (): void {
    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($promoter);
    $request->shouldReceive('validate')->once()->andReturn([
        'location_id' => $location->id,
    ]);
    $request->shouldReceive('all')->once()->andReturn([
        'location_id' => $location->id,
    ]);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(PromotionQueries::class, function ($mock) use ($request): void {
        $mock->shouldReceive('getPromotionsOfProvidedPromoCodeForApplication')
            ->once()
            ->with(1, $request->location_id, 'PromoCode')
            ->andReturn(new Promotion());
    });

    $promotionController = new PromotionController();
    $response = $promotionController->getPromotionWithPromoCode($request, 'PromoCode');

    expect($response['promotion']);
});
