<?php

declare(strict_types=1);

use App\Domains\Product\Enums\ProductBatches;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\Statuses;
use App\Domains\ProductLoyaltyPoint\ProductLoyaltyPointQueries;
use App\Models\Company;
use App\Models\Membership;
use App\Models\Product;
use App\Models\ProductLoyaltyPoint;

beforeEach(function (): void {
    $this->productLoyaltyPointQueries = new ProductLoyaltyPointQueries();
});

test('a product loyalty point can be added', function (): void {
    $productLoyaltyPoint = ProductLoyaltyPoint::factory()->make();

    $this->productLoyaltyPointQueries->addNew(
        $productLoyaltyPoint->product_id,
        $productLoyaltyPoint->membership_id,
        $productLoyaltyPoint->points,
    );

    $this->assertDatabaseHas('product_loyalty_points', [
        'product_id' => $productLoyaltyPoint->product_id,
        'membership_id' => $productLoyaltyPoint->membership_id,
        'points' => $productLoyaltyPoint->points,
    ]);
});

test('if product is merged then the product id is updated', function (): void {
    $companyId = Company::factory()->create()->id;
    $productAId = Product::factory()->create()->id;
    $productBId = Product::factory()->create()->id;
    $membership = Membership::factory()->create([
        'company_id' => $companyId,
    ]);

    $productLoyaltyPoint = ProductLoyaltyPoint::factory()->create([
        'membership_id' => $membership->id,
        'product_id' => $productBId,
    ]);

    $this->productLoyaltyPointQueries->updateProductId($companyId, $productBId, $productAId);

    $this->assertDatabaseHas(ProductLoyaltyPoint::class, [
        'membership_id' => $membership->id,
        'product_id' => $productAId,
    ]);
});

test('existByProductLoyaltyPoint method returns result as expected', function (): void {
    $companyId = Company::factory()->create()->id;
    $productBId = Product::factory()->create([
        'company_id' => $companyId,
    ])->id;
    $membership = Membership::factory()->create([
        'company_id' => $companyId,
    ]);

    $productLoyaltyPoint = ProductLoyaltyPoint::factory()->create([
        'membership_id' => $membership->id,
        'product_id' => $productBId,
    ]);

    $response = $this->productLoyaltyPointQueries->existByProductLoyaltyPoint(
        $productLoyaltyPoint->membership_id,
        $productLoyaltyPoint->product_id
    );
    $this->assertTrue($response);

    $response = $this->productLoyaltyPointQueries->existByProductLoyaltyPoint(1, 1);
    $this->assertFalse($response);
});

test(
    'getLoyaltyPointProducts method returns the product loyalty point data with relations for export',
    function (): void {
        $companyId = Company::factory()->create()->id;

        $product = Product::factory()->create([
            'company_id' => $companyId,
            'compound_product_name' => 'XYZ',
            'code' => 'X1234',
            'status' => Statuses::ACTIVE->value,
        ]);

        $membership = Membership::factory()->create([
            'company_id' => $companyId,
        ]);

        $productLoyaltyPoint = ProductLoyaltyPoint::factory()->create([
            'membership_id' => $membership->id,
            'product_id' => $product->id,
            'points' => 10,
        ]);

        $response = $this->productLoyaltyPointQueries->getLoyaltyPointProducts([
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'status' => ProductStatuses::ACTIVE->value,
            'batch' => ProductBatches::ALL->value,
            'date_range' => null,
            'product_type_id' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'color_ids' => null,
            'size_ids' => null,
            'department_ids' => null,
            'article_numbers' => null,
            'tag_ids' => null,
            'style_ids' => null,
            'product_collection_ids' => null,
        ], $companyId);

        expect($response->first()->toArray())
            ->toHaveKey('product_id', $productLoyaltyPoint->product_id)
            ->toHaveKey('membership_id', $productLoyaltyPoint->membership_id)
            ->toHaveKey('points', $productLoyaltyPoint->points);
    }
);
