<?php

declare(strict_types=1);

use App\Domains\ExternalConnection\DataObjects\ExternalConnectionData;
use App\Domains\ExternalConnection\Enums\Statuses;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Models\ExternalConnection;

beforeEach(function (): void {
    $this->externalConnectionA = ExternalConnection::factory()->create([
        'name' => 'ABC',
        'token' => 'ABC123456789',
        'status' => Statuses::APPROVED->value,
        'url' => 'http://externalconnection.com',
    ]);

    $this->externalConnectionB = ExternalConnection::factory()->create([
        'name' => 'DEF',
        'url' => 'http://externalconnectionb.com',
    ]);

    $this->externalConnectionQueries = new ExternalConnectionQueries();
});

test('External Connections can be searched', function (): void {
    $response = $this->externalConnectionQueries->listQuery([
        'search_text' => 'DEF',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->externalConnectionB->name)
        ->toHaveKey('url', $this->externalConnectionB->url);
});

test('External Connections are returned as per page', function (): void {
    $response = $this->externalConnectionQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ]);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->externalConnectionB->name);
});

test('External Connections can be sorted by id', function (): void {
    $response = $this->externalConnectionQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'per_page' => 15,
    ]);

    $this->assertEquals(2, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->externalConnectionA->name);

    expect($response->getCollection()->last()->toArray())
        ->toHaveKey('name', $this->externalConnectionB->name);
});

test('A new External Connection can be added', function (): void {
    $response = $this->externalConnectionQueries->addNew(new ExternalConnectionData('Test', 'https://test.com'));

    $this->assertDatabaseHas('external_connections', [
        'name' => 'Test',
        'url' => 'https://test.com',
    ]);

    expect($response->toArray())
        ->toHaveKey('name', 'Test')
        ->toHaveKey('url', 'https://test.com');
});

test('A External Connection can be fetched', function (): void {
    $response = $this->externalConnectionQueries->getById($this->externalConnectionA->id);
    expect($response->toArray())
        ->toHaveKey('name', $this->externalConnectionA->name);
});

test('A External Connection can be updated', function (): void {
    $this->externalConnectionQueries->update(
        new ExternalConnectionData('Test', 'https://test.com'),
        $this->externalConnectionA->id,
    );

    $this->assertDatabaseHas('external_connections', [
        'name' => 'Test',
        'url' => 'https://test.com',
    ]);
});

test('A External Connection can be reject', function (): void {
    $this->externalConnectionQueries->reject($this->externalConnectionA->id);

    $this->assertDatabaseHas('external_connections', [
        'name' => $this->externalConnectionA->name,
        'url' => $this->externalConnectionA->url,
        'status' => Statuses::REJECTED->value,
    ]);
});

test('A External Connection can be approve', function (): void {
    $response = $this->externalConnectionQueries->approve($this->externalConnectionA->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->externalConnectionA->name)
        ->toHaveKey('url', $this->externalConnectionA->url)
        ->toHaveKey('status', Statuses::APPROVED->value);

    $this->assertDatabaseHas('external_connections', [
        'name' => $this->externalConnectionA->name,
        'url' => $this->externalConnectionA->url,
        'status' => Statuses::APPROVED->value,
    ]);
});

test('addNewWithApprove method External Connection can be added', function (): void {
    $response = $this->externalConnectionQueries->addNewWithApprove(
        new ExternalConnectionData('Test', 'https://test.com')
    );

    $this->assertDatabaseHas('external_connections', [
        'name' => 'Test',
        'url' => 'https://test.com',
    ]);

    expect($response->toArray())
        ->toHaveKey('name', 'Test')
        ->toHaveKey('url', 'https://test.com');
});

test('getAll fetch all External Connection', function (): void {
    $response = $this->externalConnectionQueries->getAll($this->externalConnectionA->id);

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->externalConnectionA->name)
        ->toHaveKey('url', $this->externalConnectionA->url);
});

test('existsByToken method returns boolean as expected', function (): void {
    $response = $this->externalConnectionQueries->existsByToken($this->externalConnectionA->token);
    $this->assertTrue($response);

    $response = $this->externalConnectionQueries->existsByToken('123135415');
    $this->assertFalse($response);
});
