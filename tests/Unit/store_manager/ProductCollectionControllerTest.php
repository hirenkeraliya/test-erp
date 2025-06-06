<?php

declare(strict_types=1);

use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Http\Controllers\StoreManager\ProductCollectionController;
use Illuminate\Http\Request;

test(
    'It calls the getFilteredProductCollections method of the product collection queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $productCollectionQueries = $this->mock(ProductCollectionQueries::class, function ($mock): void {
            $mock->shouldReceive('getFilteredProductCollectionsByCompanyId')
                ->once();
        });

        $request = new Request([
            'search_text' => 'ABC',
        ]);

        $productCollectionController = new ProductCollectionController($productCollectionQueries);
        $response = $productCollectionController->getFilteredProductCollections($request);

        expect($response)->toHaveKey('productCollections');
    }
);
