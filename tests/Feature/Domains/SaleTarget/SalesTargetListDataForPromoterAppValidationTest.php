<?php

declare(strict_types=1);

use App\Domains\SaleTarget\DataObjects\SalesTargetListDataForPromoterApp;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Models\Promoter;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

function getSalesTargetListForPromoterRequestData(): Request
{
    return new Request([
        'time_interval_type_id' => TimeIntervalType::DAILY->value,
        'per_page' => 10,
        'page' => 1,
        'search_text' => '',
        'sort_by' => '',
        'sort_direction' => '',
    ]);
}

test('validation passes when all details are provided are valid', function (): void {
    $request = getSalesTargetListForPromoterRequestData();

    $promoter = Promoter::factory()->create();

    $request->setUserResolver(fn (): Promoter => $promoter);

    $request->validate(SalesTargetListDataForPromoterApp::rules());

    $this->assertTrue(true);
});

test('validation not passes when time_interval_type_id are not valid', function (): void {
    $request = getSalesTargetListForPromoterRequestData();

    $request['time_interval_type_id'] = 66;

    $promoter = Promoter::factory()->create();

    $request->setUserResolver(fn (): Promoter => $promoter);

    $request->validate(SalesTargetListDataForPromoterApp::rules());
})->throws(ValidationException::class);
