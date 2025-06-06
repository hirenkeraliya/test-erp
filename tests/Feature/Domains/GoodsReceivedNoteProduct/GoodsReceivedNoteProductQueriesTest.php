<?php

declare(strict_types=1);

use App\Domains\GoodsReceivedNoteProduct\GoodsReceivedNoteProductQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Enums\ProductStatuses;
use App\Models\Batch;
use App\Models\Company;
use App\Models\GoodsReceivedNote;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\PurchaseAmount;
use Illuminate\Support\Facades\Config;

test('Goods Received Note product can be added', function (): void {
    $goodsReceivedNoteProductQueries = new GoodsReceivedNoteProductQueries();
    $company = Company::factory()->create();
    $product = Product::factory()->create([
        'company_id' => $company->id,
    ]);
    $location = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $purchaseAmount = PurchaseAmount::factory()->create();
    $batch = Batch::factory()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
    ]);
    $goodReceivedNote = GoodsReceivedNote::factory()->create([
        'company_id' => $company->id,
        'location_id' => $location->id,
    ]);

    $goodsReceivedNoteProductQueries->addNew(
        1,
        $goodReceivedNote->id,
        $product->id,
        $batch->id,
        $purchaseAmount->id,
        null,
        1,
        null,
        null,
    );

    $this->assertDatabaseHas('goods_received_note_products', [
        'goods_received_note_id' => $goodReceivedNote->id,
        'product_id' => $product->id,
        'batch_id' => $batch->id,
        'purchase_amount_id' => $purchaseAmount->id,
        'unit_of_measure_derivative_id' => null,
        'input_quantity' => 1,
        'derivative_ratio' => null,
        'quantity' => 1,
    ]);
});

test('fetch goods received note products by grn id when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $company = Company::factory()->create();
    $location = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $goodReceivedNote = GoodsReceivedNote::factory()->create([
        'company_id' => $company->id,
        'location_id' => $location->id,
    ]);
    $goodsReceivedNoteProduct = GoodsReceivedNoteProduct::factory()->create([
        'goods_received_note_id' => $goodReceivedNote->id,
    ]);
    $goodsReceivedNoteProductQueries = new GoodsReceivedNoteProductQueries();
    $response = $goodsReceivedNoteProductQueries->getByGrnId(
        $goodsReceivedNoteProduct->goods_received_note_id,
        $company->id
    );
    expect($response->first()->toArray())
        ->toHaveKey('id', $goodsReceivedNoteProduct->id)
        ->toHaveKey('product_id', $goodsReceivedNoteProduct->product_id)
        ->toHaveKey('batch_id', $goodsReceivedNoteProduct->batch_id)
        ->toHaveKey('purchase_amount_id', $goodsReceivedNoteProduct->purchase_amount_id)
        ->toHaveKey('quantity', $goodsReceivedNoteProduct->quantity)
        ->toHaveKeys(['product.color', 'product.size']);
});

test('fetch goods received note products by grn id when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $company = Company::factory()->create();

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $company->id,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $product = Product::factory()->create([
        'company_id' => $company->id,
        'compound_product_name' => 'ABCD131333',
        'code' => '131313',
        'upc' => 'wrwrwr',
        'article_number' => '12346644',
        'status' => ProductStatuses::ACTIVE->value,
        'is_non_inventory' => false,
        'is_non_selling_item' => false,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'master_product_id' => $masterProduct->id,
    ]);

    $location = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $goodReceivedNote = GoodsReceivedNote::factory()->create([
        'company_id' => $company->id,
        'location_id' => $location->id,
    ]);
    $goodsReceivedNoteProduct = GoodsReceivedNoteProduct::factory()->create([
        'goods_received_note_id' => $goodReceivedNote->id,
        'product_id' => $product->id,
    ]);
    $goodsReceivedNoteProductQueries = new GoodsReceivedNoteProductQueries();
    $response = $goodsReceivedNoteProductQueries->getByGrnId(
        $goodsReceivedNoteProduct->goods_received_note_id,
        $company->id
    );
    expect($response->first()->toArray())
        ->toHaveKey('id', $goodsReceivedNoteProduct->id)
        ->toHaveKey('product_id', $goodsReceivedNoteProduct->product_id)
        ->toHaveKey('batch_id', $goodsReceivedNoteProduct->batch_id)
        ->toHaveKey('purchase_amount_id', $goodsReceivedNoteProduct->purchase_amount_id)
        ->toHaveKey('quantity', $goodsReceivedNoteProduct->quantity)
        ->toHaveKeys(['product.master_product']);
});

test('fetch goods received note products by date and location with product by brand', function (): void {
    $company = Company::factory()->create();
    $location = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $goodReceivedNote = GoodsReceivedNote::factory()->create([
        'company_id' => $company->id,
        'location_id' => $location->id,
    ]);
    $goodsReceivedNoteProduct = GoodsReceivedNoteProduct::factory()->create([
        'goods_received_note_id' => $goodReceivedNote->id,
    ]);
    $filterData = [
        'location_id' => $goodReceivedNote->location_id,
        'date_range' => [now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d')],
        'brand_ids' => [],
    ];
    $goodsReceivedNoteProductQueries = new GoodsReceivedNoteProductQueries();
    $response = $goodsReceivedNoteProductQueries->getByDateAndLocationWithProductByBrand($filterData, $company->id);
    expect($response->first()->toArray())
        ->toHaveKey('id', $goodsReceivedNoteProduct->id)
        ->toHaveKey('product_id', $goodsReceivedNoteProduct->product_id)
        ->toHaveKey('purchase_amount_id', $goodsReceivedNoteProduct->purchase_amount_id)
        ->toHaveKey('quantity', $goodsReceivedNoteProduct->quantity)
        ->toHaveKeys(['product.color', 'product.size', 'product.brand']);
});

test('fetch goods received note products by date and location with product by department', function (): void {
    $company = Company::factory()->create();
    $location = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $goodReceivedNote = GoodsReceivedNote::factory()->create([
        'company_id' => $company->id,
        'location_id' => $location->id,
    ]);
    $goodsReceivedNoteProduct = GoodsReceivedNoteProduct::factory()->create([
        'goods_received_note_id' => $goodReceivedNote->id,
    ]);
    $filterData = [
        'location_id' => $goodReceivedNote->location_id,
        'date_range' => [now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d')],
        'department_ids' => [],
    ];
    $goodsReceivedNoteProductQueries = new GoodsReceivedNoteProductQueries();
    $response = $goodsReceivedNoteProductQueries->getByDateAndLocationWithProductByDepartment(
        $filterData,
        $company->id
    );
    expect($response->first()->toArray())
        ->toHaveKey('id', $goodsReceivedNoteProduct->id)
        ->toHaveKey('product_id', $goodsReceivedNoteProduct->product_id)
        ->toHaveKey('purchase_amount_id', $goodsReceivedNoteProduct->purchase_amount_id)
        ->toHaveKey('quantity', $goodsReceivedNoteProduct->quantity)
        ->toHaveKeys(['product.color', 'product.size', 'product.department']);
});

test('if product is merged then the product id is updated', function (): void {
    $company = Company::factory()->create();
    $productAId = Product::factory()->create()->id;
    $productBId = Product::factory()->create()->id;

    $location = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $goodReceivedNote = GoodsReceivedNote::factory()->create([
        'company_id' => $company->id,
        'location_id' => $location->id,
    ]);

    $goodsReceivedNoteProduct = GoodsReceivedNoteProduct::factory()->create([
        'goods_received_note_id' => $goodReceivedNote->id,
        'product_id' => $productBId,
    ]);

    $goodsReceivedNoteProductQueries = new GoodsReceivedNoteProductQueries();
    $goodsReceivedNoteProductQueries->updateProductId($company->id, $productBId, $productAId);

    $this->assertDatabaseHas(GoodsReceivedNoteProduct::class, [
        'goods_received_note_id' => $goodReceivedNote->id,
        'product_id' => $productAId,
    ]);
});

test('fetch goods received note products by grn id for api when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $company = Company::factory()->create();

    $location = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $goodReceivedNote = GoodsReceivedNote::factory()->create([
        'company_id' => $company->id,
        'location_id' => $location->id,
    ]);
    $goodsReceivedNoteProduct = GoodsReceivedNoteProduct::factory()->create([
        'goods_received_note_id' => $goodReceivedNote->id,
    ]);
    $filterData = [
        'id' => $goodReceivedNote->id,
        'per_page' => 10,
        'page' => 1,
        'search_text' => null,
    ];
    $goodsReceivedNoteProductQueries = new GoodsReceivedNoteProductQueries();
    $response = $goodsReceivedNoteProductQueries->getByGrnIdForApi($company->id, $filterData);
    expect($response->first()->toArray())
        ->toHaveKey('id', $goodsReceivedNoteProduct->id)
        ->toHaveKey('product_id', $goodsReceivedNoteProduct->product_id)
        ->toHaveKey('batch_id', $goodsReceivedNoteProduct->batch_id)
        ->toHaveKey('purchase_amount_id', $goodsReceivedNoteProduct->purchase_amount_id)
        ->toHaveKey('quantity', $goodsReceivedNoteProduct->quantity)
        ->toHaveKeys(['product.color', 'product.size', 'batch', 'purchase_amount']);
});

test('fetch goods received note products by grn id for api when product variant is true', function (): void {
    $company = Company::factory()->create();

    Config::set('app.product_variant', true);

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $company->id,
        'has_batch' => false,
        'is_non_inventory' => false,
        'article_number' => '123',
    ]);

    $product = Product::factory()->create([
        'company_id' => $company->id,
        'compound_product_name' => 'ABCD131333',
        'code' => '131313',
        'upc' => 'wrwrwr',
        'status' => ProductStatuses::ACTIVE->value,
        'is_non_inventory' => false,
        'is_non_selling_item' => false,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'master_product_id' => $masterProduct->id,
    ]);

    $location = Location::factory()->create([
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $goodReceivedNote = GoodsReceivedNote::factory()->create([
        'company_id' => $company->id,
        'location_id' => $location->id,
    ]);
    $goodsReceivedNoteProduct = GoodsReceivedNoteProduct::factory()->create([
        'goods_received_note_id' => $goodReceivedNote->id,
        'product_id' => $product->id,
    ]);

    $filterData = [
        'id' => $goodReceivedNote->id,
        'per_page' => 10,
        'page' => 1,
        'search_text' => '123',
    ];
    $goodsReceivedNoteProductQueries = new GoodsReceivedNoteProductQueries();
    $response = $goodsReceivedNoteProductQueries->getByGrnIdForApi($company->id, $filterData);
    expect($response->first()->toArray())
        ->toHaveKey('id', $goodsReceivedNoteProduct->id)
        ->toHaveKey('product_id', $goodsReceivedNoteProduct->product_id)
        ->toHaveKey('batch_id', $goodsReceivedNoteProduct->batch_id)
        ->toHaveKey('purchase_amount_id', $goodsReceivedNoteProduct->purchase_amount_id)
        ->toHaveKey('quantity', $goodsReceivedNoteProduct->quantity)
        ->toHaveKeys(['product.master_product', 'batch', 'purchase_amount']);
});
