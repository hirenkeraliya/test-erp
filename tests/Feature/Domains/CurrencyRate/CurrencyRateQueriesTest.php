<?php

declare(strict_types=1);

use App\Domains\Company\DataObjects\CurrencyRateData;
use App\Domains\CurrencyRate\CurrencyRateQueries;
use App\Models\Company;
use App\Models\Currency;
use App\Models\CurrencyRate;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->currency = Currency::factory()->create();

    $this->currencyB = Currency::factory()->create();

    $this->currencyRate = CurrencyRate::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
        'rate' => 5,
    ]);

    $this->currencyRateQueries = new CurrencyRateQueries();
});

test('it add method create the currency rate', function (): void {
    $data = [
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
        'rate' => 10.0000,
    ];

    $this->currencyRateQueries->add($data);

    $this->assertDatabaseHas('currency_rates', [
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
        'rate' => 10.0000,
    ]);
});

test('it call the deleteOldRate to delete old currency', function (): void {
    $this->currencyRateQueries->deleteOldRate($this->company->id);

    $this->assertDatabaseMissing('currency_rates', [
        'id' => $this->currencyRate->id,
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
    ]);
});

test('it call the getByCompanyId to return the collection', function (): void {
    $response = $this->currencyRateQueries->getByCompanyId($this->company->id, $this->currencyB->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->currencyRate->id)
        ->toHaveKey('currency_id', $this->currencyRate->currency_id)
        ->toHaveKey('rate', $this->currencyRate->rate);
});

test('it call the currencyRateUpdateByCompanyId to update the currencyRate', function (): void {
    $data = [
        'company_id' => $this->company->id,
        'currency_data' => [
            [
                'id' => $this->currencyRate->id,
                'rate' => 15.00,
            ],
        ],
    ];

    $currencyRateData = new CurrencyRateData(...$data);

    $response = $this->currencyRateQueries->currencyRateUpdateByCompanyId($currencyRateData);

    $this->assertDatabaseHas('currency_rates', [
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
        'rate' => 15.0000,
    ]);
});
