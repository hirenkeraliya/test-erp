<?php

declare(strict_types=1);

use App\Domains\ExternalConnection\DataObjects\ExternalConnectionData;
use App\Models\ExternalConnection;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->externalConnection = ExternalConnection::factory()->create([
        'name' => 'External Connection',
        'url' => 'http://externalconnection.com',
    ]);
});

test('user cannot add same External Connection name.', function (): void {
    $request = new Request([
        'name' => $this->externalConnection->name,
        'url' => $this->externalConnection->url,
    ]);

    ExternalConnectionData::validate($request);
})->throws(ValidationException::class);

test('user can add External Connection with different name and url.', function (): void {
    $request = new Request([
        'name' => 'New External Connection',
        'url' => 'https://newexternalconnection.com',
    ]);

    ExternalConnectionData::validate($request);
    $this->assertTrue(true);
});
