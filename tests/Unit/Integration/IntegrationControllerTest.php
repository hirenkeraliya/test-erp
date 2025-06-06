<?php

declare(strict_types=1);

use App\Domains\Integration\DataObjects\IntegrationData;
use App\Domains\Integration\Enums\IntegrationConnections;
use App\Domains\Integration\IntegrationQueries;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Http\Controllers\SuperAdmin\IntegrationController;
use App\Models\Integration;
use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

test('It fetches integration list', function (): void {
    $request = new Request([
        'search_text' => 'test',
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'per_page' => 10,
    ]);

    $this->mock(IntegrationQueries::class, function ($mock): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 10));
    });

    $integrationController = new IntegrationController(resolve(IntegrationQueries::class));
    $response = $integrationController->fetchIntegration($request);

    expect($response)->toHaveKey('total_records');
    expect($response)->toHaveKey('data');
});

test('It creates a new integration', function (): void {
    $integrationData = new IntegrationData(
        'Test Integration',
        1,
        IntegrationConnections::NETSUITE->value,
        'https://example.url',
        'TestSecret',
        [],
    );

    $this->mock(IntegrationQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn('test-token');
    });

    $integrationController = new IntegrationController(resolve(IntegrationQueries::class));
    $response = $integrationController->store($integrationData);

    expect($response['token'])->toBe('test-token');
});

test('It updates an existing integration', function (): void {
    $integration = Integration::factory()->make();

    $integrationData = new IntegrationData(
        $integration->name,
        $integration->company_id,
        IntegrationConnections::NETSUITE->value,
        $integration->url,
        $integration->secret,
        [],
    );

    $this->mock(IntegrationQueries::class, function ($mock) use ($integration): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($integration);
        $mock->shouldReceive('update')
            ->once();
    });

    $integrationController = new IntegrationController(resolve(IntegrationQueries::class));
    $response = $integrationController->update($integrationData, 1);

    expect($response->getSession()->get('success'))->toBe('Integration updated successfully.');
});

test('It refreshes access token', function (): void {
    $request = new Request([
        'username' => 'admin',
        'password' => 'password',
    ]);

    $superAdmin = new SuperAdmin();
    $superAdmin->password = Hash::make('password');

    $this->mock(SuperAdminQueries::class, function ($mock) use ($superAdmin): void {
        $mock->shouldReceive('getByUsername')
            ->once()
            ->andReturn($superAdmin);
    });

    $this->mock(IntegrationQueries::class, function ($mock): void {
        $mock->shouldReceive('refreshToken')
            ->once()
            ->andReturn('new-access-token');
    });

    $integrationController = new IntegrationController(resolve(IntegrationQueries::class));
    $response = $integrationController->refreshAccessToken($request, 1);

    expect($response['access_token'])->toBe('new-access-token');
});

test('It sets the status of an integration', function (): void {
    $this->mock(IntegrationQueries::class, function ($mock): void {
        $mock->shouldReceive('updateStatus')
            ->once();
    });

    $integrationController = new IntegrationController(resolve(IntegrationQueries::class));
    $response = $integrationController->setStatus(1, true);

    expect($response->getSession()->get('success'))->toBe('Status changed successfully.');
});
