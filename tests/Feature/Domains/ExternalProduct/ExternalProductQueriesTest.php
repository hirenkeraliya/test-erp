<?php

declare(strict_types=1);

use App\Domains\ExternalProduct\Enums\ExternalProductStatuses;
use App\Domains\ExternalProduct\ExternalProductQueries;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Company;
use App\Models\ExternalCompany;
use App\Models\ExternalProduct;
use App\Models\MasterProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->externalProductQueries = new ExternalProductQueries();
});

test('A addNew method call and set duplicate status if upc with external company id exists.', function (): void {
    $companyId = Company::factory()->create([
        'id' => 1,
    ])->id;

    $externalCompanyId = ExternalCompany::factory()->create([
        'id' => 1,
    ])->id;

    $product = Product::factory()->create([
        'company_id' => $companyId,
    ]);

    ExternalProduct::factory()->create([
        'company_id' => $companyId,
        'external_company_id' => $externalCompanyId,
        'upc' => $product->upc,
        'product_name' => $product->name,
        'product_details' => $product->toArray(),
        'status' => ExternalProductStatuses::PENDING->value,
    ]);

    $this->externalProductQueries->addNew($product->toArray(), $externalCompanyId, $companyId);

    $this->assertDatabaseHas('external_products', [
        'company_id' => $companyId,
        'external_company_id' => $externalCompanyId,
        'product_name' => $product->name,
        'upc' => $product->upc,
        'status' => ExternalProductStatuses::DUPLICATE->value,
    ]);
});

test('A addNew method call and set pending status if upc with company id does not exists.', function (): void {
    $companyId = Company::factory()->create([
        'id' => 1,
    ])->id;
    $externalCompanyId = ExternalCompany::factory()->create([
        'id' => 1,
    ])->id;

    $product = Product::factory()->create([
        'company_id' => $companyId,
    ]);

    $this->externalProductQueries->addNew($product->toArray(), $externalCompanyId, $companyId);

    $externalProduct = ExternalProduct::query()
        ->select('id', 'company_id', 'external_company_id', 'product_name', 'upc')
        ->where('company_id', $companyId)
        ->where('external_company_id', $externalCompanyId)
        ->where('upc', $product->upc)
        ->first();

    $this->assertDatabaseHas('external_products', [
        'id' => $externalProduct->id,
        'company_id' => $companyId,
        'external_company_id' => $externalProduct->external_company_id,
        'product_name' => $externalProduct->product_name,
        'upc' => $externalProduct->upc,
        'status' => ExternalProductStatuses::PENDING->value,
    ]);
});

test('Supplier Catalog can be fetched', function (): void {
    $companyId = Company::factory()->create()->id;
    $externalCompanyId = ExternalCompany::factory()->create()->id;

    $product = Product::factory()->create([
        'company_id' => $companyId,
    ]);

    ExternalProduct::factory()->create([
        'company_id' => $companyId,
        'external_company_id' => $externalCompanyId,
        'upc' => $product->upc,
        'product_name' => $product->name,
        'product_details' => $product->toArray(),
        'status' => ExternalProductStatuses::PENDING->value,
    ]);

    $response = $this->externalProductQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
        'status' => null,
        'date_range' => null,
    ], $product->company_id);
    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKeys(['external_company_id', 'upc', 'product_name', 'product_details', 'status']);
});

test(
    'A markAsApproved method call and change status to in progress as expected when product variant is.',
    function (bool $productVariant): void {
        Config::set('app.product_variant', $productVariant);

        $admin = Admin::factory()->create();
        $externalCompany = ExternalCompany::factory()->create();
        $companyId = Company::factory()->create()->id;

        $brand = Brand::factory()->create();

        if ($productVariant) {
            $masterProduct = MasterProduct::factory()->create();
            $masterProduct->brand = $brand;
            $masterProduct->brand_id = $brand->id;
        }

        $productDetails = [
            'id' => '1',
            'name' => 'abc',
            'upc' => '123aaaa',
        ];

        if ($productVariant) {
            $productDetails = array_merge($productDetails, [
                'master_product' => $masterProduct,
            ]);
        } else {
            $productDetails = array_merge($productDetails, [
                'brand' => $brand,
            ]);
        }

        $externalProduct = ExternalProduct::factory()->create([
            'company_id' => $companyId,
            'external_company_id' => $externalCompany->id,
            'upc' => '123aaaa',
            'product_name' => 'abc',
            'product_details' => $productDetails,
            'status' => ExternalProductStatuses::PENDING->value,
        ]);

        $this->externalProductQueries->markAsApproved([$externalProduct->id], $admin, $companyId);

        $externalProduct->refresh();

        $this->assertDatabaseHas('external_products', [
            'id' => $externalProduct->id,
            'company_id' => $companyId,
            'external_company_id' => $externalProduct->external_company_id,
            'product_name' => $externalProduct->product_name,
            'upc' => $externalProduct->upc,
            'status' => ExternalProductStatuses::CREATED->value,
        ]);
    }
)->with([[true], [false]]);

test(
    'A markAsApproved method call and change status to duplicate when duplicate upc found in product as expected.',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $product = Product::factory()->create([
            'company_id' => $companyId,
        ]);
        $admin = Admin::factory()->create();

        $externalProduct = ExternalProduct::factory()->create([
            'company_id' => $companyId,
            'external_company_id' => $product->company_id,
            'upc' => $product->upc,
            'product_name' => $product->name,
            'product_details' => $product->toArray(),
            'status' => ExternalProductStatuses::PENDING->value,
        ]);

        $this->externalProductQueries->markAsApproved([$externalProduct->id], $admin, $companyId);

        $externalProduct->refresh();

        $this->assertDatabaseHas('external_products', [
            'id' => $externalProduct->id,
            'company_id' => $companyId,
            'external_company_id' => $externalProduct->external_company_id,
            'product_name' => $externalProduct->product_name,
            'upc' => $externalProduct->upc,
            'status' => ExternalProductStatuses::DUPLICATE->value,
        ]);
    }
);

test('A markAsRejected method call and change status to reject as expected.', function (): void {
    $admin = Admin::factory()->create();
    $externalCompany = ExternalCompany::factory()->create();
    $companyId = Company::factory()->create()->id;

    $externalProduct = ExternalProduct::factory()->create([
        'company_id' => $companyId,
        'external_company_id' => $externalCompany->id,
        'upc' => '123aaaa',
        'product_name' => 'abc',
        'product_details' => [
            'id' => '1',
            'name' => 'abc',
            'upc' => '123aaaa',
        ],
        'status' => ExternalProductStatuses::PENDING->value,
    ]);

    $this->externalProductQueries->markAsRejected([$externalProduct->id], $admin, $companyId);

    $externalProduct->refresh();

    $this->assertDatabaseHas('external_products', [
        'id' => $externalProduct->id,
        'company_id' => $companyId,
        'external_company_id' => $externalProduct->external_company_id,
        'product_name' => $externalProduct->product_name,
        'upc' => $externalProduct->upc,
        'status' => ExternalProductStatuses::REJECTED->value,
    ]);
});

test(
    'A getExternalProductByIdsAndCompanyId method call and return list external product by id and company id as expected.',
    function (): void {
        $externalCompany = ExternalCompany::factory()->create();
        $companyId = Company::factory()->create()->id;

        $externalProduct = ExternalProduct::factory()->create([
            'company_id' => $companyId,
            'external_company_id' => $externalCompany->id,
            'upc' => '123aaaa',
            'product_name' => 'abc',
            'product_details' => [
                'id' => '1',
                'name' => 'abc',
                'upc' => '123aaaa',
            ],
            'status' => ExternalProductStatuses::PENDING->value,
        ]);

        $response = $this->externalProductQueries->getExternalProductByIdsAndCompanyId(
            [$externalProduct->id],
            $companyId,
            ExternalProductStatuses::PENDING->value
        );

        expect($response->first()->toArray())
            ->toHaveKey('id', $externalProduct->id)
            ->toHaveKey('company_id', $externalProduct->company_id)
            ->toHaveKey('external_company_id', $externalProduct->external_company_id)
            ->toHaveKey('status', $externalProduct->status)
            ->toHaveKey('upc', $externalProduct->upc)
            ->toHaveKey('product_details', $externalProduct->product_details);
    }
);

test(
    'A getExternalProductByIdAndCompanyId method call and return list external product by id and company id as expected.',
    function (): void {
        $externalCompany = ExternalCompany::factory()->create();
        $companyId = Company::factory()->create()->id;

        $externalProduct = ExternalProduct::factory()->create([
            'company_id' => $companyId,
            'external_company_id' => $externalCompany->id,
            'upc' => '123aaaa',
            'product_name' => 'abc',
            'product_details' => [
                'id' => '1',
                'name' => 'abc',
                'upc' => '123aaaa',
            ],
            'status' => ExternalProductStatuses::PENDING->value,
        ]);

        $response = $this->externalProductQueries->getExternalProductByIdAndCompanyId(
            $externalProduct->id,
            $companyId,
        );

        expect($response->toArray())
            ->toHaveKey('id', $externalProduct->id)
            ->toHaveKey('company_id', $externalProduct->company_id)
            ->toHaveKey('external_company_id', $externalProduct->external_company_id)
            ->toHaveKey('status', $externalProduct->status)
            ->toHaveKey('upc', $externalProduct->upc)
            ->toHaveKey('product_details', $externalProduct->product_details);
    }
);

test(
    'A changeStatus method call and change status as expected.',
    function (): void {
        $externalCompany = ExternalCompany::factory()->create();
        $companyId = Company::factory()->create()->id;

        $externalProduct = ExternalProduct::factory()->create([
            'company_id' => $companyId,
            'external_company_id' => $externalCompany->id,
            'upc' => '123aaaa',
            'product_name' => 'abc',
            'product_details' => [
                'id' => '1',
                'name' => 'abc',
                'upc' => '123aaaa',
            ],
            'status' => ExternalProductStatuses::PENDING->value,
        ]);

        $this->externalProductQueries->changeStatus($externalProduct, ExternalProductStatuses::IN_PROGRESS->value);

        $externalProduct->refresh();
        $this->assertDatabaseHas('external_products', [
            'id' => $externalProduct->id,
            'company_id' => $companyId,
            'external_company_id' => $externalProduct->external_company_id,
            'product_name' => $externalProduct->product_name,
            'upc' => $externalProduct->upc,
            'status' => ExternalProductStatuses::IN_PROGRESS->value,
        ]);
    }
);
