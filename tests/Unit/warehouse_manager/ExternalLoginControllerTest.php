<?php

declare(strict_types=1);

use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\WarehouseManager\ExternalLoginController;
use App\Models\ExternalCompany;
use App\Models\ExternalConnection;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

test(
    'It calls the getByIdWithExternalConnection method throw Exception when externalCompany is null',
    function (): void {
        $companyId = 1;

        setWarehouseManagerWarehouseCompanyIdInSession($companyId);

        $this->mock(ExternalCompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithExternalConnection')
                ->once()
                ->andReturn(null);
        });

        $warehouseManager = WarehouseManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);

        $externalLoginController = new ExternalLoginController();
        $externalLoginController->getExternalLoginDetails(1, $request);
    }
)->throws(HttpException::class, 'External connection not active.');

test(
    'It calls the getByIdWithExternalConnection method of the ExternalCompanyQueries class and returns proper response',
    function (): void {
        $companyId = 1;

        setWarehouseManagerWarehouseCompanyIdInSession($companyId);

        $externalCompany = ExternalCompany::factory()->make([
            'id' => 1,
            'external_connection_id' => 1,
        ]);

        $externalCompany->externalConnection = ExternalConnection::factory()->make([
            'id' => 1,
            'url' => 'https:://test.com',
        ]);

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('getByIdWithExternalConnection')
                ->once()
                ->andReturn($externalCompany);
        });

        $this->mock(WarehouseManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('updateExternalLoginToken')
                ->once();
        });

        $warehouseManager = WarehouseManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);

        $externalLoginController = new ExternalLoginController();
        $response = $externalLoginController->getExternalLoginDetails(1, $request);
    }
);

test(
    'It calls the logging method throw exception token expired when validation failed.',
    function (): void {
        $companyId = 1;

        setWarehouseManagerWarehouseCompanyIdInSession($companyId);

        Http::fake([
            'https://test.com/api/external-connection/warehouse-manager-verify-external-token?token=123456' => Http::response([
                
                'staff_id' => '154',
                'company_id' => 1,
            ],
                200
            ),
        ]);

        $request = new Request([
            'token' => null,
            'url' => 'https://test.com',
        ]);

        $externalLoginController = new ExternalLoginController();
        $response = $externalLoginController->logging($request);

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertEquals('Token Expire. Please try again.', $response->getSession()->all()['error']);
    }
);

test(
    'It calls the logging method throw Exception when user not found with staff id',
    function (): void {
        $companyId = 1;

        setWarehouseManagerWarehouseCompanyIdInSession($companyId);

        Http::fake([
            'https://test.com/api/external-connection/warehouse-manager-verify-external-token?token=123456' => Http::response(
                [
                    'staff_id' => '154',
                    'company_id' => 1,
                ],
                200
            ),
        ]);

        $request = new Request([
            'token' => '123456',
            'url' => 'https://test.com',
        ]);

        $this->mock(WarehouseManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('getByStaffIdAndCompanyId')
                ->once()
                ->andReturn(null);
        });

        $externalLoginController = new ExternalLoginController();
        $response = $externalLoginController->logging($request);

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertEquals('User not found with similar staff id.', $response->getSession()->all()['error']);
    }
);

test(
    'It calls the logging method returns proper response',
    function (): void {
        $companyId = 1;

        setWarehouseManagerWarehouseCompanyIdInSession($companyId);

        $warehouseManager = WarehouseManager::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $this->mock(WarehouseManagerQueries::class, function ($mock) use ($warehouseManager): void {
            $mock->shouldReceive('getByStaffIdAndCompanyId')
                ->once()
                ->andReturn($warehouseManager);
        });

        Http::fake([
            'https://test.com/api/external-connection/warehouse-manager-verify-external-token?token=123456' => Http::response([
                
                'staff_id' => '154',
                'company_id' => 1,
            ],
                200
            ),
        ]);

        $request = new Request([
            'token' => '123456',
            'url' => 'https://test.com',
        ]);

        $externalLoginController = new ExternalLoginController();
        $response = $externalLoginController->logging($request);
        $this->assertEquals(302, $response->getStatusCode());

        $this->assertEquals('You have successfully logged in.', $response->getSession()->all()['success']);
    }
);
