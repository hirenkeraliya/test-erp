<?php

declare(strict_types=1);

use App\Domains\Style\StyleQueries;
use App\Http\Controllers\WarehouseManager\StyleController;
use App\Models\Style;
use Illuminate\Http\Request;

test('It calls the getFilteredStylesByCompanyId method and returns proper response', function (): void {
    $companyId = 1;

    setWarehouseManagerWarehouseCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
    ];

    $style = Style::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
    ]);

    $styleQueries = $this->mock(StyleQueries::class, function ($mock) use ($style): void {
        $mock->shouldReceive('getFilteredStylesByCompanyId')
            ->once()
            ->andReturn(collect([$style]));
    });

    $styleController = new StyleController($styleQueries);

    $response = $styleController->getFilteredStyles(new Request($requestParameter));
    expect($response['styles']->first()->toArray())
        ->toHaveKey('id', $style->id)
        ->toHaveKey('name', $style->name);
});
