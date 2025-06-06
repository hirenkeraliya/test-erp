<?php

declare(strict_types=1);

use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Http\Controllers\Api\Member\ConfigurationController;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Location;
use App\Models\Member;
use Illuminate\Http\Request;

test('calls the getConfiguration method and return currency symbol', function (): void {
    $company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $member = Member::factory()->make([
        'id' => 1,
        'created_location_id' => $location->id,
        'company_id' => $company->id,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Member => $member);

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $configurationController = new ConfigurationController();
    $response = $configurationController->getConfiguration($request);

    expect($response['currency_symbol']);
});
