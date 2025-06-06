<?php

declare(strict_types=1);

use App\Domains\State\StateQueries;
use App\Http\Controllers\Api\Common\StateController;

test('getAllStates returns array with records', function (): void {
    $this->mock(StateQueries::class, function ($mock): void {
        $mock->shouldReceive('getAllStates')
            ->once()
            ->andReturn(collect());
    });

    $controller = new StateController();
    $response = $controller->getAllStates();

    expect($response)->toBeArray();
    expect($response)->toHaveKey('states');
});
