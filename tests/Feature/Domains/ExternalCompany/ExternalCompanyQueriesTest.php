<?php

declare(strict_types=1);

use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalConnection\Enums\Statuses;
use App\Models\ExternalCompany;
use App\Models\ExternalConnection;

beforeEach(function (): void {
    $this->externalCompanyQueries = new ExternalCompanyQueries();
});

test('A new External company can be added', function (): void {
    $externalCompany = ExternalCompany::factory()->make()->toArray();
    $response = $this->externalCompanyQueries->addNew($externalCompany);

    $this->assertDatabaseHas('external_companies', [
        'external_connection_id' => $externalCompany['external_connection_id'],
        'external_company_id' => $externalCompany['external_company_id'],
        'name' => $externalCompany['name'],
        'code' => $externalCompany['code'],
        'email' => $externalCompany['email'],
        'fax' => $externalCompany['fax'],
        'address' => $externalCompany['address'],
    ]);

    expect($response->toArray())
        ->toHaveKey('external_connection_id', $externalCompany['external_connection_id'])
        ->toHaveKey('external_company_id', $externalCompany['external_company_id'])
        ->toHaveKey('name', $externalCompany['name'])
        ->toHaveKey('code', $externalCompany['code'])
        ->toHaveKey('email', $externalCompany['email'])
        ->toHaveKey('fax', $externalCompany['fax'])
        ->toHaveKey('address', $externalCompany['address']);
});

test('getByIdWithExternalConnection return External Company', function (): void {
    $externalConnection = ExternalConnection::factory()->create([
        'status' => Statuses::APPROVED->value,
    ]);

    $externalCompany = ExternalCompany::factory()->create([
        'external_connection_id' => $externalConnection->id,
    ]);

    $response = $this->externalCompanyQueries->getByIdWithExternalConnection($externalCompany->id);

    expect($response->toArray())
        ->toHaveKey('external_connection_id', $externalCompany->external_connection_id)
        ->toHaveKey('external_company_id', $externalCompany->external_company_id)
        ->toHaveKey('name', $externalCompany->name)
        ->toHaveKey('code', $externalCompany->code)
        ->toHaveKey('email', $externalCompany->email)
        ->toHaveKey('fax', $externalCompany->fax)
        ->toHaveKey('address', $externalCompany->address)
        ->toHaveKey('external_connection.id', $externalConnection->id)
        ->toHaveKey('external_connection.name', $externalConnection->name)
        ->toHaveKey('external_connection.url', $externalConnection->url)
        ->toHaveKey('external_connection.token', $externalConnection->token);
});

test('getById return External Company', function (): void {
    $externalConnection = ExternalConnection::factory()->create([
        'status' => Statuses::APPROVED->value,
    ]);

    $externalCompany = ExternalCompany::factory()->create([
        'external_connection_id' => $externalConnection->id,
    ]);
    $response = $this->externalCompanyQueries->getById($externalCompany->id);

    expect($response->toArray())
        ->toHaveKey('id', $externalCompany->id)
        ->toHaveKey('external_connection_id', $externalCompany->external_connection_id)
        ->toHaveKey('external_company_id', $externalCompany->external_company_id);
});

test('getAllCompanies method return all external company', function (): void {
    $externalConnection = ExternalConnection::factory()->create([
        'status' => Statuses::APPROVED->value,
    ]);

    $externalCompany = ExternalCompany::factory()->create([
        'external_connection_id' => $externalConnection->id,
    ]);

    $response = $this->externalCompanyQueries->getAllCompanies();
    expect($response->first()->toArray())
        ->toHaveKey('id', $externalCompany->id)
        ->toHaveKey('name', $externalCompany->name);
});

test('getByIdWithExternalCompanyId method return external company', function (): void {
    $externalConnection = ExternalConnection::factory()->create([
        'status' => Statuses::APPROVED->value,
    ]);

    $externalCompany = ExternalCompany::factory()->create([
        'external_connection_id' => $externalConnection->id,
    ]);

    $response = $this->externalCompanyQueries->getByIdWithExternalCompanyId($externalCompany->id);
    expect($response->toArray())
        ->toHaveKey('external_company_id', $externalCompany->external_company_id)
        ->toHaveKey('id', $externalCompany->id);
});

test('getExternalCompanyWithRelationById return All External Company', function (): void {
    $externalConnection = ExternalConnection::factory()->create([
        'status' => Statuses::APPROVED->value,
    ]);
    $externalCompany = ExternalCompany::factory()->create([
        'external_connection_id' => $externalConnection->id,
    ]);

    $response = $this->externalCompanyQueries->getExternalCompanyWithRelationById($externalCompany->id);
    expect($response->toArray())
        ->toHaveKeys([
            'id',
            'external_connection_id',
            'external_company_id',
            'name',
            'external_connection',
            'external_connection.id',
            'external_connection.name',
            'external_connection.url',
            'external_connection.token',
        ]);
});

test('getApprovedExternalCompaniesWithBasicColumns return External Company With Connection', function (): void {
    $externalConnection = ExternalConnection::factory()->create([
        'status' => Statuses::APPROVED->value,
    ]);

    ExternalCompany::factory()->create([
        'external_connection_id' => $externalConnection->id,
    ]);

    $response = $this->externalCompanyQueries->getApprovedExternalCompaniesWithBasicColumns();

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'external_company_id']);
});

test('delete method soft deletes the external company', function (): void {
    $externalConnection = ExternalConnection::factory()->create();
    $externalCompany = ExternalCompany::factory()->create([
        'external_connection_id' => $externalConnection->id,
    ]);

    $this->externalCompanyQueries->delete($externalConnection->id, $externalCompany->external_company_id);

    $this->assertSoftDeleted('external_companies', [
        'id' => $externalCompany->id,
        'external_connection_id' => $externalConnection->id,
        'external_company_id' => $externalCompany->external_company_id,
    ]);
});

test('An External company can be restored', function (): void {
    $externalConnection = ExternalConnection::factory()->create();
    $externalCompany = ExternalCompany::factory()->create([
        'external_connection_id' => $externalConnection->id,
    ]);

    $externalCompany->delete();

    $this->externalCompanyQueries->restore($externalConnection->id, $externalCompany->external_company_id);

    $this->assertDatabaseHas('external_companies', [
        'id' => $externalCompany->id,
        'external_connection_id' => $externalConnection->id,
        'external_company_id' => $externalCompany->external_company_id,
    ]);
});
