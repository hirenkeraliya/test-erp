<?php

declare(strict_types=1);

use App\Domains\ExternalLocation\ExternalLocationQueries;
use App\Models\ExternalLocation;

beforeEach(function (): void {
    $this->externalLocationQueries = new ExternalLocationQueries();
});

test('A new External location can be added', function (): void {
    $externalLocation = ExternalLocation::factory()->make()->toArray();
    $this->externalLocationQueries->addNew($externalLocation);

    $this->assertDatabaseHas('external_locations', [
        'external_company_id' => $externalLocation['external_company_id'],
        'external_location_id' => $externalLocation['external_location_id'],
        'type_id' => $externalLocation['type_id'],
        'name' => $externalLocation['name'],
        'code' => $externalLocation['code'],
        'email' => $externalLocation['email'],
        'phone' => $externalLocation['phone'],
        'address_line_1' => $externalLocation['address_line_1'],
        'address_line_2' => $externalLocation['address_line_2'],
        'city' => $externalLocation['city'],
        'area_code' => $externalLocation['area_code'],
    ]);
});

test('getByIdWithExternalCompanyAndExternalConnection return external location', function (): void {
    $externalLocation = ExternalLocation::factory()->create();
    $response = $this->externalLocationQueries->getByIdWithExternalCompanyAndExternalConnection($externalLocation->id);

    expect($response->toArray())
        ->toHaveKey('external_company_id', $externalLocation->external_company_id)
        ->toHaveKey('external_location_id', $externalLocation->external_location_id)
        ->toHaveKey('external_company')
        ->toHaveKey('external_company.external_connection');
});
