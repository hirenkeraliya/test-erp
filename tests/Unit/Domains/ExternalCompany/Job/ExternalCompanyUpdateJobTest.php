<?php

declare(strict_types=1);

use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalCompany\Jobs\ExternalCompanyUpdateJob;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Domains\ExternalLocation\Jobs\ExternalLocationUpdateJob;
use App\Domains\Notification\NotificationQueries;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Models\ExternalCompany;
use App\Models\ExternalConnection;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test(
    'ExternalCompanyUpdateJob Calls Respective Methods and Pushes ExternalLocationUpdateJob',
    function (): void {
        Queue::fake()->except(ExternalCompanyUpdateJob::class);

        Http::fake([
            'http://example.com/api/external-connection/get-companies?token=abc123' => Http::response([
                [
                    'id' => 1,
                    'name' => 'test',
                    'code' => 'abc123',
                    'email' => 'test@gmail.com',
                    'fax' => 'test',
                    'address' => 'test',
                    'social_security_number' => 'test',
                    'light_logo' => 'test',
                    'dark_logo' => 'test',
                    'email_footer_logo' => 'test',
                ],
            ], 200),
        ]);

        $externalConnection = ExternalConnection::factory()->make([
            'id' => 1,
            'url' => 'http://example.com',
            'token' => 'abc123',
            'approve_by_super_admin_id' => 1,
        ]);

        $externalCompany = ExternalCompany::factory()->make([
            'id' => 123,
            'external_connection_id' => 1,
            'external_company_id' => 1,
        ]);

        $superAdmin = SuperAdmin::factory()->make([
            'id' => 1,
        ]);

        $externalConnection->externalCompanies = collect([$externalCompany]);

        $this->mock(ExternalConnectionQueries::class, function ($mock) use ($externalConnection): void {
            $mock->shouldReceive('getAll')
                ->once()
                ->andReturn(collect([$externalConnection]));
        });

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('update')
                ->once()
                ->andReturn($externalCompany);

            $mock->shouldReceive('uploadLogos')
                ->once();
        });

        $this->mock(SuperAdminQueries::class, function ($mock) use ($superAdmin): void {
            $mock->shouldReceive('getAll')
                ->once()
                ->andReturn(collect([$superAdmin]));
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewWithNullValue')
                ->once();
        });

        ExternalCompanyUpdateJob::dispatch()->onQueue(config('horizon.default_queue_name'));

        Queue::assertPushed(ExternalLocationUpdateJob::class);
    }
);

test(
    'ExternalCompanyUpdateJob Dispatching and Method Calls with ExternalLocationUpdateJob Push',
    function (): void {
        Queue::fake()->except(ExternalCompanyUpdateJob::class);

        Http::fake([
            'http://example.com/api/external-connection/get-companies?token=abc123' => Http::response([
                [
                    'id' => 2,
                    'name' => 'test',
                    'code' => 'abc123',
                    'email' => 'test@gmail.com',
                    'fax' => 'test',
                    'address' => 'test',
                    'social_security_number' => 'test',
                    'light_logo' => 'test',
                    'dark_logo' => 'test',
                    'email_footer_logo' => 'test',
                ],
            ], 200),
        ]);

        $externalConnection = ExternalConnection::factory()->make([
            'id' => 1,
            'url' => 'http://example.com',
            'token' => 'abc123',
            'approve_by_super_admin_id' => 1,
        ]);

        $externalCompany = ExternalCompany::factory()->make([
            'id' => 123,
            'external_connection_id' => 1,
            'external_company_id' => 1,
        ]);

        $externalConnection->externalCompanies = collect([$externalCompany]);

        $superAdmin = SuperAdmin::factory()->make([
            'id' => 1,
        ]);

        $this->mock(ExternalConnectionQueries::class, function ($mock) use ($externalConnection): void {
            $mock->shouldReceive('getAll')
                ->once()
                ->andReturn(collect([$externalConnection]));
        });

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($externalCompany);

            $mock->shouldReceive('uploadLogos')
                ->once();
        });

        $this->mock(SuperAdminQueries::class, function ($mock) use ($superAdmin): void {
            $mock->shouldReceive('getAll')
                ->once()
                ->andReturn(collect([$superAdmin]));
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewWithNullValue')
                ->once();
        });

        ExternalCompanyUpdateJob::dispatch()->onQueue(config('horizon.default_queue_name'));

        Queue::assertPushed(ExternalLocationUpdateJob::class);
    }
);
