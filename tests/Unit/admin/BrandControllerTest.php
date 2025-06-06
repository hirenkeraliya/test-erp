<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Http\Controllers\Admin\BrandController;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

test(
    'It calls the getFilteredBrandsByCompanyId method of the brand queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $brandQueries = $this->mock(BrandQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getFilteredBrandsByCompanyId')
                ->once()
                ->with('ab', $companyId)
                ->andReturn(new Collection([]));
        });

        $brandController = new BrandController($brandQueries);
        $response = $brandController->getFilteredBrands(new Request([
            'search_text' => 'ab',
        ]));

        expect($response['brands'])->toBeInstanceOf(Collection::class);
    }
);

test(
    'It calls the getBrandSalesSummary method of the BrandQueries class as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);
        $filterData = [
            'locationId' => null,
            'id' => null,
            'type' => null,
            'date' => '',
        ];

        $brandQueries = $this->mock(BrandQueries::class, function ($mock): void {
            $mock->shouldReceive('getBrandSalesSummary')
                ->once()
                ->andReturn(collect([]));
        });

        $brandController = new BrandController($brandQueries);
        $redirectResponse = $brandController->getBrandSalesSummary(new Request($filterData));

        expect($redirectResponse)
            ->toHaveKeys(['brands', 'total_sales', 'total_units_sold']);
    }
);
