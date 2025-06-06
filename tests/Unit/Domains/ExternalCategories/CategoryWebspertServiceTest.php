<?php

declare(strict_types=1);

use App\Domains\ExternalCategories\ExternalCategoryQueries;
use App\Domains\ExternalCategories\Services\CategoryWebspertService;
use App\Models\SaleChannel;
use Illuminate\Support\Facades\Http;

test('fetchCategories method fetches and saves categories successfully', function (): void {
    $saleChannel = SaleChannel::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'secret' => 'test_secret',
        'default_location_id' => 1,
    ]);

    $responseBody = [
        'data' => [
            'categories' => [
                [
                    'category_id' => 1,
                    'category_name' => 'Category 1',
                    'parent_id' => 0,
                ],
                [
                    'category_id' => 2,
                    'category_name' => 'Category 2',
                    'parent_id' => 1,
                ],
            ],
        ],
    ];

    Http::fake([
        '*' => Http::response($responseBody, 200),
    ]);

    $this->mock(ExternalCategoryQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->twice();
        $mock->shouldReceive('getParentCategoryId')
            ->andReturn(0, 1);
    });

    $categoryWebspertService = new CategoryWebspertService();
    $categoryWebspertService->fetchCategories($saleChannel);
});

test('fetchCategories method logs error on failure', function (): void {
    $saleChannel = SaleChannel::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'secret' => 'test_secret',
        'default_location_id' => 1,
    ]);

    Http::fake([
        '*' => Http::response([
            'error' => 'Unauthorized',
        ], 401),
    ]);

    $this->mock(ExternalCategoryQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->never();
    });

    $categoryWebspertService = new CategoryWebspertService();
    $categoryWebspertService->fetchCategories($saleChannel);
});
