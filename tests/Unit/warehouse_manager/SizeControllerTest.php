<?php

declare(strict_types=1);

use App\Domains\Size\SizeQueries;
use App\Http\Controllers\WarehouseManager\SizeController;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

test(
    'It calls the getFilteredSizesByCompanyId method of the size queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setWarehouseManagerWarehouseCompanyIdInSession($companyId);

        $sizeQueries = $this->mock(SizeQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getFilteredSizesByCompanyId')
                ->once()
                ->with('ab', $companyId)
                ->andReturn(new Collection([]));
        });

        $sizeController = new SizeController($sizeQueries);
        $response = $sizeController->getFilteredSizes(new Request([
            'search_text' => 'ab',
        ]));

        expect($response['sizes'])->toBeInstanceOf(Collection::class);
    }
);
