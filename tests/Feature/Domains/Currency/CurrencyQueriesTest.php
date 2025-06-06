<?php

declare(strict_types=1);

use App\Domains\Currency\CurrencyQueries;
use App\Models\Company;
use App\Models\Country;
use App\Models\Currency;

beforeEach(function (): void {
    $this->currencyQueries = new CurrencyQueries();
    $this->company = Company::factory()->create();
});

test(
    'getByCompanyId method returns the currency',
    function (): void {
        Country::insert([
            'iso2' => 'Ab',
            'name' => 'ABCD',
            'status' => true,
            'phone_code' => '1234',
            'iso3' => 'bc',
            'region' => 'south',
            'subregion' => 'south left',
        ]);

        $countryId = Country::first()->id;

        $this->company->default_country_id = $countryId;
        $this->company->save();

        $currency = Currency::insert([
            'country_id' => $countryId,
            'name' => 'Euro',
            'code' => 'EUR',
            'precision' => 2,
            'symbol' => '€',
            'symbol_native' => '€',
            'symbol_first' => 1,
            'decimal_mark' => '.',
            'thousands_separator' => ',',
        ]);

        $currency = Currency::first();

        $response = $this->currencyQueries->getByCompanyId($this->company->getKey());
        expect($response->first()->toArray())
            ->toHaveKey('id', $currency->getKey())
            ->toHaveKey('name', $currency->name);
    }
);
