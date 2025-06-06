<?php

declare(strict_types=1);

use App\Domains\Category\CategoryQueries;
use App\Http\Controllers\StoreManager\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

test(
    'It calls the getFilteredCategoriesByCompanyId method of the category queries class and returns proper response',
    function (): void {
        setStoreManagerStoreCompanyIdInSession();

        $categoryQueries = $this->mock(CategoryQueries::class, function ($mock): void {
            $mock->shouldReceive('getFilteredCategoriesByCompanyId')
                ->once()
                ->with('ab', 1)
                ->andReturn(new Collection([]));
        });

        $categoryController = new CategoryController($categoryQueries);
        $response = $categoryController->getFilteredCategories(new Request([
            'search_text' => 'ab',
        ]));

        expect($response['categories'])->toBeInstanceOf(Collection::class);
    }
);

test(
    'It calls the getParentByCompanyId method of the category queries class and returns proper response',
    function (): void {
        setStoreManagerStoreCompanyIdInSession();

        $categoryQueries = $this->mock(CategoryQueries::class, function ($mock): void {
            $mock->shouldReceive('getParentByCompanyId')
                ->once()
                ->with(1)
                ->andReturn(new Collection([]));
        });

        $categoryController = new CategoryController($categoryQueries);
        $response = $categoryController->getParentCategories();

        expect($response['categories'])->toBeInstanceOf(Collection::class);
    }
);
