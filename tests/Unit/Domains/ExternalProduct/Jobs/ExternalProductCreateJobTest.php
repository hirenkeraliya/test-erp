<?php

declare(strict_types=1);

namespace App\Domains\ExternalProduct\Jobs;

use App\Domains\Company\CompanyQueries;
use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalConnection\Services\ExternalConnectionService;
use App\Domains\Product\ProductQueries;
use App\Models\Company;
use App\Models\ExternalCompany;
use App\Models\ExternalConnection;
use App\Models\MasterProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Config;

test(
    'ExternalProductCreateJob Calls then add new external product when product variant ',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $companyId = 1;

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->make([
                'id' => 1,
                'company_id' => $companyId,
                'article_number' => '123456',
                'brand_id' => 1,
                'department_id' => 1,
                'unit_of_measure_id' => 1,
                'variant_template_id' => 1,
                'vendor_id' => null,
                'has_batch' => false,
                'is_non_inventory' => false,
                'status' => true,
            ]);
        }

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

        if ($productVariant) {
            $masterProduct->productVariants = collect([$product]);
            $product->masterProduct = $masterProduct;
        }

        $externalConnection = ExternalConnection::factory()->make([
            'id' => 1,
            'url' => 'http://example.com',
            'token' => 'abc123',
            'approve_by_super_admin_id' => 1,
            'status' => 1,
        ]);

        $externalCompany = ExternalCompany::factory()->make([
            'id' => 1,
            'external_connection_id' => $externalConnection->id,
            'external_company_id' => 1,
        ]);

        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $externalCompany->externalConnection = $externalConnection;

        $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
            $mock->shouldReceive('getNameAndCodeById')
                ->once()
                ->andReturn($company);
        });

        $this->mock(ExternalCompanyQueries::class, function ($mock) use ($externalCompany): void {
            $mock->shouldReceive('getExternalCompanyWithRelationById')
                ->once()
                ->andReturn($externalCompany);
        });

        $this->mock(ProductQueries::class, function ($mock) use ($product): void {
            $mock->shouldReceive('getActiveProductByIdWithAllRelations')
                ->once()
                ->andReturn($product);
        });

        $this->mock(ExternalConnectionService::class, function ($mock): void {
            $mock->shouldReceive('sendProductDataExternalConnection')
                ->once();
        });

        ExternalProductCreateJob::dispatch($externalCompany->id, $product->id)->onQueue(
            config('horizon.default_queue_name')
        );
    }
)->with([[true], [false]]);
