<?php

declare(strict_types=1);

use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalLocation\ExternalLocationQueries;
use App\Domains\ExternalLocation\Jobs\ExternalLocationUpdateJob;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\ExternalCompany;
use App\Models\ExternalConnection;
use App\Models\ExternalLocation;
use Illuminate\Support\Facades\Http;

test(
    'ExternalLocationUpdateJob job calls respective methods',
    function (): void {
        Http::fake([
            'http://example.com/api/external-connection/get-locations' => Http::response([
                [
                    'id' => 1,
                    'name' => 'test',
                    'code' => 'abc123',
                    'email' => 'test@gmail.com',
                    'phone' => 'test',
                    'address_line_1' => 'test',
                    'address_line_2' => 'test',
                    'city' => 'test',
                    'area_code' => 'test',
                    'fax' => 'test',
                    'type_id' => LocationTypes::STORE->value,
                ],
                [
                    'id' => 2,
                    'name' => 'test',
                    'code' => 'abc123',
                    'email' => 'test@gmail.com',
                    'phone' => 'test',
                    'address_line_1' => 'test',
                    'address_line_2' => 'test',
                    'city' => 'test',
                    'area_code' => 'test',
                    'fax' => 'test',
                    'type_id' => LocationTypes::WAREHOUSE->value,
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

        $externalCompany->externalConnection = $externalConnection;
        $externalCompany->externalLocations = ExternalLocation::factory(2)->make([
            'id' => 1,
            'external_company_id' => 1,
            'external_location_id' => 1,
        ]);

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('getByIdWithExternalConnection')
                ->once()
                ->andReturn($externalCompany);
        });

        $this->mock(ExternalLocationQueries::class, function ($mock): void {
            $mock->shouldReceive('update')
                ->times(1);
            $mock->shouldReceive('addNew')
                ->once();
        });

        ExternalLocationUpdateJob::dispatch(123)->onQueue(config('horizon.default_queue_name'));
    }
);
