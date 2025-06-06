<?php

declare(strict_types=1);

use App\Domains\Color\ColorQueries;
use App\Http\Controllers\StoreManager\ColorController;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

test(
    'It calls the getFilteredColorsByCompanyId method of the color queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setStoreManagerStoreCompanyIdInSession($companyId);

        $colorQueries = $this->mock(ColorQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getFilteredColorsByCompanyId')
                ->once()
                ->with('ab', $companyId)
                ->andReturn(new Collection([]));
        });

        $colorController = new ColorController($colorQueries);
        $response = $colorController->getFilteredColors(new Request([
            'search_text' => 'ab',
        ]));

        expect($response['colors'])->toBeInstanceOf(Collection::class);
    }
);
