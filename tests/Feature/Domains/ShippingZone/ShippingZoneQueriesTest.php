<?php

declare(strict_types=1);

use App\Domains\ShippingZone\DataObjects\ShippingZoneData;
use App\Domains\ShippingZone\ShippingZoneQueries;
use App\Models\Company;
use App\Models\Country;
use App\Models\ShippingZone;
use App\Models\State;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->country = Country::factory()->create();
    $this->companyId = $this->company->id;

    $this->shippingZoneA = ShippingZone::factory()->create([
        'company_id' => $this->companyId,
        'country_id' => $this->country,
        'name' => 'DEF',
    ]);

    $this->shippingZoneB = ShippingZone::factory()->create([
        'company_id' => $this->companyId,
        'country_id' => $this->country,
        'name' => 'ABC',
    ]);

    $this->shippingZoneQueries = new ShippingZoneQueries();
});

test('shipping zone can be searched', function (): void {
    $response = $this->shippingZoneQueries->listQuery([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->shippingZoneA->name);
});

test('new shipping zone can be added', function (): void {
    $state = State::factory()->create([
        'country_id' => $this->country->id,
    ]);

    $stateIds = [$state->id];

    $this->shippingZoneQueries->addNew(
        new ShippingZoneData('malaysia zone', $this->country->id, $stateIds),
        $this->companyId
    );

    $this->assertDatabaseHas('shipping_zones', [
        'name' => 'malaysia zone',
        'company_id' => $this->companyId,
        'country_id' => $this->country->id,
    ]);

    $this->assertDatabaseHas('shipping_zone_state', [
        'state_id' => $state->id,
    ]);
});

test('A shipping zone can be fetched', function (): void {
    $response = $this->shippingZoneQueries->getById($this->shippingZoneA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('name', $this->shippingZoneA->name)
        ->toHaveKey('country_id', $this->shippingZoneA->country_id);
});

test('A shipping zone can be updated', function (): void {
    $stateA = State::factory()->create([
        'country_id' => $this->country->id,
    ]);

    $this->shippingZoneA->states()->sync([$stateA->id]);

    $stateB = State::factory()->create([
        'country_id' => $this->country->id,
    ]);
    $this->shippingZoneQueries->update(
        new ShippingZoneData('malaysia zone', $this->country->id, [$stateB->id]),
        $this->shippingZoneA->id,
        $this->companyId,
    );

    $this->assertDatabaseHas('shipping_zones', [
        'name' => 'malaysia zone',
        'company_id' => $this->companyId,
        'country_id' => $this->country->id,
    ]);

    $this->assertDatabaseHas('shipping_zone_state', [
        'shipping_zone_id' => $this->shippingZoneA->id,
        'state_id' => $stateB->id,
    ]);
});
