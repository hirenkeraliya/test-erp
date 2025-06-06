<?php

declare(strict_types=1);

use App\Domains\CountryChannelReference\CountryChannelReferenceQueries;
use App\Models\Country;
use App\Models\CountryChannelReference;
use App\Models\SaleChannel;

beforeEach(function (): void {
    $this->countryChannelReferenceQueries = new CountryChannelReferenceQueries();
});

test('a country channel reference can be added', function (): void {
    $country = Country::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $countryChannelReferenceRecord = CountryChannelReference::factory()->make([
        'country_id' => $country,
        'sale_channel_id' => $saleChannelId,
        'external_country_id' => $country,
    ]);

    $this->countryChannelReferenceQueries->addNew($countryChannelReferenceRecord->toArray());

    $this->assertDatabaseHas(CountryChannelReference::class, $countryChannelReferenceRecord->toArray());
});

test('it calls the getByCountryIdAndSaleChannelId to get the external DreamPrice', function (): void {
    $countryId = Country::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $countryChannelReference = CountryChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'country_id' => $countryId,
        'external_country_id' => 1,
    ]);

    $response = $this->countryChannelReferenceQueries->getByCountryIdAndSaleChannelId($countryId, $saleChannelId);

    expect($response)
        ->toHaveKey('id', $countryChannelReference->getKey())
        ->toHaveKey('country_id', $countryId)
        ->toHaveKey('external_country_id', 1);
});
