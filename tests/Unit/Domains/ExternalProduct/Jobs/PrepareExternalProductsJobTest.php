<?php

declare(strict_types=1);

namespace App\Domains\ExternalProduct\Jobs;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\ExternalProduct\Enums\ExternalProductStatuses;
use App\Domains\ExternalProduct\ExternalProductQueries;
use App\Domains\Product\ProductQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\ExternalProduct;
use Illuminate\Support\Facades\Queue;

test(
    'PrepareExternalProductsJob Calls then prepare Supplier Catalog and create product',
    function (): void {
        Queue::fake()->except(PrepareExternalProductsJob::class);
        $company = Company::factory()->make([
            'id' => 1,
            'commission_type_id' => CommissionTypes::BY_PROMOTER->value,
            'default_country_id' => 1,
        ]);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $externalProduct = ExternalProduct::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'external_company_id' => $company->id,
            'approved_by_id' => $admin->id,
            'approved_by_type' => ModelMapping::ADMIN->name,
            'status' => ExternalProductStatuses::APPROVED->value,
        ]);

        $this->mock(ExternalProductQueries::class, function ($mock) use ($externalProduct): void {
            $mock->shouldReceive('changeStatus')
                ->once();

            $mock->shouldReceive('getExternalProductByIdAndCompanyId')
                ->once()
                ->andReturn($externalProduct);
        });

        $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByUpc')
                ->once()
                ->andReturn(false);
        });

        PrepareExternalProductsJob::dispatch($externalProduct->id, $externalProduct->company_id, $admin)->onQueue(
            config('horizon.default_queue_name')
        );
        Queue::assertPushed(CreateProductFromExternalProductJob::class, 1);
    }
);
