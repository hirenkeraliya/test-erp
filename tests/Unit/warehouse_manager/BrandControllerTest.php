<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Http\Controllers\WarehouseManager\BrandController;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

test(
    'It calls the getFilteredBrandsByCompanyId method of the brand queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setWarehouseManagerWarehouseCompanyIdInSession($companyId);

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
