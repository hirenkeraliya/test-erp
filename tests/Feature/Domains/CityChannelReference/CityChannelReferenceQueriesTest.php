<?php

declare(strict_types=1);

use App\Domains\CityChannelReference\CityChannelReferenceQueries;
use App\Models\City;
use App\Models\CityChannelReference;
use App\Models\SaleChannel;

beforeEach(function (): void {
    $this->cityChannelReferenceQueries = new CityChannelReferenceQueries();
});

test('a city channel reference can be added', function (): void {
    $cityId = City::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $cityChannelReferenceRecord = CityChannelReference::factory()->make([
        'city_id' => $cityId,
        'sale_channel_id' => $saleChannelId,
        'external_city_id' => $cityId,
    ]);

    $this->cityChannelReferenceQueries->addNew($cityChannelReferenceRecord->toArray());

    $this->assertDatabaseHas(CityChannelReference::class, $cityChannelReferenceRecord->toArray());
});

test('it calls the getByCityIdAndSaleChannelId to get the external DreamPrice', function (): void {
    $cityId = City::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $cityChannelReference = CityChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'city_id' => $cityId,
        'external_city_id' => 1,
    ]);

    $response = $this->cityChannelReferenceQueries->getByCityIdAndSaleChannelId($cityId, $saleChannelId);

    expect($response)
        ->toHaveKey('id', $cityChannelReference->getKey())
        ->toHaveKey('city_id', $cityId)
        ->toHaveKey('external_city_id', $cityChannelReference->external_city_id);
});
