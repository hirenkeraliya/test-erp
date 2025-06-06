<?php

declare(strict_types=1);

use App\Domains\Style\StyleQueries;
use App\Http\Controllers\Api\Integration\StyleController;
use App\Models\Integration;
use Illuminate\Http\Request;

test('It calls the getAllStyles method of the styleQueries class', function (): void {
    $integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Integration => $integration);

    $stylesData = [
        [
            'id' => 100,
            'name' => 'Test Style',
        ],
    ];

    $this->mock(StyleQueries::class, function ($mock) use ($stylesData): void {
        $mock->shouldReceive('getAllByCompanyId')
            ->once()
            ->andReturn(collect($stylesData));
    });

    $styleController = new StyleController();
    $response = $styleController->getAllStyles($request);

    expect($response['styles']->first())->toHaveKeys(['id', 'name']);
});
