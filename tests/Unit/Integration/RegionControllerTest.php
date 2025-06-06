<?php

declare(strict_types=1);

use App\Domains\Region\RegionQueries;
use App\Http\Controllers\Api\Integration\RegionController;
use App\Models\Integration;
use Illuminate\Http\Request;

test('It calls the getAllRegions method of the regionQueries class', function (): void {
    $integration = Integration::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Integration => $integration);

    $regionsData = [
        [
            'id' => 100,
            'name' => 'Test Region',
        ],
    ];

    $this->mock(RegionQueries::class, function ($mock) use ($regionsData): void {
        $mock->shouldReceive('getAllByCompanyId')
            ->once()
            ->andReturn(collect($regionsData));
    });

    $regionController = new RegionController();
    $response = $regionController->getAllRegions($request);

    expect($response['regions']->first())->toHaveKeys(['id', 'name']);
});
