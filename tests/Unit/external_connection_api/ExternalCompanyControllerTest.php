<?php

declare(strict_types=1);

use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Http\Controllers\Api\ExternalConnection\ExternalCompanyController;
use App\Models\ExternalConnection;
use Illuminate\Http\Request;

test('It calls the delete method of ExternalCompanyQueries with correct parameters', function (): void {
    $token = 'test-token';
    $externalCompanyId = 1;

    $request = new Request([
        'token' => $token,
        'external_company_id' => $externalCompanyId,
    ]);

    $externalConnection = ExternalConnection::factory()->make([
        'id' => 1,
    ]);

    $this->mock(ExternalConnectionQueries::class, function ($mock) use ($token, $externalConnection): void {
        $mock->shouldReceive('getByToken')
            ->once()
            ->with($token)
            ->andReturn($externalConnection);
    });

    $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalConnection, $externalCompanyId): void {
        $mock->shouldReceive('delete')
            ->once()
            ->with($externalConnection->id, $externalCompanyId);
    });

    $companyController = resolve(ExternalCompanyController::class);
    $companyController->externalCompanyArchive($request);
});

test('It calls the restore method of ExternalCompanyQueries with correct parameters', function (): void {
    $token = 'test-token';
    $externalCompanyId = 1;

    $request = new Request([
        'token' => $token,
        'external_company_id' => $externalCompanyId,
    ]);

    $externalConnection = ExternalConnection::factory()->make([
        'id' => 1,
    ]);

    $this->mock(ExternalConnectionQueries::class, function ($mock) use ($token, $externalConnection): void {
        $mock->shouldReceive('getByToken')
            ->once()
            ->with($token)
            ->andReturn($externalConnection);
    });

    $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalConnection, $externalCompanyId): void {
        $mock->shouldReceive('restore')
            ->once()
            ->with($externalConnection->id, $externalCompanyId);
    });

    $companyController = resolve(ExternalCompanyController::class);
    $companyController->externalCompanyRestore($request);
});
