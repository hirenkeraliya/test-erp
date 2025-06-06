<?php

declare(strict_types=1);

use App\Domains\Currency\CurrencyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Http\Controllers\Api\Promoter\ConfigurationController;
use App\Models\Currency;
use App\Models\Promoter;
use Illuminate\Http\Request;

test('calls the getConfiguration method and return currency symbol', function (): void {
    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Promoter => $promoter);

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once();
    });

    $configurationController = new ConfigurationController();
    $response = $configurationController->getConfiguration($request);

    expect($response['currency_symbol']);
});
