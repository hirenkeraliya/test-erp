<?php

declare(strict_types=1);

use App\Domains\CategoryWiseDailyTotal\CategoryWiseDailyTotalQueries;
use App\Models\CategoryWiseDailyTotal;

test('getTotalSalesAmount methods returns daily sales data', function (): void {
    CategoryWiseDailyTotal::factory()->create([
        'date' => now(),
    ]);

    $categoryWiseDailyTotalQueries = resolve(CategoryWiseDailyTotalQueries::class);

    $response = $categoryWiseDailyTotalQueries->getTotalSalesAmount();

    expect($response->first()->toArray())
        ->toHaveKey('total_units_sold')
        ->toHaveKey('total_amount');
});

test('yearlySalesData methods returns yearly sales data', function (): void {
    CategoryWiseDailyTotal::factory()->create([
        'date' => now(),
    ]);

    $categoryWiseDailyTotalQueries = resolve(CategoryWiseDailyTotalQueries::class);

    $response = $categoryWiseDailyTotalQueries->yearlySalesData();

    expect($response->first())
        ->toHaveKey('year')
        ->toHaveKey('full_year_sales')
        ->toHaveKey('partial_sales');
});
