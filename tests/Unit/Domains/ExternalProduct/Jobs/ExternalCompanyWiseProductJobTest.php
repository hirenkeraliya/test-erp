<?php

declare(strict_types=1);

namespace App\Domains\ExternalProduct\Jobs;

use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Models\ExternalCompany;
use App\Models\ExternalConnection;
use App\Models\Product;
use Illuminate\Support\Facades\Queue;

test(
    'ExternalCompanyWiseProductJob Calls then Pushes ExternalProductCreateJob',
    function (): void {
        Queue::fake()->except(ExternalCompanyWiseProductJob::class);

        $companyId = 1;

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'article_number' => '123456',
            'is_non_inventory' => false,
            'status' => true,
        ]);

        $externalConnection = ExternalConnection::factory()->make([
            'id' => 1,
            'url' => 'http://example.com',
            'token' => 'abc123',
            'approve_by_super_admin_id' => 1,
            'status' => 1,
        ]);

        $externalCompany = ExternalCompany::factory()->make([
            'id' => 123,
            'external_connection_id' => $externalConnection->id,
            'external_company_id' => 1,
        ]);

        $externalCompany->externalConnection = $externalConnection;

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('getApprovedExternalCompaniesWithBasicColumns')
                ->once()
                ->andReturn(collect([$externalCompany]));
        });

        ExternalCompanyWiseProductJob::dispatch($product->id)->onQueue(config('horizon.default_queue_name'));
        Queue::assertPushed(ExternalProductCreateJob::class, 1);
    }
);
