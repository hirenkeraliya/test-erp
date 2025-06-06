<?php

declare(strict_types=1);

use App\Domains\Admin\AdminQueries;
use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Http\Controllers\Admin\ExternalLoginController;
use App\Models\Admin;
use App\Models\ExternalCompany;
use App\Models\ExternalConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

test(
    'It calls the getByIdWithExternalConnection method throw Exception when externalCompany is null',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $this->mock(ExternalCompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithExternalConnection')
                ->once()
                ->andReturn(null);
        });

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $externalLoginController = new ExternalLoginController();
        $externalLoginController->getExternalLoginDetails(1, $request);
    }
)->throws(HttpException::class, 'External connection not active.');

test(
    'It calls the getByIdWithExternalConnection method of the ExternalCompanyQueries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

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

        $this->mock(AdminQueries::class, function ($mock): void {
            $mock->shouldReceive('updateExternalLoginToken')
                ->once();
        });

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Admin => $admin);

        $externalLoginController = new ExternalLoginController();
        $response = $externalLoginController->getExternalLoginDetails(1, $request);
    }
);

test(
    'It calls the logging method throw Exception token expired when validation failed.',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        Http::fake([
            'https://test.com/api/external-connection/admin-verify-external-token?token=123456' => Http::response([
                'staff_id' => '154',
                'company_id' => 1,
            ], 200),
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

        setCompanyIdInSession($companyId);

        $this->mock(AdminQueries::class, function ($mock): void {
            $mock->shouldReceive('getByStaffIdAndCompanyId')
                ->once()
                ->andReturn(null);
        });

        Http::fake([
            'https://test.com/api/external-connection/admin-verify-external-token?token=123456' => Http::response([
                'staff_id' => '154',
                'company_id' => 1,
            ], 200),
        ]);

        $request = new Request([
            'token' => '123456',
            'url' => 'https://test.com',
        ]);

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

        setCompanyIdInSession($companyId);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $this->mock(AdminQueries::class, function ($mock) use ($admin): void {
            $mock->shouldReceive('getByStaffIdAndCompanyId')
                ->once()
                ->andReturn($admin);
        });

        Http::fake([
            'https://test.com/api/external-connection/admin-verify-external-token?token=123456' => Http::response([
                'staff_id' => '154',
                'company_id' => 1,
            ], 200),
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
